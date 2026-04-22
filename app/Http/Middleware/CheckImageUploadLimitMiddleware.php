<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Plan;

class CheckImageUploadLimitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['status'  => false,'message' => 'Unauthorized','code'    => 401], 401);
        }

        $plan = 'free';
        $subscription = $user->subscription('default');

        if ($subscription && $subscription->active()) {
            $planModel = Plan::where('stripe_price_id', $subscription->stripe_price)->first();
            if ($planModel) {
                $plan = strtolower($planModel->name);
            }
        }

        $planLimits = [
            'free'    => 3,
            'regular' => 15,
            'pro'     => 50,
            'vip'     => PHP_INT_MAX,
        ];

        $chatImageLimit = $planLimits[$plan] ?? 0;

        $chatImagesToday = DB::table('chat_histories')
            ->where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->whereNotNull('image_path')
            ->count();

        if ($chatImagesToday >= $chatImageLimit) {
            return response()->json([
                'status'  => false,
                'message' => "Chat image upload limit reached for your {$plan} plan (Max: {$chatImageLimit} per day).",
                'code'    => 403
            ], 403);
        }

        return $next($request);
    }
}
