<?php

declare(strict_types=1);

namespace OrcaRail;

use OrcaRail\Exception\SignatureVerificationException;

final class WebhookSignature
{
    public static function verifyHeader(string $payload, string $signature, string $secret): bool
    {
        if ($signature === '' || $secret === '' || preg_match('/^[a-f0-9]{64}$/i', $signature) !== 1) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $payload, $secret), strtolower($signature));
    }

    public static function assertValid(string $payload, string $signature, string $secret): void
    {
        if (!self::verifyHeader($payload, $signature, $secret)) {
            throw new SignatureVerificationException(
                'Webhook signature verification failed. The signature does not match the expected signature.',
                $signature,
            );
        }
    }
}
