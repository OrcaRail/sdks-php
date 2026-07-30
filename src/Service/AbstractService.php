<?php

declare(strict_types=1);

namespace OrcaRail\Service;

use OrcaRail\HttpClient\ClientInterface;
use OrcaRail\OrcaRailObject;

abstract class AbstractService
{
    public function __construct(protected readonly ClientInterface $client) {}

    /**
     * @param array<string, mixed>|null $body
     */
    protected function request(
        string $method,
        string $path,
        ?array $body = null,
        bool $requireAuth = true,
    ): mixed {
        return OrcaRailObject::convert($this->client->request($method, $path, $body, $requireAuth));
    }

    /**
     * @param array<string, mixed>|null $body
     */
    protected function requestObject(
        string $method,
        string $path,
        ?array $body = null,
        bool $requireAuth = true,
    ): OrcaRailObject {
        $response = $this->request($method, $path, $body, $requireAuth);
        if (!$response instanceof OrcaRailObject) {
            throw new \UnexpectedValueException('Expected an object response from the OrcaRail API.');
        }

        return $response;
    }

    protected function segment(string $value): string
    {
        return rawurlencode($value);
    }

    /** @param array<string, mixed> $params */
    protected function query(array $params): string
    {
        if ($params === []) {
            return '';
        }

        $normalize = static function (mixed $value) use (&$normalize): mixed {
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            return is_array($value) ? array_map($normalize, $value) : $value;
        };

        return '?' . http_build_query(array_map($normalize, $params), '', '&', PHP_QUERY_RFC3986);
    }
}
