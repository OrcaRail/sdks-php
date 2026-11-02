<?php

declare(strict_types=1);

namespace OrcaRail\Tests;

use OrcaRail\Enum\WebhookEventType;
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

    public function testConstructsEventFromFreshTimestampedSignature(): void
    {
        $payload = '{"type":"payment_intent.completed"}';
        $now = 1700000000;
        $signature = hash_hmac('sha256', ($now - 10) . '.' . $payload, 'secret');

        $event = Webhook::constructEventFromHeaders($payload, (string) ($now - 10), $signature, 'secret', 300, $now);
        self::assertSame('payment_intent.completed', $event->type);
    }

    public function testRejectsReplayedTimestampedDelivery(): void
    {
        $payload = '{"type":"payment_intent.completed"}';
        $now = 1700000000;
        $old = $now - 301;
        $signature = hash_hmac('sha256', $old . '.' . $payload, 'secret');

        self::assertFalse(WebhookSignature::verifyTimestamped($payload, (string) $old, $signature, 'secret', 300, $now));
        $this->expectException(SignatureVerificationException::class);
        Webhook::constructEventFromHeaders($payload, (string) $old, $signature, 'secret', 300, $now);
    }

    public function testRejectsChangedTimestampOrMissingHeaders(): void
    {
        $payload = '{"type":"payment_intent.completed"}';
        $now = 1700000000;
        $signature = hash_hmac('sha256', ($now - 3600) . '.' . $payload, 'secret');

        self::assertFalse(WebhookSignature::verifyTimestamped($payload, (string) $now, $signature, 'secret', 300, $now));
        self::assertFalse(WebhookSignature::verifyTimestamped($payload, '', $signature, 'secret', 300, $now));
        self::assertFalse(WebhookSignature::verifyTimestamped($payload, (string) $now, '', 'secret', 300, $now));
    }

    public function testEnumCoversEveryEventTheApiSends(): void
    {
        foreach (self::eventTypesSentByApi() as $type) {
            self::assertSame($type, WebhookEventType::from($type)->value);
        }
    }

    /**
     * Event types the OrcaRail API dispatches to merchant webhooks.
     *
     * @return list<string>
     */
    private static function eventTypesSentByApi(): array
    {
        return [
            'payment_intent.completed',
            'payment_intent.processing',
            'payment_intent.canceled',
            'payment_intent.requires_payment_method',
            'payment_intent.requires_confirmation',
            'subscription.created',
            'subscription.updated',
            'subscription.canceled',
            'subscription.paused',
            'subscription.resumed',
            'subscription.collection_method.updated',
            'subscription.payer_wallet.verified',
            'subscription.payment_link.created',
            'subscription.payment_link.paid',
            'subscription.payment_link.payment_failed',
            'subscription.auto_charge.allowance_insufficient',
            'subscription.auto_charge.canceled_insufficient_allowance',
            'subscription.auto_charge.canceled_insufficient_balance',
        ];
    }
}
