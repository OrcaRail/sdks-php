<?php

declare(strict_types=1);

namespace OrcaRail\Tests;

use OrcaRail\Exception\SignatureVerificationException;
use OrcaRail\Webhook;
use OrcaRail\WebhookSignature;
use PHPUnit\Framework\TestCase;

final class WebhookTest extends TestCase
{
    public function testConstructsVerifiedDynamicEvent(): void
    {
        $payload = '{"type":"payment_intent.completed","data":{"object":{"id":"pi_1"}},"created":123}';
        $signature = hash_hmac('sha256', $payload, 'secret');

        self::assertTrue(WebhookSignature::verifyHeader($payload, strtoupper($signature), 'secret'));
        $event = Webhook::constructEvent($payload, $signature, 'secret');
        self::assertSame('payment_intent.completed', $event->type);
        self::assertSame('pi_1', $event->data->object->id);
    }

    public function testRejectsTamperingAndMalformedSignatures(): void
    {
        $payload = '{"type":"payment_intent.completed"}';
        $signature = hash_hmac('sha256', $payload, 'secret');

        self::assertFalse(WebhookSignature::verifyHeader($payload . ' ', $signature, 'secret'));
        self::assertFalse(WebhookSignature::verifyHeader($payload, 'not-hex', 'secret'));
        self::assertFalse(WebhookSignature::verifyHeader($payload, '', 'secret'));

        $this->expectException(SignatureVerificationException::class);
        Webhook::constructEvent($payload . ' ', $signature, 'secret');
    }

    public function testRejectsInvalidJsonAfterValidSignature(): void
    {
        $payload = 'not json';
        $signature = hash_hmac('sha256', $payload, 'secret');

        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('Failed to parse webhook body');
        Webhook::constructEvent($payload, $signature, 'secret');
    }

    public function testRejectsNonObjectJson(): void
    {
        $payload = '[]';
        $signature = hash_hmac('sha256', $payload, 'secret');

        $this->expectException(SignatureVerificationException::class);
        Webhook::constructEvent($payload, $signature, 'secret');
    }
}
