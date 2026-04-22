<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\Price;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $plans = [
            [
                'name'           => 'Free',
                'description'    => 'Free plan with limited features',
                'price'          => 0,
                'currency'       => 'usd',
                'interval'       => 'month',
                'interval_count' => 1,
                'trial_days'     => 0,
                'features'       => [
                    '3 picture uploads per day',
                    '4 outfit/shopping recommendations per day',
                    'Limited chat with AI stylist',
                    'Sample Closet Access (1 saved outfit)',
                ],
            ],
            [
                'name'           => 'Regular',
                'description'    => 'Best for casual users',
                'price'          => 9.99,
                'currency'       => 'usd',
                'interval'       => 'month',
                'interval_count' => 1,
                'trial_days'     => 0,
                'features'       => [
                    '15 picture uploads per day',
                    '20 outfit/shopping recommendations per day',
                    'Unlimited chat with AI stylist',
                    'Limited Closet Access (up to 10 outfits monthly)',
                ],
            ],
            [
                'name'           => 'Pro',
                'description'    => 'Most Popular: Advanced plan with full closet access',
                'price'          => 24.99,
                'currency'       => 'usd',
                'interval'       => 'month',
                'interval_count' => 1,
                'trial_days'     => 0,
                'features'       => [
                    '50 picture uploads per day',
                    '40 outfit/shopping recommendations per day',
                    'Unlimited chat with AI stylist',
                    'Full Closet Access (unlimited outfit saves)',
                    'Daily AI outfit inspiration',
                    'Priority AI response speed',
                ],
            ],
            [
                'name'           => 'VIP',
                'description'    => 'Premium experience with everything unlocked',
                'price'          => 49.99,
                'currency'       => 'usd',
                'interval'       => 'month',
                'interval_count' => 1,
                'trial_days'     => 0,
                'features'       => [
                    'Unlimited picture uploads',
                    'Unlimited outfit/shopping recommendations',
                    'Unlimited chat with AI stylist',
                    'Full Closet Access',
                    'Early access to new features',
                ],
            ],
        ];

        foreach ($plans as $planData) {

            $stripeProduct = Product::create([
                'name' => $planData['name'],
            ]);

            $stripePrice = Price::create([
                'product'        => $stripeProduct->id,
                'unit_amount'    => (int)($planData['price'] * 100), 
                'currency'       => $planData['currency'] ?? 'usd',
                'recurring'      => [
                    'interval'       => $planData['interval'],
                    'interval_count' => $planData['interval_count'] ?? 1,
                ],
            ]);

            Plan::create([
                'name'              => $planData['name'],
                'description'       => $planData['description'],
                'stripe_product_id' => $stripeProduct->id,
                'stripe_price_id'   => $stripePrice->id,
                'price'             => $planData['price'],
                'currency'          => $planData['currency'] ?? 'usd',
                'interval'          => $planData['interval'],
                'interval_count'    => $planData['interval_count'] ?? 1,
                'trial_days'        => $planData['trial_days'] ?? 0,
                'features'          => json_encode($planData['features']),
            ]);
        }
    }
}
