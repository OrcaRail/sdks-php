<?php

declare(strict_types=1);

namespace OrcaRail\Exception;

class ApiException extends OrcaRailException
{
    /**
     * @param array<string, mixed> $request
     */
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly ?string $type = null,
        public readonly mixed $details = null,
        public readonly array $request = [],
    ) {
        parent::__construct($message, $statusCode);
    }
}
