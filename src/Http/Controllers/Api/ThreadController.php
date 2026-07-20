<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Http\Controllers\Api;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Core\Http\Concerns\HandlesApiQuery;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\Forum\Exceptions\SolutionPostMismatchException;
use Kurt\Modules\Forum\Http\Requests\StoreThreadRequest;
use Kurt\Modules\Forum\Http\Requests\UpdateThreadRequest;
use Kurt\Modules\Forum\Http\Resources\ThreadResource;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;

final class ThreadController extends ApiController
{
    use HandlesApiQuery;

    /** Sort tokens accepted by index(), mapped to their backing columns. */
    private const SORTS = [
        'created_at' => 'created_at',
        'last_post' => 'last_post_at',
        'replies' => 'reply_count',
    ];

    /**
     * Public listing with filter[board|author|solved], sort and pagination.
     * Hidden threads are never listed.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Thread::query()->where('is_hidden', false);

        $this->applyThreadFilters($query, $request);
        $this->applyThreadSort($query, $request);

        return $this->respondPaginated($this->apiPaginate($query, $request), ThreadResource::class);
    }

    /**
     * Full-text (or portable LIKE) search over thread titles and post bodies.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1'],
        ]);

        $query = Thread::query()
            ->where('is_hidden', false)
            ->search($validated['q']);

        return $this->respondPaginated($this->apiPaginate($query, $request), ThreadResource::class);
    }

    public function show(Thread $thread): JsonResponse
    {
        $this->authorize('viewThread', $thread);

        return $this->respond(ThreadResource::make(
            $thread->load(['board', 'rootPost', 'solutionPost']),
        ));
    }

    public function store(StoreThreadRequest $request): JsonResponse
    {
        /** @var Board $board */
        $board = Board::query()->findOrFail($request->integer('board_id'));

        $this->authorize('createThread', $board);

        /** @var Authenticatable&Model $user */
        $user = $request->user();

        $thread = DB::transaction(function () use ($board, $user, $request): Thread {
            /** @var Thread $thread */
            $thread = $board->threads()->create([
                'user_id' => $user->getAuthIdentifier(),
                'title' => $request->string('title')->value(),
            ]);

            $thread->posts()->create([
                'user_id' => $user->getAuthIdentifier(),
                'body' => $request->string('body')->value(),
                'is_root' => true,
            ]);

            return $thread;
        });

        return $this->respondCreated(ThreadResource::make(
            $thread->load(['board', 'rootPost']),
        ));
    }

    public function update(UpdateThreadRequest $request, Thread $thread): JsonResponse
    {
        $this->authorize('updateThread', $thread);

        $thread->update(['title' => $request->string('title')->value()]);

        return $this->respond(ThreadResource::make($thread));
    }

    public function destroy(Thread $thread): JsonResponse
    {
        $this->authorize('deleteThread', $thread);

        $thread->delete();

        return $this->respondNoContent();
    }

    public function markSolution(Request $request, Thread $thread): JsonResponse
    {
        $this->authorize('markSolution', $thread);

        $validated = $request->validate([
            'post_id' => ['required', 'integer', 'exists:forum_posts,id'],
        ]);

        /** @var Post $post */
        $post = Post::query()->findOrFail($validated['post_id']);

        try {
            $thread->markSolution($post);
        } catch (SolutionPostMismatchException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->respond(ThreadResource::make($thread->fresh()));
    }

    public function unmarkSolution(Thread $thread): JsonResponse
    {
        $this->authorize('unmarkSolution', $thread);

        $thread->unmarkSolution();

        return $this->respond(ThreadResource::make($thread->fresh()));
    }

    public function subscribe(Request $request, Thread $thread): JsonResponse
    {
        $this->authorize('viewThread', $thread);

        /** @var Authenticatable&Model $user */
        $user = $request->user();

        $thread->subscribe($user);

        return $this->respondCreated(['subscribed' => true, 'thread_id' => $thread->id]);
    }

    public function unsubscribe(Request $request, Thread $thread): JsonResponse
    {
        /** @var Authenticatable&Model $user */
        $user = $request->user();

        $thread->unsubscribe($user);

        return $this->respondNoContent();
    }

    /**
     * @param  Builder<Thread>  $query
     */
    private function applyThreadFilters(Builder $query, Request $request): void
    {
        $filters = $request->query('filter');

        if (! is_array($filters)) {
            return;
        }

        if (isset($filters['board']) && is_scalar($filters['board'])) {
            $query->where('board_id', (int) $filters['board']);
        }

        if (isset($filters['author']) && is_scalar($filters['author'])) {
            $query->where('user_id', (int) $filters['author']);
        }

        if (array_key_exists('solved', $filters) && is_scalar($filters['solved'])) {
            filter_var($filters['solved'], FILTER_VALIDATE_BOOLEAN)
                ? $query->whereNotNull('solution_post_id')
                : $query->whereNull('solution_post_id');
        }
    }

    /**
     * @param  Builder<Thread>  $query
     */
    private function applyThreadSort(Builder $query, Request $request): void
    {
        $sort = $request->query('sort');

        if (! is_string($sort) || $sort === '') {
            $query->pinnedFirst();

            return;
        }

        $direction = 'asc';

        if (str_starts_with($sort, '-')) {
            $direction = 'desc';
            $sort = substr($sort, 1);
        }

        $column = self::SORTS[$sort] ?? null;

        if ($column === null) {
            $query->pinnedFirst();

            return;
        }

        $query->orderBy($column, $direction);
    }
}
