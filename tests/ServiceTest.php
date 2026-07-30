<?php

declare(strict_types=1);

namespace OrcaRail\Tests;

use OrcaRail\CatalogPlanMetadata;
use OrcaRail\OrcaRailClient;
use OrcaRail\OrcaRailObject;
use PHPUnit\Framework\TestCase;

final class ServiceTest extends TestCase
{
    public function testClientLazilyCachesServicesAndHydratesResponses(): void
    {
        $transport = new RecordingClient(['id' => 'pi_1', 'nested' => ['value' => 7]]);
        $client = new OrcaRailClient(['http_client' => $transport]);

        self::assertSame($client->paymentIntents, $client->paymentIntents);
        $intent = $client->paymentIntents->retrieve('pi/1');

        self::assertSame('pi_1', $intent->id);
        self::assertSame(7, $intent['nested']->value);
        self::assertSame('payment_intents/pi%2F1', $transport->requests[0]['path']);
    }

    public function testPaymentIntentRoutesDefaultsAndAuthenticatedConfirm(): void
    {
        $transport = new RecordingClient(
            ['id' => 'created'],
            ['id' => 'canceled'],
            ['id' => 'confirmed'],
            ['id' => 'completed'],
            ['id' => 'updated'],
        );
        $service = (new OrcaRailClient(['http_client' => $transport]))->paymentIntents;
        $service->create([
            'amount' => '10',
            'currency' => 'usd',
            'tokenId' => 'tok',
            'networkId' => 'net',
        ]);
        $service->cancel('pi_1');
        $service->confirm('pi_1', ['client_secret' => 'secret', 'return_url' => 'https://example.com']);
        $service->complete('pi_1');
        $service->update('pi_1', ['description' => 'updated']);

        self::assertIsArray($transport->requests[0]['body']);
        self::assertSame(['crypto'], $transport->requests[0]['body']['payment_method_types']);
        self::assertSame('payment_intents/pi_1/cancel', $transport->requests[1]['path']);
        self::assertTrue($transport->requests[2]['requireAuth']);
        self::assertSame('payment_intents/pi_1/confirm', $transport->requests[2]['path']);
        self::assertSame('payment_intents/pi_1/complete', $transport->requests[3]['path']);
        self::assertSame('PATCH', $transport->requests[4]['method']);
    }

    public function testPaymentIntentCreateAcceptsPriceAndRejectsInvalidUnions(): void
    {
        $service = (new OrcaRailClient(['http_client' => new RecordingClient(['id' => 'pi_1'])]))->paymentIntents;
        self::assertSame('pi_1', $service->create(['price_id' => 'price_1'])->id);

        try {
            $service->create(['price_id' => 'price_1', 'amount' => '10']);
            self::fail('Expected mixed create modes to fail.');
        } catch (\InvalidArgumentException) {
        }

        $this->expectException(\InvalidArgumentException::class);
        $service->create(['amount' => '10', 'currency' => 'usd']);
    }

    public function testSubscriptionRoutesValidationAndBracketedQuery(): void
    {
        $transport = new RecordingClient(
            ['id' => 'sub_1'],
            ['id' => 'sub_1'],
            ['id' => 'sub_1'],
            ['id' => 'sub_1'],
            ['data' => [], 'has_more' => false],
            ['data' => [], 'has_more' => false],
        );
        $service = (new OrcaRailClient(['http_client' => $transport]))->subscriptions;
        $service->create(['description' => 'Plan', 'price_id' => 'price_1']);
        $service->update('sub_1', ['description' => 'New']);
        $service->cancel('sub_1', ['cancellation_details' => ['feedback' => 'other']]);
        $service->resume('sub_1');
        $service->all([
            'status' => 'active',
            'current_period_end' => ['lte' => '2026-08-01T00:00:00Z'],
        ]);
        $service->listPaymentLinks('sub_1', ['limit' => 10, 'starting_after' => 'pl_1']);

        self::assertSame('DELETE', $transport->requests[2]['method']);
        self::assertSame('subscriptions/sub_1/resume', $transport->requests[3]['path']);
        self::assertStringContainsString('current_period_end%5Blte%5D=', $transport->requests[4]['path']);
        self::assertStringContainsString('starting_after=pl_1', $transport->requests[5]['path']);

        $this->expectException(\InvalidArgumentException::class);
        $service->create(['description' => 'Incomplete', 'amount' => '10']);
    }

