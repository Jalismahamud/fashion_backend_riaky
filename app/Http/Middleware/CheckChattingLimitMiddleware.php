<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class CheckChattingLimitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'code' => 401], 401);
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
            'free' => 16,
            'regular' => PHP_INT_MAX,
            'pro' => PHP_INT_MAX,
            'vip' => PHP_INT_MAX
        ];

        $chatLimit = $planLimits[$plan] ?? 0;

        $todayChats = $user->chats()
            ->whereDate('created_at', Carbon::today())
            ->count();

        if ($todayChats >= $chatLimit) {
            return response()->json([
                'status' => false,
                'message' => "You have reached your daily chat limit of {$chatLimit} for the {$plan} plan.",
                'code' => 403
            ], 403);
        }

        return $next($request);
    }
}
