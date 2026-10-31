# Changelog

## Unreleased

## 1.2.1 - 2026-10-31

- Docs: `paymentIntents->complete($id)` is required once the customer returns to
  your `return_url`. Until it is called, a paid intent stays `processing`, the
  payment is not final or withdrawable and no `payment_intent.completed` webhook
  is sent.

## 1.2.0 - 2026-10-24

- `WebhookSignature::verifyTimestamped()` and `Webhook::constructEventFromHeaders()`:
  verify the timestamped `x-webhook-signature-v2` header and reject deliveries
  older than 5 minutes (replay protection). Prefer them over `constructEvent()`.

## 1.1.0 - 2026-09-29

- `paymentIntents->simulate($id)`: sandbox only, completes a payment without an
  on-chain transfer.
- Documented `livemode` on webhook events (false for sandbox organizations).

## 1.0.0 - 2026-07-30

- Initial release with payment intents, subscriptions, pay, rates, products,
  prices, catalog metadata, dynamic response objects, and webhook verification.
