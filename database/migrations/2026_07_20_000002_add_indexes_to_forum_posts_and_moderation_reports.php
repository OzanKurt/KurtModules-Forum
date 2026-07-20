<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            // Posts are ordered/filtered by score (top posts, leaderboards).
            $table->index('score');
        });

        Schema::table('forum_moderation_reports', function (Blueprint $table) {
            // The moderation queue filters reports by state (pending/resolved/...).
            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropIndex(['score']);
        });

        Schema::table('forum_moderation_reports', function (Blueprint $table) {
            $table->dropIndex(['state']);
        });
    }
};
