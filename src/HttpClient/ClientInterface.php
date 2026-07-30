<?php

declare(strict_types=1);

namespace OrcaRail\HttpClient;

interface ClientInterface
{
    /**
     * @param array<string, mixed>|null $body
     */
    public function request(
        string $method,
        string $path,
        ?array $body = null,
        bool $requireAuth = true,
    ): mixed;
}
