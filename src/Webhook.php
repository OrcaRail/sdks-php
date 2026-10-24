<?php

declare(strict_types=1);

namespace OrcaRail;

use OrcaRail\Exception\SignatureVerificationException;

final class Webhook
{
    /**
     * Verifies x-webhook-timestamp + x-webhook-signature-v2 (replay-safe) and parses the event.
     * Prefer this over constructEvent().
     */
    public static function constructEventFromHeaders(
        string $payload,
        string $timestamp,
        string $signatureV2,
        string $secret,
        int $toleranceSeconds = 300,
        ?int $now = null,
    ): OrcaRailObject {
        if (!WebhookSignature::verifyTimestamped($payload, $timestamp, $signatureV2, $secret, $toleranceSeconds, $now)) {
            throw new SignatureVerificationException(
                'Webhook signature verification failed: missing, invalid or expired timestamped signature.',
                $signatureV2,
            );
        }

        return self::decode($payload, $signatureV2);
    }

    public static function constructEvent(string $payload, string $signature, string $secret): OrcaRailObject
    {
        WebhookSignature::assertValid($payload, $signature, $secret);

        return self::decode($payload, $signature);
    }

    private static function decode(string $payload, string $signature): OrcaRailObject
    {

        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new SignatureVerificationException(
                'Failed to parse webhook body: ' . $exception->getMessage(),
                $signature,
            );
        }

        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new SignatureVerificationException('Webhook body must be a JSON object.', $signature);
        }

        return new OrcaRailObject($decoded);
    }
}
