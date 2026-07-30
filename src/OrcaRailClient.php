<?php

declare(strict_types=1);

namespace OrcaRail;

use OrcaRail\HttpClient\ClientInterface;
use OrcaRail\HttpClient\CurlClient;
use OrcaRail\Service\AbstractService;
use OrcaRail\Service\CoreServiceFactory;
use OrcaRail\Service\PaymentIntentService;
use OrcaRail\Service\PayService;
use OrcaRail\Service\PriceService;
use OrcaRail\Service\ProductService;
use OrcaRail\Service\RateService;
use OrcaRail\Service\SubscriptionService;

/**
 * @property-read PaymentIntentService $paymentIntents
 * @property-read SubscriptionService $subscriptions
 * @property-read PayService $pay
 * @property-read RateService $rates
 * @property-read ProductService $products
 * @property-read PriceService $prices
 */
final class OrcaRailClient
{
    private readonly CoreServiceFactory $services;

    /**
     * @param array{
     *   api_key?: string,
     *   api_secret?: string,
     *   base_url?: string,
     *   timeout?: int,
     *   connect_timeout?: int,
     *   http_client?: mixed
     * } $config
     */
    public function __construct(array $config)
    {
        $httpClient = $config['http_client'] ?? null;
        if ($httpClient !== null && !$httpClient instanceof ClientInterface) {
            throw new \InvalidArgumentException('http_client must implement ClientInterface.');
        }

        if ($httpClient === null) {
            $httpClient = new CurlClient(
                $config['api_key'] ?? '',
                $config['api_secret'] ?? '',
                $config['base_url'] ?? CurlClient::DEFAULT_BASE_URL,
                $config['timeout'] ?? 30000,
                $config['connect_timeout'] ?? 10000,
            );
        }

        $this->services = new CoreServiceFactory($httpClient);
    }

    public function __get(string $name): AbstractService
    {
        return $this->services->{$name};
    }
}
