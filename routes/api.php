<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kurt\Modules\Forum\Http\Controllers\Api\BoardController;
use Kurt\Modules\Forum\Http\Controllers\Api\PostController;
use Kurt\Modules\Forum\Http\Controllers\Api\ThreadController;

/*
|--------------------------------------------------------------------------
| Forum REST API
|--------------------------------------------------------------------------
|
| Registered by ForumServiceProvider::registerModuleApi(), which wraps this
| file in the module route group (prefix, base middleware, throttle, name
| prefix "forum.api."). Read routes are public; write routes append the
| module auth middleware per-route and are guarded by Policies in the
| controller via $this->authorize().
|
*/

/** @var array<int, string> $auth */
$auth = config('forum.http.auth_middleware', ['auth']);

// Boards
Route::get('boards', [BoardController::class, 'index'])->name('boards.index');
Route::get('boards/{board}', [BoardController::class, 'show'])->name('boards.show');
Route::post('boards', [BoardController::class, 'store'])->middleware($auth)->name('boards.store');
Route::patch('boards/{board}', [BoardController::class, 'update'])->middleware($auth)->name('boards.update');
Route::delete('boards/{board}', [BoardController::class, 'destroy'])->middleware($auth)->name('boards.destroy');

// Threads — `search` must precede `{thread}` so it is not captured as an id.
Route::get('threads', [ThreadController::class, 'index'])->name('threads.index');
Route::get('threads/search', [ThreadController::class, 'search'])->name('threads.search');
Route::get('threads/{thread}', [ThreadController::class, 'show'])->name('threads.show');
Route::post('threads', [ThreadController::class, 'store'])->middleware($auth)->name('threads.store');
Route::patch('threads/{thread}', [ThreadController::class, 'update'])->middleware($auth)->name('threads.update');
Route::delete('threads/{thread}', [ThreadController::class, 'destroy'])->middleware($auth)->name('threads.destroy');
Route::post('threads/{thread}/solution', [ThreadController::class, 'markSolution'])->middleware($auth)->name('threads.solution.mark');
Route::delete('threads/{thread}/solution', [ThreadController::class, 'unmarkSolution'])->middleware($auth)->name('threads.solution.unmark');
Route::post('threads/{thread}/subscribe', [ThreadController::class, 'subscribe'])->middleware($auth)->name('threads.subscribe');
Route::delete('threads/{thread}/subscribe', [ThreadController::class, 'unsubscribe'])->middleware($auth)->name('threads.unsubscribe');

// Posts / replies
Route::get('threads/{thread}/posts', [PostController::class, 'index'])->name('threads.posts.index');
Route::post('threads/{thread}/posts', [PostController::class, 'store'])->middleware($auth)->name('threads.posts.store');
Route::patch('posts/{post}', [PostController::class, 'update'])->middleware($auth)->name('posts.update');
Route::delete('posts/{post}', [PostController::class, 'destroy'])->middleware($auth)->name('posts.destroy');
Route::post('posts/{post}/vote', [PostController::class, 'vote'])->middleware($auth)->name('posts.vote');
Route::delete('posts/{post}/vote', [PostController::class, 'unvote'])->middleware($auth)->name('posts.unvote');
