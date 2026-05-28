<?php

namespace App\Http\Controllers;

use App\Services\PaymentFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentFulfillmentService $paymentFulfillmentService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');
        $secret = config('services.stripe.webhook_secret');

        if ($secret === null || $secret === '') {
            return response('', Response::HTTP_BAD_REQUEST);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response('', Response::HTTP_BAD_REQUEST);
        }

        $this->paymentFulfillmentService->fulfillFromStripeEvent(
            $event->toArray(),
            $event->id,
        );

        return response('', Response::HTTP_OK);
    }
}
