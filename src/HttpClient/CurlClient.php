<?php

declare(strict_types=1);

namespace OrcaRail\HttpClient;

use OrcaRail\Exception\ApiException;
use OrcaRail\Exception\AuthenticationException;
use OrcaRail\Exception\TransportException;
use OrcaRail\Version;

final class CurlClient implements ClientInterface
{
    public const DEFAULT_BASE_URL = 'https://api.orcarail.com/api/v1';

    private readonly ?\Closure $executor;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiSecret,
        private readonly string $baseUrl = self::DEFAULT_BASE_URL,
        private readonly int $timeout = 30000,
        private readonly int $connectTimeout = 10000,
        ?\Closure $executor = null,
    ) {
        if ($apiKey === '' || $apiSecret === '') {
            throw new \InvalidArgumentException('API key and secret are required.');
        }

        if ($timeout <= 0 || $connectTimeout <= 0) {
            throw new \InvalidArgumentException('Timeouts must be positive integers.');
        }

        $this->executor = $executor;
    }

    public function request(
        string $method,
        string $path,
        ?array $body = null,
        bool $requireAuth = true,
    ): mixed {
        $method = strtoupper($method);
        if ($method === '') {
            throw new \InvalidArgumentException('HTTP method cannot be empty.');
        }

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
        $requestDetails = ['method' => $method, 'url' => $url];
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'User-Agent: orcarail-php/' . Version::VERSION,
        ];

        if ($requireAuth) {
            $headers[] = 'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret);
        }

        $encodedBody = null;
        if ($body !== null) {
            try {
                $encodedBody = json_encode($body, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new TransportException('Failed to encode request JSON: ' . $exception->getMessage(), $requestDetails);
            }
        }

        if ($this->executor !== null) {
            try {
                $response = ($this->executor)([
                    'method' => $method,
                    'url' => $url,
                    'headers' => $headers,
                    'body' => $encodedBody,
                    'timeout' => $this->timeout,
                    'connect_timeout' => $this->connectTimeout,
                ]);
            } catch (\Throwable $exception) {
                throw new TransportException('Request failed: ' . $exception->getMessage(), $requestDetails);
            }

            if (!is_array($response)
                || !isset($response['status'])
                || !is_int($response['status'])
                || !isset($response['headers'])
                || !is_array($response['headers'])
                || !isset($response['body'])
                || !is_string($response['body'])) {
                throw new TransportException('HTTP executor returned an invalid response.', $requestDetails);
            }

            /** @var array<string, string> $executorHeaders */
            $executorHeaders = array_change_key_case($response['headers'], CASE_LOWER);

            return $this->handleResponse(
                $response['status'],
                $response['body'],
                $executorHeaders['content-type'] ?? null,
                $requestDetails,
            );
        }

        $responseHeaders = [];
        $handle = curl_init($url);
        if ($handle === false) {
            throw new TransportException('Unable to initialize cURL.', $requestDetails);
        }

        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => $this->timeout,
            CURLOPT_CONNECTTIMEOUT_MS => $this->connectTimeout,
            CURLOPT_HEADERFUNCTION => static function (\CurlHandle $curl, string $header) use (&$responseHeaders): int {
                $length = strlen($header);
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return $length;
            },
        ];

        if ($encodedBody !== null) {
            $options[CURLOPT_POSTFIELDS] = $encodedBody;
        }

        curl_setopt_array($handle, $options);
        $rawBody = curl_exec($handle);
        if ($rawBody === false) {
            $message = curl_error($handle);
            $code = curl_errno($handle);
            curl_close($handle);
            throw new TransportException(sprintf('Request failed (%d): %s', $code, $message), $requestDetails);
        }

        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        return $this->handleResponse(
            $statusCode,
            (string) $rawBody,
            $responseHeaders['content-type'] ?? null,
            $requestDetails,
        );
    }

    /**
     * @param array<string, mixed> $requestDetails
     */
    private function handleResponse(
        int $statusCode,
        string $rawBody,
        ?string $contentType,
        array $requestDetails,
    ): mixed {
        $parsed = $this->parseResponse($rawBody, $contentType, $requestDetails);

        if ($statusCode >= 200 && $statusCode < 300) {
            return $parsed;
        }

        $message = sprintf('API request failed with status %d', $statusCode);
        $type = null;
        if (is_array($parsed)) {
            $message = isset($parsed['message']) ? (string) $parsed['message'] : $message;
            $type = isset($parsed['error']) ? (string) $parsed['error'] : null;
        }

        if ($statusCode === 401) {
            throw new AuthenticationException($message, $parsed, $requestDetails);
        }

        throw new ApiException($message, $statusCode, $type, $parsed, $requestDetails);
    }

    /**
     * @param array<string, mixed> $requestDetails
     */
    private function parseResponse(string $body, ?string $contentType, array $requestDetails): mixed
    {
        if ($body === '') {
            return null;
        }

        if ($contentType === null || !str_contains(strtolower($contentType), 'json')) {
            return $body;
        }

        try {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new TransportException('Failed to decode response JSON: ' . $exception->getMessage(), $requestDetails);
        }
    }
}
