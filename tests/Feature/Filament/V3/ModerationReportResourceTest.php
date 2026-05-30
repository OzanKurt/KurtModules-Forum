<?php

declare(strict_types=1);

use Filament\Tables\Table;
use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Forum\Enums\ReportState;
use Kurt\Modules\Forum\Filament\V3\Resources\ModerationReportResource;
use Kurt\Modules\Forum\Filament\V3\Resources\ModerationReportResource\Pages\ListModerationReports;
use Kurt\Modules\Forum\Models\ModerationReport;

beforeEach(function () {
    if (FilamentVersion::major() !== 3) {
        $this->markTestSkipped('Filament v3 is not installed.');
    }
});

it('targets the ModerationReport model and registers a list + edit page (no create)', function () {
    expect(ModerationReportResource::getModel())->toBe(ModerationReport::class)
        ->and(array_keys(ModerationReportResource::getPages()))->toBe(['index', 'edit']);
});

it('builds a reason + state + notes form', function () {
    $fields = formFieldNames(ModerationReportResource::class, ListModerationReports::class);

    expect($fields)->toContain('reason', 'state', 'notes');
});

it('defaults the queue to pending reports', function () {
    expect(tableFilterNames(ModerationReportResource::class, ListModerationReports::class))
        ->toContain('state');

    $table = ModerationReportResource::table(
        Table::make(app(ListModerationReports::class))
    );
    $filter = $table->getFilters()['state'];

    expect($filter->getDefaultState())->toBe(ReportState::Pending->value);
});

it('offers resolve/dismiss row actions and resolve/dismiss/delete bulk actions', function () {
    expect(tableActionNames(ModerationReportResource::class, ListModerationReports::class))
        ->toContain('resolve', 'dismiss', 'edit', 'delete');

    expect(tableBulkActionNames(ModerationReportResource::class, ListModerationReports::class))
        ->toContain('resolve', 'dismiss', 'delete');
});
