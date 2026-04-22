<?php

namespace App\Http\Controllers\Web\Backend;

use Exception;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Stripe\Stripe;
use Stripe\Coupon as StripeCoupon;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Coupon::latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group">
                              <a href="#" onclick="showEditModal(' . $data->id . ', \'' . addslashes($data->name) . '\', \'' . addslashes($data->code) . '\', \'' . addslashes($data->type) . '\', ' . $data->amount . ')" class="btn btn-primary text-white" title="Edit">
                              <i class="bi bi-pencil"></i>
                              </a>
                              <a href="#" onclick="showDeleteConfirm(' . $data->id . ')" class="btn btn-danger text-white" title="Delete">
                              <i class="bi bi-trash"></i>
                            </a>
                            </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('backend.layouts.coupon.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:coupons,code',
            'name' => 'required|string',
            'type' => 'required|in:percentage,fixed',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string',
            'max_redemptions' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
        ]);

        Stripe::setApiKey(config('services.stripe.secret'));
        try {
            $stripeCoupon = StripeCoupon::create([
                'name' => $request->name,
                'duration' => 'once',
                'percent_off' => $request->type === 'percentage' ? $request->amount : null,
                'amount_off' => $request->type === 'fixed' ? intval($request->amount * 100) : null,
                'currency' => $request->currency,
                'max_redemptions' => $request->max_redemptions,
                'redeem_by' => $request->expires_at ? strtotime($request->expires_at) : null,
                'metadata' => [
                    'code' => $request->code,
                ],
            ]);
            Coupon::create([
                'code' => $request->code,
                'stripe_coupon_id' => $stripeCoupon->id,
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'max_redemptions' => $request->max_redemptions,
                'expires_at' => $request->expires_at,
                'is_active' => true,
            ]);
            return response()->json(['success' => true, 'message' => 'Coupon created successfully.']);
        } catch (Exception $e) {
            Log::error('Stripe Coupon Create Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'expires_at' => 'nullable|date|after:now',
            'max_redemptions' => 'nullable|integer|min:1',
        ]);
        $coupon = Coupon::findOrFail($id);

        Stripe::setApiKey(config('services.stripe.secret'));
        try {
            StripeCoupon::update($coupon->stripe_coupon_id, [
                'name' => $request->name ?? $coupon->name,
                'metadata' => [
                    'description' => $request->description ?? $coupon->description,
                ],
            ]);
            $coupon->update($request->only(['name', 'description', 'is_active', 'expires_at', 'max_redemptions']));
            return response()->json(['success' => true, 'message' => 'Coupon updated successfully.']);
        } catch (Exception $e) {
            Log::error('Stripe Coupon Update Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        try {

            $stripeCoupon = \Stripe\Coupon::retrieve($coupon->stripe_coupon_id);
            $stripeCoupon->delete();

            $coupon->delete();

            return response()->json(['success' => true, 'message' => 'Coupon deleted successfully.']);
        } catch (Exception $e) {
            Log::error('Stripe Coupon Delete Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
