<?php

declare(strict_types=1);

namespace OrcaRail\Service;

use OrcaRail\OrcaRailObject;

final class RateService extends AbstractService
{
    /** @param array{amount: string, currency: string} $params */
    public function getFiatQuote(array $params): OrcaRailObject
    {
        return $this->requestObject('GET', 'rates/fiat-quote' . $this->query($params));
    }

    /**
     * @param array{active?: bool} $options
     * @return list<OrcaRailObject>
     */
    public function getCurrencies(array $options = []): array
    {
        $params = ($options['active'] ?? false) === true ? ['active' => true] : [];
        $response = $this->request('GET', 'rates/currencies' . $this->query($params));
        if (!is_array($response)) {
            throw new \UnexpectedValueException('Expected a list response from the OrcaRail API.');
        }

        /** @var list<OrcaRailObject> $response */
        return $response;
    }
}
