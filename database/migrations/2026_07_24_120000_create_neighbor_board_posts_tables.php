<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('neighbor_board_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 64);
            $table->string('title');
            $table->text('description');
            $table->string('status', 20)->default('active');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('comments_locked')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['building_id', 'status']);
            $table->index(['building_id', 'is_pinned', 'created_at']);
            $table->index('author_id');
        });

        Schema::create('neighbor_board_post_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained('neighbor_board_posts')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });

        Schema::create('neighbor_board_post_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained('neighbor_board_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['post_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neighbor_board_post_comments');
        Schema::dropIfExists('neighbor_board_post_images');
        Schema::dropIfExists('neighbor_board_posts');
    }
};
