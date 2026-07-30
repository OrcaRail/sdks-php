<?php

declare(strict_types=1);

namespace OrcaRail\Service;

use OrcaRail\OrcaRailObject;

final class SubscriptionService extends AbstractService
{
    /** @param array<string, mixed> $params */
    public function create(array $params): OrcaRailObject
    {
        $this->validateCreateParams($params);

        return $this->requestObject('POST', 'subscriptions', $params);
    }

    public function retrieve(string $id): OrcaRailObject
    {
        return $this->requestObject('GET', 'subscriptions/' . $this->segment($id));
    }

    /** @param array<string, mixed> $params */
    public function update(string $id, array $params): OrcaRailObject
    {
        return $this->requestObject('PATCH', 'subscriptions/' . $this->segment($id), $params);
    }

    /** @param array<string, mixed> $params */
    public function cancel(string $id, array $params = []): OrcaRailObject
    {
        return $this->requestObject('DELETE', 'subscriptions/' . $this->segment($id), $params === [] ? null : $params);
    }

    public function resume(string $id): OrcaRailObject
    {
        return $this->requestObject('POST', 'subscriptions/' . $this->segment($id) . '/resume', []);
    }

    /** @param array<string, mixed> $params */
    public function all(array $params = []): OrcaRailObject
    {
        return $this->requestObject('GET', 'subscriptions' . $this->query($params));
    }

    /** @param array<string, mixed> $params */
    public function listPaymentLinks(string $id, array $params = []): OrcaRailObject
    {
        return $this->requestObject(
            'GET',
            'subscriptions/' . $this->segment($id) . '/payment-links' . $this->query($params),
        );
    }

    /** @param array<string, mixed> $params */
    private function validateCreateParams(array $params): void
    {
        $direct = ['amount', 'currency', 'token_id', 'network_id'];
        $hasPrice = isset($params['price_id']) && is_string($params['price_id']) && $params['price_id'] !== '';

        if ($hasPrice) {
            foreach ($direct as $key) {
                if (array_key_exists($key, $params)) {
                    throw new \InvalidArgumentException('price_id cannot be combined with direct amount parameters.');
                }
            }

            return;
        }

        foreach ([...$direct, 'interval'] as $key) {
            if (!isset($params[$key]) || !is_string($params[$key]) || $params[$key] === '') {
                throw new \InvalidArgumentException(
                    'Subscription creation requires either price_id or amount, currency, token_id, network_id, and interval.',
                );
            }
        }
    }
}
