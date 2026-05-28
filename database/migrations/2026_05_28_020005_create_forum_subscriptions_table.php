<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(config('auth.providers.users.table', 'users'))->cascadeOnDelete();
            $table->morphs('subscribable');
            $table->timestamps();
            $table->unique(['user_id', 'subscribable_type', 'subscribable_id'], 'forum_subscriptions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_subscriptions');
    }
};
