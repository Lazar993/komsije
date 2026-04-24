<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use LogicException;
use UnitEnum;

/**
 * @property-read Schema $form
 */
final class Profile extends Page
{
    public ?array $data = [];

    protected static string | UnitEnum | null $navigationGroup = 'Account';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'profile';

    public function mount(): void
    {
        $this->form->fill($this->getUser()->attributesToArray());
    }

    public static function getNavigationLabel(): string
    {
        return __('Profile');
    }

    public function getTitle(): string | Htmlable
    {
        return __('My profile');
    }

    public function getUser(): Authenticatable & Model
    {
        $user = Filament::auth()->user();

        if (! $user instanceof Model) {
            throw new LogicException('The authenticated user must be an Eloquent model.');
        }

        return $user;
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->model($this->getUser())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('profile_image_path')
                ->label(__('Profile image'))
                ->avatar()
                ->disk('public')
                ->directory('profile-images')
                ->visibility('public')
                ->imageEditor()
                ->imageAspectRatio('1:1')
                ->automaticallyOpenImageEditorForAspectRatio()
                ->automaticallyResizeImagesMode('cover')
                ->automaticallyResizeImagesToWidth('300')
                ->automaticallyResizeImagesToHeight('300')
                ->imageEditorViewportWidth(300)
                ->imageEditorViewportHeight(300)
                ->maxSize(2048),
            TextInput::make('name')
                ->label(__('Name'))
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label(__('Email address'))
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('password')
                ->label(__('Password'))
                ->password()
                ->revealable(filament()->arePasswordsRevealable())
                ->rule(Password::default())
                ->dehydrated(fn (mixed $state): bool => filled($state))
                ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                ->same('passwordConfirmation')
                ->autocomplete('new-password')
                ->live(debounce: 500),
            TextInput::make('passwordConfirmation')
                ->label(__('Confirm password'))
                ->password()
                ->revealable(filament()->arePasswordsRevealable())
                ->dehydrated(false)
                ->autocomplete('new-password')
                ->visible(fn (Get $get): bool => filled($get('password'))),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->getUser()->update($data);

        $this->data['password'] = null;
        $this->data['passwordConfirmation'] = null;

        Notification::make()
            ->success()
            ->title(__('Profile saved.'))
            ->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Ticket activity'))
                ->schema([
                    \Filament\Schemas\Components\View::make('filament.pages.profile.stats')
                        ->viewData(['stats' => $this->getTicketStats()]),
                ]),
            Section::make(__('Recent tickets'))
                ->schema([
                    \Filament\Schemas\Components\View::make('filament.pages.profile.recent-tickets')
                        ->viewData(['tickets' => $this->getRecentTickets()]),
                ]),
            Section::make(__('Edit account'))
                ->schema([
                    $this->getFormContentComponent(),
                ]),
        ]);
    }

    protected function getFormContentComponent(): Form
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->key('form-actions'),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('Save changes'))
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function getFormActionsAlignment(): string | Alignment
    {
        return Alignment::Start;
    }

    /**
     * @return array<string, int>
     */
    private function getTicketStats(): array
    {
        $query = Ticket::query()->where('reported_by', $this->getUser()->getKey());

        return [
            'active' => (clone $query)
                ->whereIn('status', [TicketStatus::New->value, TicketStatus::InProgress->value])
                ->count(),
            'resolved' => (clone $query)
                ->where('status', TicketStatus::Resolved->value)
                ->count(),
            'total' => (clone $query)->count(),
        ];
    }

    private function getRecentTickets(): iterable
    {
        return Ticket::query()
            ->where('reported_by', $this->getUser()->getKey())
            ->with(['building', 'apartment'])
            ->latest()
            ->limit(5)
            ->get();
    }
}