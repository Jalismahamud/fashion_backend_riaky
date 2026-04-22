<?php

namespace App\Http\Controllers\Api\Auth;

use Exception;
use App\Models\Plan;
use App\Helper\Helper;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    use ApiResponse;

    public function profile()
    {
        try {

            $user = auth('api')->user();

            if (!$user) {
                return $this->error([], 'User not found.', 404);
            }

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => $user->role,
                'gender' => $user->gender ?? null,
                'address' => $user->address,
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
            ];

            $styleProfile = \App\Helper\Helper::getStyleProfile($user->id);

            $questions = \App\Models\StyleQuizQuestion::with('options')->get();

            $answers = \App\Models\StyleQuizAnswer::where('user_id', $user->id)->with(['question', 'option'])->get();

            $quiz_set = $questions->map(function ($question) use ($answers) {
                $answer = $answers->firstWhere('question_id', $question->id);

                return [
                    'question' => $question->question_text,
                    'answer'   => $answer && $answer->option ? $answer->option->option_text : null,
                ];
            });


            $subscription = $user->subscriptions()->latest()->first();

            $plan = null;
            if ($subscription && $subscription->stripe_price) {
                $plan = Plan::where('stripe_price_id', $subscription->stripe_price)->first();
            }


            $subscriptionData = [
                'type' => $subscription?->type,
                'status' => $subscription?->stripe_status,
                'subscribed' => $subscription?->active() ?? false,
                'ends_at' => $subscription?->ends_at,
                'canceled_at' => $subscription && $subscription->canceled()? $subscription->ends_at : null,
                'on_grace_period' => $subscription?->onGracePeriod(),
                'checkout_url' => $subscription?->checkout_url,
                'plan' => $plan ? [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => $plan->price,
                    'currency' => $plan->currency,
                    'interval' => $plan->interval,
                    'interval_count' => $plan->interval_count,
                    'trial_days' => $plan->trial_days,
                    'features' => $plan->features ? json_decode($plan->features) : [],
                ] : null,
            ];

            $planName = $plan ? $plan->name : 'Null';
            $accessibility = $this->getAccessibilityByPlan($planName);

            $response = [
                'user' => $userData,
                'style_profile' => array_merge($styleProfile, ['quiz_set' => $quiz_set]),
                'is_style_profile' => $user->getIsStyleProfileAttribute(),
                'subscription' => $subscriptionData,
                'accessibility' => $accessibility,
            ];

            return $this->success($response, 'User profile and style profile retrieved successfully', 200);
        } catch (Exception $e) {

            Log::error('Quiz Profile Error: ' . $e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }


    public function updateProfile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['nullable', 'string', 'max:255'],
                'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:5120'],
                'gender' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:255'],
                'latitude' => ['nullable', 'string', 'max:255'],
                'longitude' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                return $this->error([], $validator->errors()->first(), 422);
            }

            $user = auth('api')->user();

            $data = $validator->validated();

            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    Helper::deleteAvatar($user->avatar);
                }
                $avatarPath = Helper::uploadImage($request->file('avatar'), 'profile');
                $data['avatar'] = $avatarPath;
            }

            $user->update($data);

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => url($user->avatar) ?? null,
                'gender' => $user->gender ?? null,
                'address' => $user->address,
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
            ];

            return $this->success($userData, 'Profile updated successfully.', 200);
        } catch (Exception $e) {
            Log::error('Profile Update Error: ' . $e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }


    public function updateAvatar(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:5120'],
            ]);

            if ($validator->fails()) {
                return $this->error([], $validator->errors()->first(), 422);
            }

            $user = auth('api')->user();

            if ($user->avatar) {
                Helper::deleteAvatar($user->avatar);
            }

            $avatarPath = Helper::uploadImage($request->file('avatar'), 'profile');

            $user->update(['avatar' => $avatarPath]);

            return $this->success(['avatar' => url($avatarPath)], 'Avatar updated successfully.', 200);
        } catch (Exception $e) {
            Log::error('Avatar Update Error: ' . $e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }


    public function updatePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => ['required', 'string', 'min:8'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            if ($validator->fails()) {
                return $this->error([], $validator->errors()->first(), 200);
            }

            $user = auth('api')->user();

            if (!Hash::check($request->current_password, $user->password)) {
                return $this->error([], 'current password is incorrect.', 200);
            }

            $user->update(['password' => Hash::make($request->password)]);

            return $this->success(['Password updated successfully'], 'Password updated successfully.', 200);
        } catch (Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }


    public function deleteProfile()
    {
        try {
            $user = auth('api')->user();

            if ($user->avatar) {
                Helper::deleteAvatar($user->avatar);
            }

            $user->delete();

            return $this->success([], 'Profile deleted successfully.', 200);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }

    private function getAccessibilityByPlan($planName)
    {
        $access = [
            'Null' => [
                'picture_uploads_per_day' => 0,
                'outfit_recommendations_per_day' => 0,
                'chat_with_ai_stylist' => 'null',
                'closet_access' => 0,
                'ai_inspiration' => false,
                'priority_response' => false,
            ],
            'Free' => [
                'picture_uploads_per_day' => 3,
                'outfit_recommendations_per_day' => 4,
                'chat_with_ai_stylist' => 'limited',
                'closet_access' => 1,
                'ai_inspiration' => false,
                'priority_response' => false,
            ],
            'Regular' => [
                'picture_uploads_per_day' => 15,
                'outfit_recommendations_per_day' => 20,
                'chat_with_ai_stylist' => 'unlimited',
                'closet_access' => 10,
                'ai_inspiration' => false,
                'priority_response' => false,
            ],
            'Pro' => [
                'picture_uploads_per_day' => 50,
                'outfit_recommendations_per_day' => 40,
                'chat_with_ai_stylist' => 'unlimited',
                'closet_access' => 'unlimited',
                'ai_inspiration' => true,
                'priority_response' => true,
            ],
            'VIP' => [
                'picture_uploads_per_day' => 'unlimited',
                'outfit_recommendations_per_day' => 'unlimited',
                'chat_with_ai_stylist' => 'unlimited',
                'closet_access' => 'unlimited',
                'ai_inspiration' => true,
                'priority_response' => true,
            ],
        ];

        return $access[$planName] ?? $access['Free'];
    }
}
