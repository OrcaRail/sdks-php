<?php

declare(strict_types=1);

namespace OrcaRail\Enum;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';
    case Paused = 'paused';
    case Completed = 'completed';
}
