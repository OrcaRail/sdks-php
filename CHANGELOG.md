# Changelog

## Unreleased

- `paymentIntents->simulate($id)`: sandbox only, completes a payment without an
  on-chain transfer.
- Documented `livemode` on webhook events (false for sandbox organizations).

## 1.0.0 - 2026-07-30

- Initial release with payment intents, subscriptions, pay, rates, products,
  prices, catalog metadata, dynamic response objects, and webhook verification.
