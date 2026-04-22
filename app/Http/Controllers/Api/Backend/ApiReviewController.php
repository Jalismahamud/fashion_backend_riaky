<?php

namespace App\Http\Controllers\Api\Backend;

use Exception;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ApiReviewController extends Controller
{
    use ApiResponse;

    public function getReviews(Request $request)
    {
        try {
            $reviews = Review::with('user')->where('status', 'approved')->orderBy('created_at', 'desc')->paginate(6);
            $reviewData = $reviews->getCollection()->map(function ($review) {
                return [
                    'id' => $review->id,
                    'user_name' => $review->user->name ?? 'Anonymous',
                    'user_avatar' => $review->user->avatar ?? null,
                    'rating' => $review->rating,
                    'review_text' => $review->review_text,
                    'created_at' => $review->created_at->diffForHumans(),
                ];
            })->toArray();

            $paginate = [
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
            ];

            $response = [
                'paginate' => $paginate,
                'reviews' => $reviewData,
            ];

            return $this->success($response, 'Reviews retrieved successfully.', 200);
        } catch (Exception $e) {
            Log::error('Error fetching reviews: ' . $e->getMessage());
            return $this->error([], 'Failed to fetch reviews', 500);
        }
    }

    public function getHomePageReviews(Request $request)
    {
        try {
            $reviews = Review::with('user')
                ->where('status', 'approved')
                ->latest()
                ->take(20)
                ->get();

            $reviewData = $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'user_name' => $review->user->name ?? 'Anonymous',
                    'user_avatar' => $review->user->avatar ?? null,
                    'rating' => $review->rating,
                    'review_text' => $review->review_text,
                    'created_at' => $review->created_at->diffForHumans(),
                ];
            })->toArray();

            return $this->success($reviewData, 'Reviews retrieved successfully.', 200);
        } catch (Exception $e) {
            Log::error('Error fetching reviews: ' . $e->getMessage());
            return $this->error([], 'Failed to fetch reviews', 500);
        }
    }


    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'review_text' => 'nullable|string|max:2000',
            ]);

            if ($validator->fails()) {
                return $this->error([], $validator->errors()->first(), 422);
            }
            if (!Auth::check()) {
                return $this->success([], 'You have to be logged in first', 200);
            }

            $review = Review::create([
                'user_id' => Auth::id(),
                'rating' => $request->rating,
                'review_text' => $request->review_text,
            ]);

            return $this->success($review, 'Review created successfully.', 201);
        } catch (Exception $e) {

            Log::error($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {

            $review = Review::find($id);

            if (!$review) {
                return $this->error([], 'Review not found', 404);
            }

            if ($review->user_id !== Auth::id()) {
                return $this->error([], 'You are not authorized to delete this review', 403);
            }

            $review->delete();

            return $this->success([], 'Review deleted successfully.', 200);
        } catch (Exception $e) {

            Log::error($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }
}
