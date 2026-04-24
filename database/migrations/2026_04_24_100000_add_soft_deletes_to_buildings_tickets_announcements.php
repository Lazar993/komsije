<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', static function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('tickets', static function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('announcements', static function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('buildings', static function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('tickets', static function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('announcements', static function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
