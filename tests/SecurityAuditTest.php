<?php

declare(strict_types=1);

namespace OrcaRail\Tests;

use OrcaRail\OrcaRailClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * plan/security 2026-11-04 (PHP-1): rawurlencode() leaves "." and ".." unchanged and libcurl
 * removes dot segments, so PriceService::deactivate($org, '..') became DELETE /organizations/{org}.
 */
final class SecurityAuditTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function unsafeSegments(): iterable
    {
        yield 'parent' => ['..'];
        yield 'current' => ['.'];
        yield 'empty' => [''];
        yield 'blank' => ['  '];
        yield 'slash' => ['a/b'];
        yield 'backslash' => ['a\\b'];
        yield 'query' => ['x?y'];
        yield 'fragment' => ['x#y'];
        yield 'nul' => ["x\0y"];
    }

    #[DataProvider('unsafeSegments')]
    public function testPhp1RejectsDotAndEmptyPathSegments(string $segment): void
    {
        $transport = new RecordingClient();
        $client = new OrcaRailClient(['http_client' => $transport]);

        try {
            $client->prices->deactivate('org_1', $segment);
            self::fail('Expected InvalidArgumentException for ' . json_encode($segment));
        } catch (\InvalidArgumentException) {
            // expected
        }

        self::assertSame([], $transport->requests, 'no request may be sent');
    }

    public function testNormalIdsStillWork(): void
    {
        $transport = new RecordingClient();
        $client = new OrcaRailClient(['http_client' => $transport]);

        $client->prices->deactivate('org_1', 'price_1');

        self::assertSame('organizations/org_1/prices/price_1', $transport->requests[0]['path']);
    }
}
