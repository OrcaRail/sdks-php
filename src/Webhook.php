<?php

declare(strict_types=1);

namespace OrcaRail;

use OrcaRail\Exception\SignatureVerificationException;

final class Webhook
{
    public static function constructEvent(string $payload, string $signature, string $secret): OrcaRailObject
    {
        WebhookSignature::assertValid($payload, $signature, $secret);

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
