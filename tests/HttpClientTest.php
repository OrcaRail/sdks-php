<?php

declare(strict_types=1);

namespace OrcaRail\Tests;

use OrcaRail\Exception\ApiException;
use OrcaRail\Exception\AuthenticationException;
use OrcaRail\Exception\TransportException;
use OrcaRail\HttpClient\CurlClient;
use OrcaRail\Version;
use PHPUnit\Framework\TestCase;

final class HttpClientTest extends TestCase
{
    public function testBuildsAuthenticatedJsonRequestAndParsesJson(): void
    {
        $captured = null;
        $executor = static function (array $request) use (&$captured): array {
            $captured = $request;

            return [
                'status' => 200,
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'body' => '{"id":"pi_1"}',
            ];
        };
        $client = new CurlClient('pk_test', 'sk_test', 'https://api.example.test/v1/', 1234, 456, $executor);

        self::assertSame(['id' => 'pi_1'], $client->request('post', '/payment_intents', ['amount' => '10']));
        self::assertIsArray($captured);
        self::assertSame('POST', $captured['method']);
        self::assertSame('https://api.example.test/v1/payment_intents', $captured['url']);
        self::assertContains('Authorization: Basic ' . base64_encode('pk_test:sk_test'), $captured['headers']);
        self::assertContains('User-Agent: orcarail-php/' . Version::VERSION, $captured['headers']);
        self::assertSame('{"amount":"10"}', $captured['body']);
        self::assertSame(1234, $captured['timeout']);
        self::assertSame(456, $captured['connect_timeout']);
    }

    public function testOmitsAuthAndDoesNotAddGetCacheBuster(): void
    {
        $captured = null;
        $client = new CurlClient('pk', 'sk', executor: static function (array $request) use (&$captured): array {
            $captured = $request;

            return ['status' => 200, 'headers' => ['content-type' => 'text/plain'], 'body' => 'ok'];
        });

        self::assertSame('ok', $client->request('GET', 'health?ready=true', null, false));
        self::assertIsArray($captured);
        self::assertSame('https://api.orcarail.com/api/v1/health?ready=true', $captured['url']);
        self::assertFalse((bool) array_filter(
            $captured['headers'],
            static fn(string $header): bool => str_starts_with($header, 'Authorization:'),
        ));
    }

    public function testThrowsAuthenticationAndApiErrorsWithDetails(): void
    {
        $authentication = new CurlClient('pk', 'sk', executor: static fn(): array => [
            'status' => 401,
            'headers' => ['content-type' => 'application/json'],
            'body' => '{"message":"Bad credentials"}',
        ]);

        try {
            $authentication->request('GET', 'private');
            self::fail('Expected authentication error.');
        } catch (AuthenticationException $exception) {
            self::assertSame(401, $exception->statusCode);
            self::assertSame('Bad credentials', $exception->getMessage());
            self::assertSame('GET', $exception->request['method']);
        }

        $api = new CurlClient('pk', 'sk', executor: static fn(): array => [
            'status' => 422,
            'headers' => ['content-type' => 'application/json'],
            'body' => '{"message":"Invalid amount","error":"validation_error","fields":{"amount":"positive"}}',
        ]);

        try {
            $api->request('POST', 'payment_intents', ['amount' => '-1']);
            self::fail('Expected API error.');
        } catch (ApiException $exception) {
            self::assertSame(422, $exception->statusCode);
            self::assertSame('validation_error', $exception->type);
            self::assertSame('positive', $exception->details['fields']['amount']);
        }
    }

    public function testWrapsExecutorAndInvalidJsonAsTransportErrors(): void
    {
        $failed = new CurlClient('pk', 'sk', executor: static function (): never {
            throw new \RuntimeException('connection refused');
        });

        try {
            $failed->request('GET', 'payment_intents/pi_1');
            self::fail('Expected transport error.');
        } catch (TransportException $exception) {
            self::assertStringContainsString('connection refused', $exception->getMessage());
            self::assertStringEndsWith('/payment_intents/pi_1', $exception->request['url']);
        }

        $invalidJson = new CurlClient('pk', 'sk', executor: static fn(): array => [
            'status' => 200,
            'headers' => ['content-type' => 'application/json'],
            'body' => '{invalid',
        ]);

        $this->expectException(TransportException::class);
        $invalidJson->request('GET', 'bad-json');
    }

    public function testRejectsMissingCredentialsAndBadTimeouts(): void
    {
        try {
            new CurlClient('', '');
            self::fail('Expected credentials validation.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('required', $exception->getMessage());
        }

        $this->expectException(\InvalidArgumentException::class);
        new CurlClient('pk', 'sk', timeout: 0);
    }
}
