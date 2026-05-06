<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polls', function (Blueprint $table): void {
            // Stamps so the scheduled reminder + close jobs don't double-send.
            $table->timestamp('ending_reminder_sent_at')->nullable()->after('ends_at');
            $table->timestamp('closed_notified_at')->nullable()->after('ending_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('polls', function (Blueprint $table): void {
            $table->dropColumn(['ending_reminder_sent_at', 'closed_notified_at']);
        });
    }
};
