<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table): void {
            $table->string('checksum', 64)->nullable()->after('size');
            $table->index(['ticket_id', 'checksum']);
        });
    }

    public function down(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table): void {
            $table->dropIndex(['ticket_id', 'checksum']);
            $table->dropColumn('checksum');
        });
    }
};
