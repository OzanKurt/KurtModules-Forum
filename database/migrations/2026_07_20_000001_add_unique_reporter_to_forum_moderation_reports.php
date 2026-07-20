<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Collapse any pre-existing duplicate reports (same post + reporter),
        // keeping the earliest, so the unique index below can be created.
        $duplicates = DB::table('forum_moderation_reports')
            ->select('post_id', 'reporter_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('post_id', 'reporter_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('forum_moderation_reports')
                ->where('post_id', $duplicate->post_id)
                ->where('reporter_id', $duplicate->reporter_id)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        Schema::table('forum_moderation_reports', function (Blueprint $table) {
            $table->unique(['post_id', 'reporter_id']);
        });
    }

    public function down(): void
    {
        Schema::table('forum_moderation_reports', function (Blueprint $table) {
            $table->dropUnique(['post_id', 'reporter_id']);
        });
    }
};
