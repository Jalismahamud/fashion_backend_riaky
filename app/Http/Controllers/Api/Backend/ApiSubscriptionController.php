<?php

namespace App\Http\Controllers\Api\Backend;

use Exception;
use App\Models\Plan;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class ApiSubscriptionController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function index()
    {
        try {

            $interval = request()->query('interval');
            $query = Plan::query();
            if ($interval) {
                $query->where('interval', $interval);
            }
            $plans = $query->get();

            if ($plans->isEmpty()) {
                return $this->error([], 'Subscription plan not found', 200);
            }

            foreach ($plans as $plan) {
                $plan->features = json_decode($plan->features);
            }

            return $this->success($plans, 'Plans retrieved successfully', 200);
        } catch (Exception $e) {

            Log::info($e->getMessage());
            return $this->error([], 'Error retrieving plans: ' . $e->getMessage(), 500);
        }
    }


    public function purchaseSubscription(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'coupon_code' => 'nullable|string|exists:coupons,code',
        ]);

        $user = auth('api')->user();
        $plan = Plan::find($validated['plan_id']);

        if (!$plan) {
            return $this->error([], 'Plan not found', 404);
        }

        // Coupon logic
        $coupon = null;
        if (!empty($validated['coupon_code'])) {
            $coupon = \App\Models\Coupon::where('code', $validated['coupon_code'])->first();
            if (!$coupon || !$coupon->isValid() || !$coupon->canBeUsedBy($user->id)) {
                return $this->error([], 'Invalid or expired coupon', 400);
            }
        }

        try {
            if ($plan->price == 0) {
                return $this->handleFreeSubscription($user, $plan);
            }

            $user->createOrGetStripeCustomer();

            $checkoutSessionData = [
                'payment_method_types' => ['card'],
                'mode' => 'subscription',
                'customer' => $user->stripe_id,
                'line_items' => [[
                    'price' => $plan->stripe_price_id,
                    'quantity' => 1,
                ]],
                'success_url' => 'https://aichique.com/payment/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => 'https://aichique.com/payment/cancel?session_id={CHECKOUT_SESSION_ID}',
                'client_reference_id' => $user->id,
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'coupon_code' => $coupon ? $coupon->code : null,
                ],
                'subscription_data' => [
                    'metadata' => [
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'plan_name' => $plan->name,
                        'coupon_code' => $coupon ? $coupon->code : null,
                    ],
                ],
                'allow_promotion_codes' => true,
            ];


            if ($coupon) {
                $checkoutSessionData['discounts'] = [
                    ['coupon' => $coupon->stripe_coupon_id]
                ];
            }

            $checkoutSession = Session::create($checkoutSessionData);

            return $this->success([
                'checkout_url' => $checkoutSession->url,
                'session_id'   => $checkoutSession->id,
            ], 'Checkout session created', 200);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            return $this->error([], 'Error creating subscription: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Handle free subscription creation locally
     */
    private function handleFreeSubscription($user, $plan)
    {
        try {

            $existingSubscription = $user->subscriptions()
                ->where('stripe_status', 'active')
                ->first();

            if ($existingSubscription) {

                if (str_starts_with($existingSubscription->stripe_id, 'free_')) {

                    $existingSubscription->update([
                        'stripe_price' => $plan->stripe_price_id,
                        'updated_at' => now(),
                    ]);


                    $existingSubscription->items()->update([
                        'stripe_product' => $plan->stripe_product_id,
                        'stripe_price' => $plan->stripe_price_id,
                        'updated_at' => now(),
                    ]);

                    return $this->success([
                        'message' => 'Free subscription updated successfully',
                        'subscription_status' => 'active',
                        'plan_name' => $plan->name,
                    ], 'Free subscription updated', 200);
                } else {

                    $existingSubscription->cancel();
                }
            }


            $subscription = $user->subscriptions()->create([
                'type' => 'default',
                'stripe_id' => 'free_' . $user->id . '_' . time(),
                'stripe_status' => 'active',
                'stripe_price' => $plan->stripe_price_id,
                'quantity' => 1,
                'trial_ends_at' => null,
                'ends_at' => null,
            ]);


            $subscription->items()->create([
                'stripe_id' => 'free_item_' . $subscription->id . '_' . time(),
                'stripe_product' => $plan->stripe_product_id,
                'stripe_price' => $plan->stripe_price_id,
                'quantity' => 1,
            ]);


            return $this->success([
                'message' => 'Free subscription activated successfully',
                'subscription_status' => 'active',
                'plan_name' => $plan->name,
            ], 'Free subscription created', 201);
        } catch (Exception $e) {

            Log::info($e->getMessage());
            return $this->error([], 'Error creating free subscription: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check if subscription is free (local)
     */
    private function isFreeSubscription($subscription)
    {
        return str_starts_with($subscription->stripe_id, 'free_');
    }



    public function cancelSubscription()
    {
        $user = auth('api')->user();
        $subscription = $user->subscriptions()
            ->where('stripe_status', 'active')
            ->first();

        if (!$subscription) {
            return $this->error([], 'No active subscription found', 404);
        }

        try {

            if ($this->isFreeSubscription($subscription)) {

                $subscription->update([
                    'stripe_status' => 'canceled',
                    'ends_at' => now(),
                ]);


                $subscription->items()->delete();

                return $this->success([
                    'subscription_id' => $subscription->stripe_id,
                    'status' => 'canceled',
                    'ends_at' => $subscription->ends_at,
                ], 'Free subscription canceled successfully', 200);
            } else {

                if (!$subscription->active() && !$subscription->onTrial()) {
                    return $this->error([], 'The subscription is not active and cannot be canceled.', 400);
                }

                $subscription->cancel();

                return $this->success([
                    'subscription_id' => $subscription->stripe_id,
                    'status' => 'canceled',
                    'ends_at' => $subscription->ends_at,
                ], 'Subscription canceled successfully', 200);
            }
        } catch (Exception $e) {

            Log::info($e->getMessage());
            return $this->error([], 'Error canceling subscription: ' . $e->getMessage(), 500);
        }
    }
    public function status()
    {
        $user = auth('api')->user();

        try {

            $subscription = $user->subscriptions()->latest()->first();

            if (!$subscription) {
                return $this->success([
                    'status' => 'inactive',
                    'subscribed' => false,
                ], 'No subscription found', 200);
            }


            $plan = null;
            if ($subscription->items->isNotEmpty()) {
                $stripePrice = $subscription->items->first()->stripe_price;
                $plan = Plan::where('stripe_price_id', $stripePrice)->first();
            }

            return $this->success([
                'type' => $subscription->type,
                'status' => $subscription->stripe_status,
                'subscribed' => $subscription->active(),
                'ends_at' => $subscription->ends_at,
                'canceled_at' => $subscription->canceled() ? $subscription->ends_at : null,
                'on_grace_period' => $subscription->onGracePeriod(),
                'checkout_url' => $subscription->checkout_url,
                'plan' => $plan ? [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => $plan->price,
                    'features' => json_decode($plan->features),
                ] : null,
            ], 'Subscription status retrieved', 200);
        } catch (Exception $e) {

            Log::info($e->getMessage());
            return $this->error([], 'Error retrieving subscription status: ' . $e->getMessage(), 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {

            Log::info($e->getMessage());
        } catch (\Stripe\Exception\SignatureVerificationException $e) {

            Log::info($e->getMessage());
        }


        $cashierController = new CashierWebhookController();

        try {
            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handleCheckoutSessionCompleted($event);
                    break;

                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                case 'customer.subscription.deleted':
                case 'invoice.payment_succeeded':
                case 'invoice.payment_failed':

                    return $cashierController->handleWebhook($request);

                default:
                    Log::info("Unhandled event type: {$event->type}");
                    break;
            }
        } catch (Exception $e) {

            Log::info($e->getMessage());
        }

        return response('Webhook handled successfully', 200);
    }

    private function handleCheckoutSessionCompleted($event)
    {
        $session = $event->data->object;

        Log::info('Checkout session completed', [
            'session_id' => $session->id,
            'customer_id' => $session->customer,
            'subscription_id' => $session->subscription,
        ]);


        $user = User::where('stripe_id', $session->customer)->first();

        if ($user) {

            $planName = $session->metadata->plan_name ?? 'default';

            $subscription = $user->subscriptions()
                ->where('stripe_id', $session->subscription)
                ->first();

            if ($subscription) {
                $subscription->update([
                    'type' => $planName,
                ]);

                Log::info('Subscription type updated after checkout', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->stripe_id,
                    'plan_name' => $planName,
                ]);
            }
        }
    }

    public function checkoutSuccess(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return $this->error([], 'Session ID is required', 400);
        }

        try {
            $session = Session::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                return $this->success([
                    'session_id' => $sessionId,
                    'status' => 'success',
                    'subscription_id' => $session->subscription,
                ], 'Payment completed successfully', 200);
            } else {
                return $this->error([], 'Payment not completed', 400);
            }
        } catch (Exception $e) {

            Log::info($e->getMessage());
            return $this->error([], 'Error verifying payment: ' . $e->getMessage(), 500);
        }
    }
}
