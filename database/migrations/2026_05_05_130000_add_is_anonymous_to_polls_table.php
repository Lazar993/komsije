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
            $table->boolean('is_anonymous')->default(true)->after('description');
            $table->index('is_anonymous');
        });
    }

    public function down(): void
    {
        Schema::table('polls', function (Blueprint $table): void {
            $table->dropIndex(['is_anonymous']);
            $table->dropColumn('is_anonymous');
        });
    }
};
