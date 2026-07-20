<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Forum\Enums\Visibility;
use Kurt\Modules\Forum\Models\Board;
use Kurt\Modules\Forum\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->user = StubUser::create(['email' => 'user@example.com']);
    $this->moderator = StubUser::create(['email' => 'mod@example.com']);
});

it('lists public boards to guests and hides private ones', function () {
    Board::factory()->count(2)->create();
    Board::factory()->visibility(Visibility::Private)->create();

    $this->getJson('/api/forum/boards')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('shows a public board', function () {
    $board = Board::factory()->create();

    $this->getJson('/api/forum/boards/'.$board->id)
        ->assertOk()
        ->assertJsonPath('data.id', $board->id);
});

it('forbids guests from viewing a private board', function () {
    $board = Board::factory()->visibility(Visibility::Private)->create();

    $this->getJson('/api/forum/boards/'.$board->id)->assertForbidden();
});

it('forbids a non-moderator from creating a board', function () {
    $this->actingAs($this->user)
        ->postJson('/api/forum/boards', ['name' => 'New Board'])
        ->assertForbidden();
});

it('lets a moderator create, update and delete a board', function () {
    Gate::define('canModerateForum', fn ($user): bool => (int) $user->getAuthIdentifier() === $this->moderator->id);

    $created = $this->actingAs($this->moderator)
        ->postJson('/api/forum/boards', ['name' => 'Announcements'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Announcements');

    $boardId = $created->json('data.id');

    $this->actingAs($this->moderator)
        ->patchJson('/api/forum/boards/'.$boardId, ['name' => 'Renamed Board'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed Board');

    $this->actingAs($this->moderator)
        ->deleteJson('/api/forum/boards/'.$boardId)
        ->assertNoContent();

    expect(Board::query()->whereKey($boardId)->exists())->toBeFalse();
});

it('blocks guests from board writes with 401', function () {
    $board = Board::factory()->create();

    $this->postJson('/api/forum/boards', ['name' => 'x'])->assertUnauthorized();
    $this->patchJson('/api/forum/boards/'.$board->id, ['name' => 'x'])->assertUnauthorized();
    $this->deleteJson('/api/forum/boards/'.$board->id)->assertUnauthorized();
});
