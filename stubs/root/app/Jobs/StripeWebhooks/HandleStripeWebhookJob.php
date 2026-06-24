<?php

namespace App\Jobs\StripeWebhooks;

use App\Services\StripeService;
use Exception;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class HandleStripeWebhookJob extends ProcessWebhookJob
{
    /**
     * The handle method will be called when the job is dispatched.
     */
    public function handle(StripeService $stripeService)
    {
        $payload = $this->webhookCall->payload;
        $type = $payload['type'] ?? 'unknown';
        $object = $payload['data']['object'] ?? [];

        Log::info("Audit Job: Processing Stripe Webhook [{$type}]", [
            'webhook_call_id' => $this->webhookCall->id,
            'event_id' => $payload['id'] ?? 'N/A',
        ]);

        try {
            switch ($type) {
                case 'checkout.session.completed':
                    $stripeService->fulfillCheckout($object['id']);
                    break;

                case 'charge.refunded':
                    $stripeService->handleRefund($object);
                    break;

                case 'customer.subscription.created':
                    $stripeService->handleSubscriptionCreated($object);
                    break;

                case 'customer.subscription.deleted':
                    $stripeService->handleSubscriptionDeleted($object);
                    break;

                case 'customer.subscription.updated':
                    $stripeService->handleSubscriptionUpdated($object);
                    break;

                case 'invoice.payment_succeeded':
                    $stripeService->handleInvoicePaid($object);
                    break;

                default:
                    Log::debug("Audit Job: Unhandled event type [{$type}]");
                    break;
            }
        } catch (Exception $e) {
            Log::error('Audit Job Error: '.$e->getMessage(), [
                'type' => $type,
                'payload' => $payload,
            ]);
            throw $e;
        }
    }
}
