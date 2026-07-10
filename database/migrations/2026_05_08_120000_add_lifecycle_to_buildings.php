<?php

declare(strict_types=1);

use App\Enums\BuildingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table): void {
            $table->string('status', 20)->default(BuildingStatus::Trial->value)->after('billing_customer_reference');

            $table->timestamp('trial_started_at')->nullable()->after('status');
            $table->timestamp('trial_ends_at')->nullable()->after('trial_started_at');

            $table->timestamp('subscription_started_at')->nullable()->after('trial_ends_at');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_started_at');

            $table->timestamp('suspended_at')->nullable()->after('subscription_ends_at');
            $table->timestamp('archived_at')->nullable()->after('suspended_at');

            $table->boolean('created_by_super_admin')->default(false)->after('archived_at');

            $table->index('status');
            $table->index('trial_ends_at');
        });

        // Existing production buildings predate the lifecycle system and must
        // never be suspended retroactively — mark them as active subscriptions.
        DB::table('buildings')->update([
            'status' => BuildingStatus::Active->value,
            'subscription_started_at' => DB::raw('created_at'),
            'created_by_super_admin' => true,
        ]);

        Schema::create('building_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['building_id', 'created_at']);
            $table->index('action');
        });

        Schema::create('building_trial_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            // Days-before-expiration milestone: 7, 5, 2, 0 (expiration day).
            $table->unsignedTinyInteger('milestone');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['building_id', 'milestone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_trial_reminders');
        Schema::dropIfExists('building_audit_logs');

        Schema::table('buildings', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropIndex(['trial_ends_at']);
            $table->dropColumn([
                'status',
                'trial_started_at',
                'trial_ends_at',
                'subscription_started_at',
                'subscription_ends_at',
                'suspended_at',
                'archived_at',
                'created_by_super_admin',
            ]);
        });
    }
};
