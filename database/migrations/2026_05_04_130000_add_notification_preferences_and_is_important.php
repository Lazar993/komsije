<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('notify_push')->default(true)->after('locale');
            $table->boolean('notify_email')->default(false)->after('notify_push');
            $table->boolean('notify_email_announcements')->default(false)->after('notify_email');
            $table->boolean('notify_email_tickets')->default(false)->after('notify_email_announcements');
            $table->string('notify_digest', 16)->default('none')->after('notify_email_tickets');
            $table->timestamp('last_digest_sent_at')->nullable()->after('notify_digest');
        });

        Schema::table('announcements', function (Blueprint $table): void {
            $table->boolean('is_important')->default(false)->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'notify_push',
                'notify_email',
                'notify_email_announcements',
                'notify_email_tickets',
                'notify_digest',
                'last_digest_sent_at',
            ]);
        });

        Schema::table('announcements', function (Blueprint $table): void {
            $table->dropColumn('is_important');
        });
    }
};
