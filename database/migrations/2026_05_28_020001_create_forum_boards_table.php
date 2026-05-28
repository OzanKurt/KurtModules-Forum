<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('forum_boards')->restrictOnDelete();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->string('state')->default('open');
            $table->string('visibility')->default('public');
            $table->unsignedBigInteger('thread_count')->default(0);
            $table->unsignedBigInteger('post_count')->default(0);
            $table->timestamp('last_post_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_boards');
    }
};
