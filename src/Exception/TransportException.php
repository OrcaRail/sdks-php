<?php

declare(strict_types=1);

namespace OrcaRail\Exception;

final class TransportException extends OrcaRailException
{
    /**
     * @param array<string, mixed> $request
     */
    public function __construct(string $message, public readonly array $request = [])
    {
        parent::__construct($message);
    }
}
