<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BuildingRole;
use App\Events\PollCreated;
use App\Filament\Resources\Polls\Pages\CreatePoll;
use App\Filament\Resources\Polls\Pages\EditPoll;
use App\Filament\Resources\Polls\Pages\ListPolls;
use App\Filament\Resources\Polls\Pages\ViewPoll;
use App\Models\Building;
use App\Models\Poll;
use App\Models\User;
use App\Models\Vote;
use App\Services\PollReportPdfService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PollVotingTest extends TestCase
{
    use RefreshDatabase;

    public function test_majority_thresholds_include_non_voters_and_round_up(): void
    {
        $poll = new Poll;
        $poll->majority_type = 'simple';

        $this->assertSame(8, $poll->requiredVotes(15));
        $this->assertSame(9, $poll->requiredVotes(16));
        $this->assertSame(1, $poll->requiredVotes(0));

        $poll->majority_type = 'two_thirds';

        $this->assertSame(10, $poll->requiredVotes(15));
        $this->assertSame(11, $poll->requiredVotes(16));
        $this->assertSame(1, $poll->requiredVotes(0));
    }

    public function test_only_active_residents_of_the_poll_building_are_eligible(): void
    {
        $building = Building::factory()->active()->create();
        $resident = User::factory()->create();
        $inactive = User::factory()->create(['is_active' => false]);
        $manager = User::factory()->create();
        User::factory()->create();
        $building->users()->attach($resident, ['role' => BuildingRole::Tenant->value]);
        $building->users()->attach($inactive, ['role' => BuildingRole::Tenant->value]);
        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $poll = new Poll(['building_id' => $building->id]);

        $this->assertSame([$resident->id], $poll->eligibleVoters()->pluck('users.id')->all());
    }

    public function test_decision_requires_a_majority_for_each_question_and_only_has_an_outcome_when_closed(): void
    {
        $building = Building::factory()->active()->create();
        $residents = User::factory()->count(3)->create();
        foreach ($residents as $resident) {
            $building->users()->attach($resident, ['role' => BuildingRole::Tenant->value]);
        }
        $poll = $building->polls()->create(['title' => 'Decision', 'majority_type' => 'two_thirds', 'is_active' => true]);
        $first = $poll->questions()->create(['text' => 'First question']);
        $second = $poll->questions()->create(['text' => 'Second question']);
        $firstOption = $first->options()->create(['text' => 'A']);
        $secondOption = $second->options()->create(['text' => 'B']);
        $otherOption = $second->options()->create(['text' => 'C']);

        foreach ($residents->take(2) as $index => $resident) {
            $vote = Vote::query()->create(['poll_id' => $poll->id, 'user_id' => $resident->id, 'poll_option_id' => $firstOption->id]);
            $vote->answers()->create(['poll_question_id' => $first->id, 'poll_option_id' => $firstOption->id]);
            $vote->answers()->create(['poll_question_id' => $second->id, 'poll_option_id' => $index === 0 ? $secondOption->id : $otherOption->id]);
        }

        $this->assertNull($poll->outcome());
        $this->assertSame(2, $poll->eligibleVotes()->count());
        $poll->update(['is_active' => false]);
        $this->assertFalse($poll->outcome());
        $this->assertTrue($poll->results()[0]['successful']);
        $this->assertFalse($poll->results()[1]['successful']);
        $poll->votes()->latest('id')->first()->answers()->where('poll_question_id', $second->id)->update(['poll_option_id' => $secondOption->id]);
        $this->assertTrue($poll->fresh()->outcome());
        $poll->update(['majority_type' => null]);
        $this->assertNull($poll->outcome());
    }

    public function test_resident_submits_all_answers_once_and_counts_as_one_voter(): void
    {
        [$poll, $resident, $answers] = $this->votingScenario();

        $this->actingAs($resident)->withSession(['current_building_id' => $poll->building_id])
            ->post(route('portal.polls.vote', $poll), ['answers' => $answers])
            ->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(1, $poll->votes()->count());
        $this->assertSame(2, $poll->votes()->first()->answers()->count());
        $this->assertSame(1, $poll->eligibleVotes()->count());

        $this->post(route('portal.polls.vote', $poll), ['answers' => $answers])->assertRedirect();
        $this->assertSame(1, $poll->votes()->count());
        $this->assertDatabaseCount('poll_answers', 2);
    }

    public function test_missing_extra_or_mismatched_answers_never_save_a_partial_vote(): void
    {
        [$poll, $resident, $answers] = $this->votingScenario();
        $otherPoll = $poll->building->polls()->create(['title' => 'Other']);
        $otherQuestion = $otherPoll->questions()->create(['text' => 'Other question']);
        $otherOption = $otherQuestion->options()->create(['text' => 'Other answer']);
        $questionIds = array_keys($answers);

        foreach ([
            [],
            [$questionIds[0] => $answers[$questionIds[0]]],
            $answers + [999999 => $otherOption->id],
            [$questionIds[0] => $answers[$questionIds[1]], $questionIds[1] => $answers[$questionIds[0]]],
            [$questionIds[0] => $otherOption->id, $questionIds[1] => $answers[$questionIds[1]]],
        ] as $invalidAnswers) {
            $this->actingAs($resident)->withSession(['current_building_id' => $poll->building_id])
                ->post(route('portal.polls.vote', $poll), ['answers' => $invalidAnswers])
                ->assertSessionHasErrors();
            $this->assertDatabaseCount('votes', 0);
            $this->assertDatabaseCount('poll_answers', 0);
        }
    }

    public function test_manager_and_other_building_resident_cannot_vote(): void
    {
        [$poll, $resident, $answers] = $this->votingScenario();
        $manager = User::factory()->create();
        $poll->building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);

        $this->actingAs($manager)->withSession(['current_building_id' => $poll->building_id])
            ->post(route('portal.polls.vote', $poll), ['answers' => $answers])->assertForbidden();

        $otherBuilding = Building::factory()->active()->create();
        $otherBuilding->users()->attach($resident, ['role' => BuildingRole::Tenant->value]);
        $this->actingAs($resident)->withSession(['current_building_id' => $otherBuilding->id])
            ->post(route('portal.polls.vote', $poll), ['answers' => $answers])->assertNotFound();
        $this->assertDatabaseCount('votes', 0);
    }

    public function test_closed_polls_reject_votes_and_legacy_single_option_votes_still_work(): void
    {
        [$poll, $resident, $answers] = $this->votingScenario();
        $poll->update(['ends_at' => now()->subMinute()]);
        $this->actingAs($resident)->withSession(['current_building_id' => $poll->building_id])
            ->post(route('portal.polls.vote', $poll), ['answers' => $answers])->assertRedirect();
        $this->assertDatabaseCount('votes', 0);

        $legacy = $poll->building->polls()->create(['title' => 'Legacy', 'is_active' => true]);
        $option = $legacy->options()->create(['text' => 'Yes']);
        $this->post(route('portal.polls.vote', $legacy), ['poll_option_id' => $option->id])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(1, $legacy->votes()->count());
        $this->assertSame(1, $legacy->results()[0]['options'][0]['votes']);
    }

    public function test_admin_creates_multi_question_decision_and_informational_polls(): void
    {
        Event::fake([PollCreated::class]);
        $building = Building::factory()->active()->create();
        $manager = User::factory()->create();
        $building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($manager);

        foreach ([null, 'simple', 'two_thirds'] as $majority) {
            Livewire::test(CreatePoll::class)
                ->fillForm([
                    'building_id' => $building->id,
                    'title' => 'New survey',
                    'is_decision' => $majority !== null,
                    'majority_type' => $majority,
                    'questions' => [
                        ['text' => 'First', 'options' => [['text' => 'Yes'], ['text' => 'No']]],
                        ['text' => 'Second', 'options' => [['text' => 'A'], ['text' => 'B']]],
                    ],
                ])
                ->call('create')
                ->assertHasNoFormErrors();

            $poll = $building->polls()->latest('id')->firstOrFail();
            $this->assertSame($majority, $poll->majority_type);
            $this->assertSame(2, $poll->questions()->count());
            $this->assertSame(4, $poll->options()->count());
            $this->assertSame(0, $poll->options()->whereNull('poll_question_id')->count());
        }
    }

    public function test_admin_cannot_change_questions_or_voting_rules_after_the_first_vote(): void
    {
        [$poll, $resident, $answers] = $this->votingScenario();
        $poll->update(['majority_type' => 'simple']);
        $this->actingAs($resident)->withSession(['current_building_id' => $poll->building_id])
            ->post(route('portal.polls.vote', $poll), ['answers' => $answers])->assertRedirect();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['is_super_admin' => true]));

        Livewire::test(EditPoll::class, ['record' => $poll->id])
            ->assertFormFieldIsDisabled('questions')
            ->assertFormFieldIsDisabled('building_id')
            ->assertFormFieldIsDisabled('is_decision')
            ->assertFormFieldIsDisabled('majority_type')
            ->assertFormFieldIsDisabled('is_anonymous')
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('simple', $poll->fresh()->majority_type);
        $this->assertSame(2, $poll->questions()->count());
        $this->assertDatabaseCount('poll_answers', 2);
        Livewire::test(ViewPoll::class, ['record' => $poll->id])->assertOk()->assertSee(__('Successful'));
    }

    public function test_portal_shows_questions_then_only_turnout_until_voting_closes(): void
    {
        [$poll, $resident, $answers] = $this->votingScenario();
        $poll->update(['majority_type' => 'simple']);
        $otherResident = User::factory()->create();
        $poll->building->users()->attach($otherResident, ['role' => BuildingRole::Tenant->value]);
        $url = route('portal.dashboard', ['fragment' => 'polls']);
        $headers = ['X-Requested-With' => 'XMLHttpRequest'];

        $this->actingAs($resident)->withSession(['current_building_id' => $poll->building_id])
            ->get($url, $headers)->assertOk()
            ->assertSee('First question')->assertSee('Second question')
            ->assertSee('0/2')->assertSee('answers[', false)
            ->assertDontSee(__('Successful'))->assertDontSee(__('Unsuccessful'));

        $this->post(route('portal.polls.vote', $poll), ['answers' => $answers])->assertRedirect();

        $this->get($url, $headers)->assertOk()
            ->assertSee('1/2')->assertSee(__('You already voted.'))
            ->assertDontSee('First question')->assertDontSee('Second question')
            ->assertDontSee('type="radio"', false)
            ->assertDontSee(__('Successful'))->assertDontSee(__('Unsuccessful'));

        $poll->update(['ends_at' => now()->subMinute()]);
        $this->get($url, $headers)->assertOk()
            ->assertSee('1/2')->assertSee('First question')->assertSee('Second question')
            ->assertSee(__('Unsuccessful'))->assertDontSee('type="radio"', false);

        $poll->update(['majority_type' => null]);
        $this->get($url, $headers)->assertOk()
            ->assertSee('First question')->assertDontSee(__('Successful'))->assertDontSee(__('Unsuccessful'));
    }

    public function test_existing_single_question_polls_and_votes_are_migrated_without_data_loss(): void
    {
        $migration = require database_path('migrations/2026_11_02_120000_add_poll_questions_and_decision_majority.php');
        $migration->down();
        $building = Building::factory()->active()->create();
        $resident = User::factory()->create();
        $building->users()->attach($resident, ['role' => BuildingRole::Tenant->value]);
        $poll = $building->polls()->create(['title' => 'Existing survey', 'is_active' => true]);
        $option = $poll->options()->create(['text' => 'Existing answer']);
        $vote = $poll->votes()->create(['user_id' => $resident->id, 'poll_option_id' => $option->id]);

        $migration->up();

        $question = $poll->questions()->firstOrFail();
        $this->assertSame('Existing survey', $question->text);
        $this->assertSame($question->id, $option->fresh()->poll_question_id);
        $this->assertSame($vote->id, $poll->votes()->first()->id);
        $this->assertSame($option->id, $vote->answers()->first()->poll_option_id);
        $this->assertSame(1, $poll->results()[0]['options'][0]['votes']);
        $this->assertNull($poll->fresh()->majority_type);
        $this->assertNull($poll->outcome());

        $otherResident = User::factory()->create();
        $building->users()->attach($otherResident, ['role' => BuildingRole::Tenant->value]);
        $this->actingAs($otherResident)->withSession(['current_building_id' => $building->id])
            ->post(route('portal.polls.vote', $poll), ['poll_option_id' => $option->id])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(2, $poll->votes()->count());
        $this->assertSame(2, $poll->results()[0]['options'][0]['votes']);
    }

    public function test_new_poll_labels_exist_in_both_supported_locales(): void
    {
        foreach (['en', 'sr_Latn'] as $locale) {
            $translations = json_decode(file_get_contents(lang_path($locale.'.json')), true, flags: JSON_THROW_ON_ERROR);
            foreach ([
                'Decision-making poll', 'Informational poll', 'Required majority',
                'Simple majority (more than 50%)', 'Two-thirds majority (at least 2/3)',
                'Questions', 'Question', 'Add question', 'Add option', 'Participation',
                'Outcome', 'Successful', 'Unsuccessful', 'Voting in progress',
                ':voted/:total residents voted', 'Answer every question before submitting.',
                'The answers must belong to this poll.', 'The selected option is invalid for this question.',
                'Recent polls', 'You are not eligible to vote in this poll.',
                'Votes', 'Download PDF', 'Poll results report', 'Report generated at', 'Poll type',
                'Final decision', 'Questions and results', 'Selected option',
                'Required votes per question: :count of :total eligible residents.',
                'This is an informational poll; no decision is required.',
                'A decision was reached for every question.',
                'The required majority was not reached for every question.',
                'No option received the required majority.',
                'The poll report is available only after voting closes.',
            ] as $key) {
                $this->assertNotEmpty($translations[$key] ?? null, $locale.': '.$key);
            }
        }
    }

    public function test_closed_poll_report_includes_results_decisions_and_a_valid_pdf(): void
    {
        [$poll, $resident, $answers] = $this->votingScenario();
        $poll->update(['majority_type' => 'simple', 'description' => 'Deciding on building maintenance.']);
        $this->actingAs($resident)->withSession(['current_building_id' => $poll->building_id])
            ->post(route('portal.polls.vote', $poll), ['answers' => $answers])->assertRedirect();
        $poll->update(['is_active' => false]);
        $manager = User::factory()->create();
        $poll->building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        $this->actingAs($manager);

        $service = app(PollReportPdfService::class);
        $data = $service->data($poll);
        $this->assertTrue($data['outcome']);
        $this->assertSame(1, $data['eligibleCount']);
        $this->assertSame(1, $data['votedCount']);
        $this->assertSame(1, $data['requiredVotes']);
        $this->assertSame(['Yes', 'Yes'], array_column($data['results'], 'decision'));
        $html = view('pdf.poll-results', $data)->render();
        foreach ([$poll->title, $poll->description, 'First question', 'Second question', __('Final decision'), __('Successful')] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringNotContainsString($resident->email, $html);
        $this->assertStringNotContainsString($resident->name, $html);
        $this->assertStringStartsWith('%PDF-', $service->pdf($poll));
    }

    public function test_manager_can_download_closed_poll_pdf_from_details_and_list(): void
    {
        [$poll] = $this->votingScenario();
        $poll->update(['is_active' => false]);
        $manager = User::factory()->create();
        $poll->building->users()->attach($manager, ['role' => BuildingRole::PropertyManager->value]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($manager);
        $filename = app(PollReportPdfService::class)->filename($poll);

        Livewire::test(ViewPoll::class, ['record' => $poll->id])
            ->assertActionVisible('exportPdf')
            ->callAction('exportPdf')
            ->assertHasNoActionErrors()
            ->assertFileDownloaded($filename);

        $poll->update(['is_active' => true, 'ends_at' => now()->subMinute()]);
        Livewire::test(ListPolls::class)
            ->callTableAction('exportPdf', $poll)
            ->assertHasNoActionErrors()
            ->assertFileDownloaded($filename);
    }

    public function test_open_or_reopened_poll_cannot_be_exported(): void
    {
        [$poll] = $this->votingScenario();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['is_super_admin' => true]));
        Livewire::test(ViewPoll::class, ['record' => $poll->id])
            ->assertActionHidden('exportPdf')
            ->assertNoFileDownloaded();

        $poll->update(['is_active' => false]);
        $stalePoll = $poll->fresh();
        $poll->update(['is_active' => true]);

        try {
            app(PollReportPdfService::class)->download($stalePoll);
            $this->fail('An open poll must not be exported.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_poll_export_rejects_residents_and_managers_of_other_buildings(): void
    {
        [$poll, $resident] = $this->votingScenario();
        $poll->update(['is_active' => false]);
        $otherBuilding = Building::factory()->active()->create();
        $otherManager = User::factory()->create();
        $otherBuilding->users()->attach($otherManager, ['role' => BuildingRole::PropertyManager->value]);

        foreach ([$resident, $otherManager] as $user) {
            $this->actingAs($user);
            try {
                app(PollReportPdfService::class)->download($poll);
                $this->fail('Unauthorized users must not export poll results.');
            } catch (AuthorizationException $exception) {
                $this->assertFalse($user->can('exportResults', $poll));
            }
        }
    }

    public function test_pdf_distinguishes_partial_decisions_from_informational_results_in_both_languages(): void
    {
        [$poll, $resident, $answers] = $this->votingScenario();
        $poll->update(['majority_type' => 'two_thirds']);
        $otherResidents = User::factory()->count(2)->create();
        foreach ($otherResidents as $otherResident) {
            $poll->building->users()->attach($otherResident, ['role' => BuildingRole::Tenant->value]);
        }
        $this->actingAs($resident)->withSession(['current_building_id' => $poll->building_id])
            ->post(route('portal.polls.vote', $poll), ['answers' => $answers])->assertRedirect();
        $secondQuestionId = array_key_last($answers);
        $answers[$secondQuestionId] = $poll->options()->where('poll_question_id', $secondQuestionId)->where('text', 'No')->value('id');
        $this->actingAs($otherResidents->first())
            ->post(route('portal.polls.vote', $poll), ['answers' => $answers])->assertRedirect();
        $poll->update(['is_active' => false]);
        $this->actingAs(User::factory()->create(['is_super_admin' => true]));
        $service = app(PollReportPdfService::class);

        foreach (['sr_Latn', 'en'] as $locale) {
            app()->setLocale($locale);
            $data = $service->data($poll);
            $this->assertFalse($data['outcome']);
            $this->assertSame(3, $data['eligibleCount']);
            $this->assertSame(2, $data['votedCount']);
            $this->assertSame(2, $data['requiredVotes']);
            $this->assertSame(['Yes', null], array_column($data['results'], 'decision'));
            $html = view('pdf.poll-results', $data)->render();
            $this->assertStringContainsString(__('Poll results report'), $html);
            $this->assertStringContainsString(__('Unsuccessful'), $html);
            $this->assertStringContainsString(__('Selected option').': Yes', $html);
            $this->assertStringContainsString(__('No option received the required majority.'), $html);
        }

        $poll->update(['majority_type' => null]);
        $data = $service->data($poll);
        $this->assertNull($data['outcome']);
        $this->assertSame([null, null], array_column($data['results'], 'decision'));
        $html = view('pdf.poll-results', $data)->render();
        $this->assertStringContainsString(__('This is an informational poll; no decision is required.'), $html);
        $this->assertStringNotContainsString(__('Unsuccessful'), $html);
        $this->assertStringNotContainsString(__('Successful'), $html);
        $this->assertStringNotContainsString(__('Selected option'), $html);
    }

    public function test_pdf_supports_empty_legacy_polls_and_safe_filenames(): void
    {
        $building = Building::factory()->active()->create();
        $poll = $building->polls()->create([
            'title' => 'Legacy /../ report',
            'is_active' => false,
            'majority_type' => 'simple',
        ]);
        $poll->options()->create(['text' => 'Yes']);
        $poll->options()->create(['text' => 'No']);
        $this->actingAs(User::factory()->create(['is_super_admin' => true]));
        $service = app(PollReportPdfService::class);
        $data = $service->data($poll);

        $this->assertFalse($data['outcome']);
        $this->assertSame(0, $data['votedCount']);
        $this->assertSame(0, $data['eligibleCount']);
        $this->assertNull($data['results'][0]['decision']);
        $this->assertSame($poll->title, $data['results'][0]['question']);
        $this->assertStringNotContainsString('/', $service->filename($poll));
        $this->assertStringNotContainsString('..', $service->filename($poll));
        $this->assertStringEndsWith('.pdf', $service->filename($poll));
        $this->assertStringStartsWith('%PDF-', $service->pdf($poll));
    }

    private function votingScenario(): array
    {
        $building = Building::factory()->active()->create();
        $resident = User::factory()->create();
        $building->users()->attach($resident, ['role' => BuildingRole::Tenant->value]);
        $poll = $building->polls()->create(['title' => 'Building survey', 'is_active' => true]);
        $answers = [];

        foreach (['First question', 'Second question'] as $text) {
            $question = $poll->questions()->create(['text' => $text]);
            $option = $question->options()->create(['text' => 'Yes']);
            $question->options()->create(['text' => 'No']);
            $answers[$question->id] = $option->id;
        }

        return [$poll, $resident, $answers];
    }
}
