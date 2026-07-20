<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('registers no API routes in the default headless mode', function () {
    expect(config('forum.http.mode'))->toBe('headless')
        ->and(Route::has('forum.api.threads.index'))->toBeFalse()
        ->and(Route::has('forum.api.boards.index'))->toBeFalse()
        ->and(Route::has('forum.api.posts.vote'))->toBeFalse();
});

it('does not respond on the API prefix when headless', function () {
    $this->getJson('/api/forum/threads')->assertNotFound();
});
