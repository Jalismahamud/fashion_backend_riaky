<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use App\Models\Item;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckItemUploadLimitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['status'  => false, 'message' => 'Unauthorized', 'code'    => 401], 401);
        }

        if (!in_array('Laravel\\Cashier\\Billable', class_uses($user))) {
            $user = User::find($user->id);
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
            'free'    => 1,
            'regular' => 10,
            'pro'     => PHP_INT_MAX,
            'vip'     => PHP_INT_MAX,
        ];

        $limit = $planLimits[$plan] ?? 0;

        $monthlyUploads = Item::where('user_id', $user->id)
            ->whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        if ($monthlyUploads >= $limit) {
            return response()->json([
                'status'  => false,
                'message' => "Upload limit reached for your {$plan} plan (Max: {$limit} per day).",
                'code'    => 403
            ], 403);
        }

        return $next($request);
    }
}
