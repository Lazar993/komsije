<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', static function (Blueprint $table): void {
            $table->index('reported_by');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', static function (Blueprint $table): void {
            $table->dropIndex(['reported_by']);
            $table->dropIndex(['assigned_to']);
        });
    }
};
