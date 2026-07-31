# OrcaRail PHP SDK

The official PHP 8.1+ client for the OrcaRail API. Tested on PHP 8.1–8.4.

```bash
composer require orcarail/orcarail-php
```

```php
use OrcaRail\OrcaRailClient;

$orcarail = new OrcaRailClient([
    'api_key' => 'pk_live_...',
    'api_secret' => 'sk_live_...',
]);

$intent = $orcarail->paymentIntents->create([
    'price_id' => 'price_...',
    'return_url' => 'https://example.com/return',
]);

echo $intent->id;
```

Services are exposed lazily as `paymentIntents`, `subscriptions`, `pay`,
`rates`, `products`, and `prices`. API responses are `OrcaRailObject` values
that support both property and array access.

Webhook events must be constructed from the unmodified request body:

```php
$event = \OrcaRail\Webhook::constructEvent(
    file_get_contents('php://input'),
    $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '',
    'sk_live_...',
);
```

## Documentation

- [PHP SDK guide](https://docs.orcarail.com/docs/integration/php-sdk/)
- [PHP SDK API reference](https://docs.orcarail.com/docs/integration/php-sdk-api-reference/)

## Development

```bash
composer install
composer test
composer analyse
composer cs-check
```
