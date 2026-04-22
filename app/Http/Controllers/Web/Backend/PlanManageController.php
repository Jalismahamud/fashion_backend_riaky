<?php

namespace App\Http\Controllers\Web\Backend;

use Exception;
use Stripe\Price;
use Stripe\Stripe;
use Stripe\Product;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class PlanManageController extends Controller
{
    //stripe api key
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    //show plan list in datatable
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Plan::latest('id');

            return DataTables::of($data)
                ->addIndexColumn()

                // Price column (formatted)
                ->addColumn('price', function ($item) {
                    return '$' . number_format($item->price / 100, 2) . ' / ' . $item->interval;
                })

                ->addColumn('features', function ($item) {
                    $features = json_decode($item->features, true) ?? [];
                    $html = '<button class="btn btn-sm btn-outline-primary toggle-features">Show Features</button>';
                    $html .= '<div class="features-list" style="display:none; margin-top:5px;"><ul style="padding-left:20px;">';
                    foreach ($features as $feature) {
                        $html .= '<li>' . e($feature) . '</li>';
                    }
                    $html .= '</ul></div>';
                    return $html;
                })

                // Action buttons
                ->addColumn('action', function ($item) {
                    return '<div class="btn-group btn-group-sm" role="group">
                      <button type="button" class="btn btn-primary fs-14 text-white editPlan"
                            data-id="' . $item->id . '" title="Edit">
                            <i class="fe fe-edit"></i>
                        </button>
                        <button type="button" onclick="confirmDelete(' . $item->id . ')"
                            class="btn btn-danger fs-14 text-white" title="Delete">
                            <i class="fe fe-trash"></i>
                        </button>
                    </div>';
                })

                ->rawColumns(['features', 'status', 'action'])
                ->make(true);
        }

        return view('backend.layouts.plan.index');
    }


    // Store subscription plan
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name'           => 'required|string|max:255',
                'description'    => 'nullable|string|max:1000',
                'price'          => 'required',
                'interval'       => 'required|string|in:day,week,month,year',
                'interval_count' => 'nullable|integer|min:1',
                'trial_days'     => 'nullable|integer|min:0',
                'currency'       => 'nullable|string|size:3',
            ]);

            // Create Stripe Product
            $stripeProduct = Product::create([
                'name' => $request->name,
            ]);

            // Create Stripe Price
            $stripePrice = Price::create([
                'product' => $stripeProduct->id,
                'unit_amount' => $request->price * 100, // Stripe expects cents
                'currency' => $request->currency ?? 'usd',
                'recurring' => [
                    'interval'       => $request->interval,
                    'interval_count' => $request->interval_count ?? 1,
                ],
            ]);

            // Save in DB
            $plan = Plan::create([
                'name'              => $request->name,
                'description'       => $request->description,
                'stripe_product_id' => $stripeProduct->id,
                'stripe_price_id'   => $stripePrice->id,
                'price'             => $request->price,
                'currency'          => $request->currency ?? 'usd',
                'interval'          => $request->interval,
                'interval_count'    => $request->interval_count ?? 1,
                'trial_days'        => $request->trial_days ?? 0,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'status'  => 1,
                    'message' => 'Plan created successfully',
                    'data'    => $plan
                ]);
            }

            return redirect()->route('admin.subscription.index')
                ->with('success', 'Plan created successfully.');
        } catch (Exception $e) {
            Log::error('Plan creation failed: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Failed to create plan',
                    'error'   => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->withInput()
                ->withErrors('Failed to create plan: ' . $e->getMessage());
        }
    }


    //edit subscription plan
    public function edit($id)
    {
        try {
            $plan = Plan::findOrFail($id);

            return response()->json([
                'status' => 1,
                'message' => 'Plan fetched successfully.',
                'data' => $plan
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to fetch plan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update subscription plan
    public function update(Request $request, $id)
    {
        try {
            $plan = Plan::findOrFail($id);

            $request->validate([
                'name'           => 'required|string|max:255',
                'description'    => 'nullable|string|max:1000',
                'price'          => 'required',
                'interval'       => 'required|string|in:day,week,month,year',
                'interval_count' => 'nullable|integer|min:1',
                'trial_days'     => 'nullable|integer|min:0',
                'currency'       => 'nullable|string|size:3',
            ]);

            // Update Stripe Product if name changed
            if ($plan->name !== $request->name && $plan->stripe_product_id) {
                $stripeProduct = Product::update(
                    $plan->stripe_product_id,
                    ['name' => $request->name]
                );
            }

            // Update Stripe Price
            // Note: Stripe prices are immutable; usually you create a new price if amount changes
            if ($plan->price != $request->price || $plan->interval != $request->interval || $plan->interval_count != ($request->interval_count ?? 1)) {
                $stripePrice = Price::create([
                    'product' => $plan->stripe_product_id,
                    'unit_amount' => $request->price * 100,
                    'currency' => $request->currency ?? $plan->currency,
                    'recurring' => [
                        'interval'       => $request->interval,
                        'interval_count' => $request->interval_count ?? 1,
                    ],
                ]);

                $plan->stripe_price_id = $stripePrice->id;
            }

            // Update DB
            $plan->update([
                'name'           => $request->name,
                'description'    => $request->description,
                'price'          => $request->price,
                'currency'       => $request->currency ?? $plan->currency,
                'interval'       => $request->interval,
                'interval_count' => $request->interval_count ?? 1,
                'trial_days'     => $request->trial_days ?? $plan->trial_days,
                'is_popular'     => $request->is_popular ?? $plan->is_popular,
                'status'         => $plan->status,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'status'  => 1,
                    'message' => 'Plan updated successfully',
                    'data'    => $plan
                ]);
            }

            return redirect()->route('admin.subscription.index')
                ->with('success', 'Plan updated successfully.');
        } catch (Exception $e) {
            Log::error('Plan update failed: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Failed to update plan',
                    'error'   => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->withInput()
                ->withErrors('Failed to update plan: ' . $e->getMessage());
        }
    }

    //delete subscription plan
    public function destroy($id, Request $request)
    {
        try {
            $plan = Plan::find($id);

            if (!$plan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Plan not found'
                ]);
            }

            // Deactivate product in Stripe (archive instead of delete)
            if ($plan->stripe_product_id) {
                Product::update($plan->stripe_product_id, ['active' => false]);
            }

            $plan->delete();
            return response()->json([
                'status'  => true,
                'message' => 'Plan deleted successfully and archived on Stripe.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 0,
                'message' => 'Failed to delete plan.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
