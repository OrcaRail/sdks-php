<?php

declare(strict_types=1);

namespace OrcaRail\Enum;

enum PaymentIntentStatus: string
{
    case RequiresPaymentMethod = 'requires_payment_method';
    case RequiresConfirmation = 'requires_confirmation';
    case Processing = 'processing';
    case Completed = 'completed';
    case Canceled = 'canceled';
}
