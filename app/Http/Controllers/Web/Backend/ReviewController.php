<?php

namespace App\Http\Controllers\Web\Backend;

use Exception;
use App\Models\Review;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Review::with('user')->where('status', 'pending')->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('user_name', fn($data) => $data->user ? $data->user->name : 'N/A')
                ->addColumn('user_email', fn($data) => $data->user ? $data->user->email : 'N/A')
                ->addColumn('rating', fn($data) => $data->rating)
                ->addColumn('review_text', fn($data) => $data->review_text)
                ->addColumn('status', function ($data) {
                    $backgroundColor = $data->status == "approved" ? '#4CAF50' : '#ccc';
                    $sliderTranslateX = $data->status == "approved" ? '26px' : '2px';
                    $sliderStyles = "position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background-color: green; border-radius: 50%; transition: transform 0.3s ease; transform: translateX($sliderTranslateX);";
                    $status = '<div class="form-check form-switch" style="margin-left:40px; position: relative; width: 50px; height: 24px; background-color: ' . $backgroundColor . '; border-radius: 12px; transition: background-color 0.3s ease; cursor: pointer;">';
                    $status .= '<input onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" class="form-check-input" id="customSwitch' . $data->id . '" getAreaid="' . $data->id . '" name="status" style="position: absolute; width: 100%; height: 100%; opacity: 0; z-index: 2; cursor: pointer;">';
                    $status .= '<span style="' . $sliderStyles . '"></span>';
                    $status .= '<label for="customSwitch' . $data->id . '" class="form-check-label" style="margin-left: 10px;"></label>';
                    $status .= '</div>';
                    return $status;
                })
                ->addColumn('cancel_status', function ($data) {
                    $backgroundColor = $data->status == "approved" ? '#4CAF50' : '#ccc';
                    $sliderTranslateX = $data->status == "approved" ? '26px' : '2px';
                    $sliderStyles = "position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background-color: red; border-radius: 50%; transition: transform 0.3s ease; transform: translateX($sliderTranslateX);";
                    $status = '<div class="form-check form-switch" style="margin-left:40px; position: relative; width: 50px; height: 24px; background-color: ' . $backgroundColor . '; border-radius: 12px; transition: background-color 0.3s ease; cursor: pointer;">';
                    $status .= '<input onclick="showCancelRequestAlert(' . $data->id . ')" type="checkbox" class="form-check-input" id="customSwitch' . $data->id . '" getAreaid="' . $data->id . '" name="status" style="position: absolute; width: 100%; height: 100%; opacity: 0; z-index: 2; cursor: pointer;">';
                    $status .= '<span style="' . $sliderStyles . '"></span>';
                    $status .= '<label for="customSwitch' . $data->id . '" class="form-check-label" style="margin-left: 10px;"></label>';
                    $status .= '</div>';
                    return $status;
                })
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
                                <!-- Edit Button -->
                                <a href="' . route('admin.review.show', ['id' => Crypt::encryptString($data->id)]) . '" class="btn btn-primary fs-14 text-white view-icn" title="View">
                       <i class="fa fa-eye"></i>
            </a>

                            </div>';
                })
                ->rawColumns(['status', 'cancel_status' , 'action'])
                ->make(true);
        }
        return view('backend.layouts.review.index');
    }

    public function approveReview($id)
    {
        try {
            $review = Review::findOrFail($id);
            $review->status = 'approved';
            $review->save();

            return response()->json([
                'success' => true,
                'message' => 'Review Approved Successfully.',
                'data' => $review,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function cancelledReview($id)
    {
        try {
            $review = Review::findOrFail($id);
            $review->status = 'rejected';
            $review->save();

            return response()->json([
                'success' => true,
                'message' => 'Review Rejected Successfully.',
                'data' => $review,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function show(Request $request)
    {
        try {

            $id = Crypt::decryptString($request->id);
            $review = Review::with('user')->where('status', 'pending')->findOrFail($id);


            if (!$review) {
                return redirect()->back()->with('t-error', 'Review not found.');
            }

            return view('backend.layouts.review.view', compact('review'));
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', $e->getMessage());
        }
    }
}
