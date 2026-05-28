<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Enums;

enum ReportState: string
{
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
}
