<?php

declare(strict_types=1);

namespace OrcaRail\Exception;

final class AuthenticationException extends ApiException
{
    /**
     * @param array<string, mixed> $request
     */
    public function __construct(
        string $message = 'Authentication failed. Please check your API key and secret.',
        mixed $details = null,
        array $request = [],
    ) {
        parent::__construct($message, 401, 'authentication_error', $details, $request);
    }
}
