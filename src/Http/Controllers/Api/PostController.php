<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Http\Controllers\Api;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kurt\Modules\Core\Http\Concerns\HandlesApiQuery;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\Forum\Http\Requests\StorePostRequest;
use Kurt\Modules\Forum\Http\Requests\UpdatePostRequest;
use Kurt\Modules\Forum\Http\Requests\VotePostRequest;
use Kurt\Modules\Forum\Http\Resources\PostResource;
use Kurt\Modules\Forum\Models\Post;
use Kurt\Modules\Forum\Models\Thread;

final class PostController extends ApiController
{
    use HandlesApiQuery;

    /**
     * Paginated replies for a thread (the root post is carried on the thread
     * payload). Reads are public but gated on being able to view the thread.
     */
    public function index(Request $request, Thread $thread): JsonResponse
    {
        $this->authorize('viewThread', $thread);

        $query = Post::query()
            ->where('thread_id', $thread->id)
            ->replies()
            ->orderBy('created_at');

        return $this->respondPaginated($this->apiPaginate($query, $request), PostResource::class);
    }

    public function store(StorePostRequest $request, Thread $thread): JsonResponse
    {
        $this->authorize('replyToThread', $thread);

        /** @var Authenticatable&Model $user */
        $user = $request->user();

        $parent = null;

        if ($request->filled('parent_id')) {
            /** @var Post $parent */
            $parent = Post::query()->findOrFail($request->integer('parent_id'));
        }

        $post = $thread->reply($user, $request->string('body')->value(), $parent);

        return $this->respondCreated(PostResource::make($post));
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $this->authorize('editPost', $post);

        /** @var Authenticatable&Model $user */
        $user = $request->user();

        $post->update([
            'body' => $request->string('body')->value(),
            'edited_at' => now(),
            'edited_by' => $user->getAuthIdentifier(),
        ]);

        return $this->respond(PostResource::make($post));
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('deletePost', $post);

        $post->delete();

        return $this->respondNoContent();
    }

    public function vote(VotePostRequest $request, Post $post): JsonResponse
    {
        $this->authorize('votePost', $post);

        /** @var Authenticatable&Model $user */
        $user = $request->user();

        $post->vote($user, $request->voteValue());

        return $this->respond(PostResource::make($post->fresh()));
    }

    public function unvote(Request $request, Post $post): JsonResponse
    {
        $this->authorize('votePost', $post);

        /** @var Authenticatable&Model $user */
        $user = $request->user();

        $post->unvote($user);

        return $this->respond(PostResource::make($post->fresh()));
    }
}
