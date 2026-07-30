<?php

declare(strict_types=1);

namespace OrcaRail\Exception;

final class SignatureVerificationException extends OrcaRailException
{
    public function __construct(
        string $message = 'Webhook signature verification failed.',
        public readonly string $signature = '',
    ) {
        parent::__construct($message);
    }
}
