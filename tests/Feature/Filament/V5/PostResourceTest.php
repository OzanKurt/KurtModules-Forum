<?php

declare(strict_types=1);

use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Forum\Filament\V5\Resources\PostResource;
use Kurt\Modules\Forum\Filament\V5\Resources\PostResource\Pages\CreatePost;
use Kurt\Modules\Forum\Filament\V5\Resources\PostResource\Pages\ListPosts;
use Kurt\Modules\Forum\Models\Post;

beforeEach(function () {
    if (FilamentVersion::major() !== 5) {
        $this->markTestSkipped('Filament v5 is not installed.');
    }
});

it('targets the Post model and registers its pages', function () {
    expect(PostResource::getModel())->toBe(Post::class)
        ->and(array_keys(PostResource::getPages()))->toBe(['index', 'create', 'edit']);
});

it('builds a form with thread, body, is_root and media attachments', function () {
    $fields = formFieldNames(PostResource::class, CreatePost::class);

    expect($fields)
        ->toContain('thread_id', 'body', 'is_root', 'score')
        // Spatie media library attachments upload.
        ->toContain('attachments');
});

it('builds a queue table sorted by reported_count', function () {
    expect(tableColumnNames(PostResource::class, ListPosts::class))
        ->toContain('body', 'thread.title', 'is_root', 'score', 'reported_count');
});

it('exposes edit, delete and bulk delete actions', function () {
    expect(tableActionNames(PostResource::class, ListPosts::class))
        ->toContain('edit', 'delete');

    expect(tableBulkActionNames(PostResource::class, ListPosts::class))
        ->toContain('delete');
});
