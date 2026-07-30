<?php

declare(strict_types=1);

namespace OrcaRail\Enum;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case PartialConfirmed = 'partial_confirmed';
    case Confirmed = 'confirmed';
    case Canceled = 'canceled';
    case Expired = 'expired';
    case Completed = 'completed';
    case Withdrawn = 'withdrawn';
}
