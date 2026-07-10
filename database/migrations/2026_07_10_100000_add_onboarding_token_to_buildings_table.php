<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table): void {
            $table->string('onboarding_token', 96)->nullable()->unique()->after('billing_customer_reference');
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table): void {
            $table->dropUnique(['onboarding_token']);
            $table->dropColumn('onboarding_token');
        });
    }
};
