<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves Forum post votes from the standalone `forum_votes` table onto the
 * polymorphic Interactions engagement table, then drops `forum_votes`.
 * Forum now stores votes via ozankurt/laravel-modules-interactions.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('forum_votes') || ! Schema::hasTable('interactions_interactions')) {
            return;
        }

        $postType = 'Kurt\\Modules\\Forum\\Models\\Post';

        DB::table('forum_votes')->orderBy('id')->chunkById(500, function ($rows) use ($postType): void {
            $now = now();
            $insert = [];

            foreach ($rows as $row) {
                $insert[] = [
                    'user_id' => $row->user_id,
                    'subject_type' => $postType,
                    'subject_id' => $row->post_id,
                    'type' => 'vote',
                    'value' => $row->value,
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $row->updated_at ?? $now,
                ];
            }

            if ($insert !== []) {
                DB::table('interactions_interactions')->insert($insert);
            }
        });

        Schema::dropIfExists('forum_votes');
    }

    public function down(): void
    {
        // One-way migration: votes now live in interactions_interactions.
    }
};
