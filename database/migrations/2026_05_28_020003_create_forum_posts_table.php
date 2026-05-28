<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('forum_threads')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('forum_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(config('auth.providers.users.table', 'users'))->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_root')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->foreignId('edited_by')->nullable()->constrained(config('auth.providers.users.table', 'users'))->nullOnDelete();
            $table->integer('score')->default(0);
            $table->unsignedBigInteger('reported_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['thread_id', 'created_at']);
        });

        // Deferred FK from forum_threads.last_post_id to forum_posts.id.
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->foreign('last_post_id')->references('id')->on('forum_posts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->dropForeign(['last_post_id']);
        });
        Schema::dropIfExists('forum_posts');
    }
};
