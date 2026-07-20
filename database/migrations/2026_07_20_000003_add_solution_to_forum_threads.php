<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_threads', function (Blueprint $table) {
            // The post accepted as the thread's answer. Nullable: a thread is
            // "unsolved" until an author/moderator marks a solution. The FK is
            // added separately so the column ordering is predictable.
            $table->unsignedBigInteger('solution_post_id')->nullable()->after('score');
        });

        Schema::table('forum_threads', function (Blueprint $table) {
            // If the solution post row is hard-deleted, clear the pointer rather
            // than blocking the delete. Soft-deletes leave the pointer intact.
            $table->foreign('solution_post_id')
                ->references('id')->on('forum_posts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->dropForeign(['solution_post_id']);
            $table->dropColumn('solution_post_id');
        });
    }
};
