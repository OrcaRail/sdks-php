<?php

declare(strict_types=1);

namespace OrcaRail\Service;

use OrcaRail\HttpClient\ClientInterface;

final class CoreServiceFactory
{
    /** @var array<string, AbstractService> */
    private array $services = [];

    public function __construct(private readonly ClientInterface $client) {}

    public function __get(string $name): AbstractService
    {
        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        $service = match ($name) {
            'paymentIntents' => new PaymentIntentService($this->client),
            'subscriptions' => new SubscriptionService($this->client),
            'pay' => new PayService($this->client),
            'rates' => new RateService($this->client),
            'products' => new ProductService($this->client),
            'prices' => new PriceService($this->client),
            default => throw new \InvalidArgumentException(sprintf('Unknown OrcaRail service "%s".', $name)),
        };

        return $this->services[$name] = $service;
    }
}
