<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kurt\Modules\Core\Http\Concerns\HandlesApiQuery;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\Forum\Enums\Visibility;
use Kurt\Modules\Forum\Http\Requests\StoreBoardRequest;
use Kurt\Modules\Forum\Http\Requests\UpdateBoardRequest;
use Kurt\Modules\Forum\Http\Resources\BoardResource;
use Kurt\Modules\Forum\Models\Board;

final class BoardController extends ApiController
{
    use HandlesApiQuery;

    /**
     * Public listing. Guests only see public boards; authenticated users see
     * unlisted/private boards too (per BoardPolicy::viewBoard).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Board::query();

        if ($request->user() === null) {
            $query->where('visibility', Visibility::Public->value);
        }

        $query = $this->applyApiFilters($query, $request, [
            'parent_id' => 'exact',
            'state' => 'exact',
            'visibility' => 'exact',
        ]);

        if (! is_string($request->query('sort')) || $request->query('sort') === '') {
            $query->orderBy('position');
        }

        $this->applyApiSorts($query, $request, [
            'position', 'name', 'thread_count', 'post_count', 'last_post_at', 'created_at',
        ]);

        return $this->respondPaginated($this->apiPaginate($query, $request), BoardResource::class);
    }

    public function show(Board $board): JsonResponse
    {
        $this->authorize('viewBoard', $board);

        return $this->respond(BoardResource::make($board->load('children')));
    }

    public function store(StoreBoardRequest $request): JsonResponse
    {
        $this->authorize('createBoard', Board::class);

        $board = Board::query()->create($request->validated());

        // Reload so DB-defaulted columns (state, visibility, counters) are
        // present on the freshly-created instance for the resource.
        $board->refresh();

        return $this->respondCreated(BoardResource::make($board));
    }

    public function update(UpdateBoardRequest $request, Board $board): JsonResponse
    {
        $this->authorize('updateBoard', $board);

        $board->update($request->validated());

        return $this->respond(BoardResource::make($board));
    }

    public function destroy(Board $board): JsonResponse
    {
        $this->authorize('deleteBoard', $board);

        $board->delete();

        return $this->respondNoContent();
    }
}
