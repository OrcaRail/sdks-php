<?php

declare(strict_types=1);

namespace OrcaRail\Enum;

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
    case SubscriptionTrialWillEnd = 'subscription.trial_will_end';
    case SubscriptionPaymentLinkCreated = 'subscription.payment_link.created';
    case SubscriptionPaymentLinkPaid = 'subscription.payment_link.paid';
    case SubscriptionPaymentLinkPaymentFailed = 'subscription.payment_link.payment_failed';
    case SubscriptionPastDue = 'subscription.past_due';
    case SubscriptionCompleted = 'subscription.completed';
}