    public function testPayRatesAndCatalogRoutes(): void
    {
        $transport = new RecordingClient(
            ['id' => 'pi_1'],
            ['id' => 'pi_1'],
            ['amountUsd' => '1.00'],
            [['code' => 'usd']],
            ['object' => 'list', 'data' => [['id' => 'prod_1']]],
            ['id' => 'prod_1'],
            ['id' => 'prod_1'],
            ['id' => 'prod_1', 'deleted' => true],
            ['object' => 'list', 'data' => [['id' => 'price_1']]],
            ['id' => 'price_1'],
            ['id' => 'price_1'],
            ['id' => 'price_1'],
        );
        $client = new OrcaRailClient(['http_client' => $transport]);
        $client->pay->get('pay/slug');
        $client->pay->cancel('pay/slug');
        $client->rates->getFiatQuote(['amount' => '10 00', 'currency' => 'IR/R']);
        self::assertSame('usd', $client->rates->getCurrencies(['active' => true])[0]->code);
        self::assertCount(1, $client->products->all('org/1'));
        $client->products->create('org/1', ['name' => 'Product']);
        $client->products->update('org/1', 'prod/1', ['active' => false]);
        $client->products->delete('org/1', 'prod/1');
        self::assertCount(1, $client->prices->all('org/1', ['active' => true, 'recurring' => false]));
        $client->prices->create('org/1', ['product' => 'prod_1']);
        $client->prices->update('org/1', 'price/1', ['active' => false]);
        $client->prices->deactivate('org/1', 'price/1');

        self::assertSame('pay/pay%2Fslug', $transport->requests[0]['path']);
        self::assertStringContainsString('amount=10%200', $transport->requests[2]['path']);
        self::assertSame('rates/currencies?active=true', $transport->requests[3]['path']);
        self::assertStringContainsString('active=true&recurring=false', $transport->requests[8]['path']);
        self::assertSame('organizations/org%2F1/prices/price%2F1', $transport->requests[11]['path']);
    }

    public function testOneTimePriceHelpersFindReuseAndCreate(): void
    {
        $existing = new RecordingClient([
            'object' => 'list',
            'data' => [[
                'id' => 'price_existing',
                'type' => 'one_time',
                'recurring' => null,
                'unit_amount_decimal' => '50.00',
                'currency' => 'usd',
                'product' => ['id' => 'prod_1', 'name' => 'Pro'],
            ]],
        ]);
        $prices = (new OrcaRailClient(['http_client' => $existing]))->prices;
        self::assertSame('price_existing', $prices->ensureOneTime('org', [
            'amount' => '50',
            'currencyCode' => 'USD',
            'productName' => 'Pro',
            'tokenId' => 'tok',
            'networkId' => 'net',
        ])->id);

        $create = new RecordingClient(
            ['object' => 'list', 'data' => []],
            ['object' => 'list', 'data' => []],
            ['id' => 'prod_new', 'name' => 'New'],
            ['id' => 'price_new'],
        );
        $prices = (new OrcaRailClient(['http_client' => $create]))->prices;
        $result = $prices->ensureOneTime('org', [
            'amount' => '75',
            'currencyCode' => 'USD',
            'productName' => 'New',
            'productDescription' => 'Description',
            'productMetadata' => ['tier' => 'pro'],
            'tokenId' => 'tok',
            'networkId' => 'net',
        ]);

        self::assertSame('price_new', $result->id);
        self::assertIsArray($create->requests[3]['body']);
        self::assertSame('demo-pay-onetime-75.00', $create->requests[3]['body']['nickname']);
        $metadata = CatalogPlanMetadata::parse(['tier' => 'pro']);
        self::assertInstanceOf(OrcaRailObject::class, $metadata);
        self::assertSame('pro', $metadata->tier);
        self::assertNull(CatalogPlanMetadata::parse(null));
    }

    public function testObjectSupportsPropertyArrayIterationAndSerialization(): void
    {
        $object = new OrcaRailObject(['id' => 'obj_1', 'nested' => ['ok' => true], 'rows' => [['id' => 2]]]);
        $object['extra'] = ['value' => 3];

        self::assertSame('obj_1', $object->id);
        self::assertTrue($object['nested']->ok);
        self::assertSame(2, $object->rows[0]->id);
        self::assertSame(3, $object->extra->value);
        self::assertSame($object->toArray(), $object->jsonSerialize());
        self::assertCount(4, iterator_to_array($object));
    }
}
