<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('building_user', function (Blueprint $table): void {
            $table->dropUnique(['building_id', 'user_id']);
            $table->unique(['building_id', 'user_id', 'role'], 'building_user_building_user_role_unique');
        });
    }

    public function down(): void
    {
        Schema::table('building_user', function (Blueprint $table): void {
            $table->dropUnique('building_user_building_user_role_unique');
            $table->unique(['building_id', 'user_id']);
        });
    }
};
