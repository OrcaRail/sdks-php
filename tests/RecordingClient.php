<?php

declare(strict_types=1);

namespace OrcaRail\Tests;

use OrcaRail\HttpClient\ClientInterface;

final class RecordingClient implements ClientInterface
{
    /** @var list<array{method: string, path: string, body: array<string, mixed>|null, requireAuth: bool}> */
    public array $requests = [];

    /** @var list<mixed> */
    private array $responses;

    public function __construct(mixed ...$responses)
    {
        $this->responses = array_values($responses);
    }

    public function request(
        string $method,
        string $path,
        ?array $body = null,
        bool $requireAuth = true,
    ): mixed {
        $this->requests[] = compact('method', 'path', 'body', 'requireAuth');

        if ($this->responses === []) {
            return ['id' => 'default'];
        }

        $response = array_shift($this->responses);
        if ($response instanceof \Throwable) {
            throw $response;
        }

        return $response;
    }
}
