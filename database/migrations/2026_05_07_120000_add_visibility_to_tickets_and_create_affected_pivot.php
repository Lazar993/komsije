<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->string('visibility', 16)->default('private')->after('priority');
            $table->unsignedInteger('affected_count')->default(0)->after('visibility');

            $table->index(['building_id', 'visibility']);
        });

        Schema::create('ticket_affected_users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['ticket_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_affected_users');

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropIndex(['building_id', 'visibility']);
            $table->dropColumn(['visibility', 'affected_count']);
        });
    }
};
