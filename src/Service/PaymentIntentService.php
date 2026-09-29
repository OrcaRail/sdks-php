<?php

declare(strict_types=1);

namespace OrcaRail\Service;

use OrcaRail\OrcaRailObject;

final class PaymentIntentService extends AbstractService
{
    /** @param array<string, mixed> $params */
    public function create(array $params): OrcaRailObject
    {
        $this->validateCreateParams($params);
        if (!isset($params['payment_method_types'])
            || !is_array($params['payment_method_types'])
            || $params['payment_method_types'] === []) {
            $params['payment_method_types'] = ['crypto'];
        }

        return $this->requestObject('POST', 'payment_intents', $params);
    }

    public function retrieve(string $id): OrcaRailObject
    {
        return $this->requestObject('GET', 'payment_intents/' . $this->segment($id));
    }

    public function cancel(string $id): OrcaRailObject
    {
        return $this->requestObject('POST', 'payment_intents/' . $this->segment($id) . '/cancel', []);
    }

    /** @param array<string, mixed> $params */
    public function confirm(string $id, array $params): OrcaRailObject
    {
        return $this->requestObject('POST', 'payment_intents/' . $this->segment($id) . '/confirm', $params, true);
    }

    public function complete(string $id): OrcaRailObject
    {
        return $this->requestObject('POST', 'payment_intents/' . $this->segment($id) . '/complete', []);
    }

    /**
     * Sandbox only: complete a payment without an on-chain transfer (no wallet needed).
     * Fires the usual webhooks with livemode false. Requires a sandbox (ak_test_) key;
     * the API returns 403 SIMULATION_SANDBOX_ONLY for live organizations.
     */
    public function simulate(string $id): OrcaRailObject
    {
        return $this->requestObject('POST', 'payment_intents/' . $this->segment($id) . '/simulate', []);
    }

    /** @param array<string, mixed> $params */
    public function update(string $id, array $params): OrcaRailObject
    {
        return $this->requestObject('PATCH', 'payment_intents/' . $this->segment($id), $params);
    }

    /** @param array<string, mixed> $params */
    private function validateCreateParams(array $params): void
    {
        $direct = ['amount', 'currency', 'tokenId', 'networkId'];
        $hasPrice = isset($params['price_id']) && is_string($params['price_id']) && $params['price_id'] !== '';

        if ($hasPrice) {
            foreach ($direct as $key) {
                if (array_key_exists($key, $params)) {
                    throw new \InvalidArgumentException('price_id cannot be combined with direct amount parameters.');
                }
            }

            return;
        }

        foreach ($direct as $key) {
            if (!isset($params[$key]) || !is_string($params[$key]) || $params[$key] === '') {
                throw new \InvalidArgumentException(
                    'Payment Intent creation requires either price_id or amount, currency, tokenId, and networkId.',
                );
            }
        }
    }
}
