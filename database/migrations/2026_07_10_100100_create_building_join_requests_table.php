<?php

declare(strict_types=1);

use App\Enums\BuildingJoinRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_join_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('apartment_number');
            $table->string('status', 24)->default(BuildingJoinRequestStatus::Pending->value);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('manager_reminded_at')->nullable();
            $table->string('request_ip', 64)->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->timestamps();

            $table->index(['building_id', 'status']);
            $table->index(['building_id', 'email', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('manager_reminded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_join_requests');
    }
};
