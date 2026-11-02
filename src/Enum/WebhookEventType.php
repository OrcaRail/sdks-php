<?php

declare(strict_types=1);

namespace OrcaRail\Enum;

/**
 * Webhook event types the OrcaRail API sends.
 */
enum WebhookEventType: string
{
    case PaymentIntentCompleted = 'payment_intent.completed';
    case PaymentIntentProcessing = 'payment_intent.processing';
    case PaymentIntentCanceled = 'payment_intent.canceled';
    case PaymentIntentRequiresPaymentMethod = 'payment_intent.requires_payment_method';
    case PaymentIntentRequiresConfirmation = 'payment_intent.requires_confirmation';
    case SubscriptionCreated = 'subscription.created';
    case SubscriptionUpdated = 'subscription.updated';
    case SubscriptionCanceled = 'subscription.canceled';
    case SubscriptionPaused = 'subscription.paused';
    case SubscriptionResumed = 'subscription.resumed';
    case SubscriptionCollectionMethodUpdated = 'subscription.collection_method.updated';
    case SubscriptionPayerWalletVerified = 'subscription.payer_wallet.verified';
    case SubscriptionPaymentLinkCreated = 'subscription.payment_link.created';
    case SubscriptionPaymentLinkPaid = 'subscription.payment_link.paid';
    case SubscriptionPaymentLinkPaymentFailed = 'subscription.payment_link.payment_failed';
    case SubscriptionAutoChargeAllowanceInsufficient = 'subscription.auto_charge.allowance_insufficient';
    case SubscriptionAutoChargeCanceledInsufficientAllowance = 'subscription.auto_charge.canceled_insufficient_allowance';
    case SubscriptionAutoChargeCanceledInsufficientBalance = 'subscription.auto_charge.canceled_insufficient_balance';

    /**
     * @deprecated The API never sends this event. OrcaRail emails the payer about 3 days before
     *             `trial_end`; there is no webhook. Kept so existing handlers keep working.
     */
    case SubscriptionTrialWillEnd = 'subscription.trial_will_end';

    /**
     * @deprecated The API never sends this event. A failed auto-charge sends
     *             `subscription.payment_link.payment_failed` (payload `status: "past_due"`) or
     *             `subscription.auto_charge.allowance_insufficient`. Kept so existing handlers keep working.
     */
    case SubscriptionPastDue = 'subscription.past_due';

    /**
     * @deprecated The API never sends this event; there is no webhook when the last cycle is billed.
     *             Kept so existing handlers keep working.
     */
    case SubscriptionCompleted = 'subscription.completed';
}
