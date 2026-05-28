<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Forum\Models\ModerationReport;

final class ModerationReportSubmitted
{
    use Dispatchable;

    public function __construct(public readonly ModerationReport $report) {}
}
