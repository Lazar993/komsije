<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polls', function (Blueprint $table): void {
            $table->string('majority_type')->nullable();
        });

        Schema::create('poll_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->string('text', 500);
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE poll_options ADD COLUMN poll_question_id INTEGER REFERENCES poll_questions(id) ON DELETE CASCADE');
            Schema::table('poll_options', function (Blueprint $table): void {
                $table->index('poll_question_id');
            });
        } else {
            Schema::table('poll_options', function (Blueprint $table): void {
                $table->foreignId('poll_question_id')->nullable()->index()->constrained()->cascadeOnDelete();
            });
        }

        Schema::create('poll_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('poll_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('poll_option_id')->constrained()->cascadeOnDelete();
            $table->unique(['vote_id', 'poll_question_id']);
        });

        DB::table('polls')->orderBy('id')->each(function (object $poll): void {
            $questionId = DB::table('poll_questions')->insertGetId([
                'poll_id' => $poll->id,
                'text' => $poll->title,
            ]);
            DB::table('poll_options')->where('poll_id', $poll->id)->update(['poll_question_id' => $questionId]);
            DB::table('votes')->where('poll_id', $poll->id)->orderBy('id')->each(function (object $vote) use ($questionId): void {
                DB::table('poll_answers')->insert([
                    'vote_id' => $vote->id,
                    'poll_question_id' => $questionId,
                    'poll_option_id' => $vote->poll_option_id,
                ]);
            });
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_answers');
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('poll_options', function (Blueprint $table): void {
                $table->dropIndex(['poll_question_id']);
            });
            DB::statement('ALTER TABLE poll_options DROP COLUMN poll_question_id');
        } else {
            Schema::table('poll_options', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('poll_question_id');
            });
        }
        Schema::dropIfExists('poll_questions');
        Schema::table('polls', function (Blueprint $table): void {
            $table->dropColumn('majority_type');
        });
    }
};
