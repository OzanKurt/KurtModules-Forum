<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FULLTEXT indexes power `Thread::scopeSearch()`'s MATCH...AGAINST branch on
     * MySQL/MariaDB. They are a MySQL/MariaDB-only feature, so this migration is
     * a no-op on every other driver (notably sqlite, which the test suite uses):
     * the scope transparently falls back to a portable LIKE query there.
     */
    public function up(): void
    {
        if (! $this->supportsFullText()) {
            return;
        }

        Schema::table('forum_threads', function (Blueprint $table) {
            $table->fullText('title', 'forum_threads_title_fulltext');
        });

        Schema::table('forum_posts', function (Blueprint $table) {
            $table->fullText('body', 'forum_posts_body_fulltext');
        });
    }

    public function down(): void
    {
        if (! $this->supportsFullText()) {
            return;
        }

        Schema::table('forum_threads', function (Blueprint $table) {
            $table->dropFullText('forum_threads_title_fulltext');
        });

        Schema::table('forum_posts', function (Blueprint $table) {
            $table->dropFullText('forum_posts_body_fulltext');
        });
    }

    private function supportsFullText(): bool
    {
        return in_array(
            Schema::getConnection()->getDriverName(),
            ['mysql', 'mariadb'],
            true,
        );
    }
};
