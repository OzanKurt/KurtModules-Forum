<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(config('auth.providers.users.table', 'users'))->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained('forum_badges')->cascadeOnDelete();
            $table->timestamp('awarded_at');
            $table->json('context')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'badge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_user_badges');
    }
};
