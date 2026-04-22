<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class StripeWebhookController extends Controller
{
    /**
     * Handle invoice payment succeeded.
     */
    public function handleInvoicePaymentSucceeded($payload)
    {
        $stripeCustomerId = $payload['data']['object']['customer'];
        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if ($user) {
            // Example: mark subscription active
            $subscription = $user->subscriptions()->latest()->first();
            if ($subscription) {
                $subscription->stripe_status = 'active';
                $subscription->save();
            }
        }

        return response()->json(['received' => true]);
    }
}
