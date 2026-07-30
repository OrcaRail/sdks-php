<?php

declare(strict_types=1);

namespace OrcaRail\Service;

use OrcaRail\OrcaRailObject;

final class PriceService extends AbstractService
{
    /** @param array<string, mixed> $params
     *  @return list<OrcaRailObject>
     */
    public function all(string $organizationId, array $params = []): array
    {
        $envelope = $this->requestObject(
            'GET',
            'organizations/' . $this->segment($organizationId) . '/prices' . $this->query($params),
        );
        $data = $envelope->data;
        if (!is_array($data)) {
            throw new \UnexpectedValueException('Expected a catalog list envelope.');
        }

        /** @var list<OrcaRailObject> $data */
        return $data;
    }

    /** @param array<string, mixed> $params */
    public function create(string $organizationId, array $params): OrcaRailObject
    {
        return $this->requestObject(
            'POST',
            'organizations/' . $this->segment($organizationId) . '/prices',
            $params,
        );
    }

    /** @param array<string, mixed> $params */
    public function update(string $organizationId, string $priceId, array $params): OrcaRailObject
    {
        return $this->requestObject(
            'PATCH',
            'organizations/' . $this->segment($organizationId) . '/prices/' . $this->segment($priceId),
            $params,
        );
    }

    public function deactivate(string $organizationId, string $priceId): OrcaRailObject
    {
        return $this->requestObject(
            'DELETE',
            'organizations/' . $this->segment($organizationId) . '/prices/' . $this->segment($priceId),
        );
    }

    /** @return list<OrcaRailObject> */
    public function listActiveRecurring(string $organizationId): array
    {
        return $this->all($organizationId, ['active' => true, 'recurring' => true]);
    }

    /** @param array{amount: string, currencyCode: string, productName: string} $params */
    public function findOneTimeByAmount(string $organizationId, array $params): ?OrcaRailObject
    {
        $amount = $this->normalizeMoneyAmount($params['amount']);
        $currency = strtolower(trim($params['currencyCode']));

        foreach ($this->all($organizationId, ['recurring' => false, 'active' => true]) as $price) {
            if ($price->type === 'recurring' || $price->recurring !== null) {
                continue;
            }

            $product = $price->product;
            $productName = $product instanceof OrcaRailObject ? $product->name : null;
            $priceCurrency = $price->currency;
            if ($productName !== $params['productName'] || !is_string($priceCurrency) || trim($priceCurrency) === '') {
                continue;
            }

            $unitAmount = $price->unit_amount_decimal;
            if (is_string($unitAmount)
                && strtolower(trim($priceCurrency)) === $currency
                && abs((float) $unitAmount - (float) $amount) < 0.000001) {
                return $price;
            }
        }

        return null;
    }

    /**
     * @param array{
     *   amount: string,
     *   currencyCode: string,
     *   tokenId: string,
     *   networkId: string,
     *   productName: string,
     *   productDescription?: string,
     *   productMetadata?: array<string, mixed>
     * } $params
     */
    public function ensureOneTime(string $organizationId, array $params): OrcaRailObject
    {
        $existing = $this->findOneTimeByAmount($organizationId, $params);
        if ($existing !== null) {
            return $existing;
        }

        $amount = $this->normalizeMoneyAmount($params['amount']);
        $currency = strtolower(trim($params['currencyCode']));
        $products = new ProductService($this->client);
        $product = null;
        foreach ($products->all($organizationId) as $candidate) {
            if ($candidate->name === $params['productName']) {
                $product = $candidate;
                break;
            }
        }

        if ($product === null) {
            $product = $products->create($organizationId, [
                'name' => $params['productName'],
                'description' => $params['productDescription'] ?? '',
                'active' => true,
                'metadata' => $params['productMetadata'] ?? [],
            ]);
        }

        if (!is_string($product->id) || $product->id === '') {
            throw new \UnexpectedValueException('Catalog product response did not contain an id.');
        }

        $nickname = 'demo-pay-onetime-' . $amount;

        return $this->create($organizationId, [
            'product' => $product->id,
            'nickname' => $nickname,
            'unit_amount_decimal' => $amount,
            'currency' => $currency,
            'token_id' => $params['tokenId'],
            'network_id' => $params['networkId'],
            'active' => true,
            'metadata' => ['seedKey' => $nickname],
        ]);
    }

    private function normalizeMoneyAmount(string $raw): string
    {
        if (!is_numeric($raw)) {
            throw new \InvalidArgumentException('Amount must be a positive number.');
        }

        $amount = (float) $raw;
        if (!is_finite($amount) || $amount <= 0) {
            throw new \InvalidArgumentException('Amount must be a positive number.');
        }

        return number_format($amount, 2, '.', '');
    }
}
