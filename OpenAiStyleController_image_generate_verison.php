<?php

namespace App\Http\Controllers\Api\Backend;

use App\Models\Item;
use App\Models\ApiHit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\OpenAiStyleService;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Auth\UserProfileController;

class OpenAiStyleControllerImageGenerateVersion extends Controller
{
    use ApiResponse;

    protected $imageStyleService;

    public function __construct(OpenAiStyleService $imageStyleService)
    {
        $this->imageStyleService = $imageStyleService;
    }

    /**
     * Generate AI outfit collection image for wardrobe item
     */
    public function styleItem(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'item_id' => 'required|integer|exists:items,id',
            ]);

            if ($validator->fails()) {
                return $this->error([], $validator->errors()->first(), 422);
            }

            $itemId = $request->input('item_id');
            $userId = auth('api')->id();


            $userItem = Item::where('id', $itemId)
                ->where('user_id', $userId)
                ->with(['category', 'user'])
                ->first();

            if (!$userItem) {
                return $this->error([], 'This item does not belong to your wardrobe.', 403);
            }

            // Check if item has image
            if (!$userItem->image) {
                return $this->error([], 'Item must have an image to generate outfit collection.', 400);
            }

            // Get user gender
            $userGender = $userItem->user->gender ?? 'unisex';
            $userGender = strtolower($userGender);

            // Validate gender value
            if (!in_array($userGender, ['male', 'female', 'unisex'])) {
                $userGender = 'unisex';
            }

            // Get user preferences
            $profileResponse = app(UserProfileController::class)->profile($request)->getData(true);
            $profileData = $profileResponse['data'] ?? [];
            $preferences = $profileData['style_profile'] ?? [
                'type' => 'Mixed Style',
                'details' => '',
                'keywords' => ''
            ];

            Log::info('Processing outfit generation', [
                'user_id' => $userId,
                'item_id' => $itemId,
                'item_type' => $userItem->clouth_type,
                'gender' => $userGender,
                'style_type' => $preferences['type'] ?? 'Unknown'
            ]);

            // Get website names
            $webSiteNames = \App\Models\WebSiteName::pluck('name')->toArray();
            if (empty($webSiteNames)) {
                $webSiteNames = ['https://www.daraz.com.bd'];
            }

            // Generate outfit collection
            $result = $this->imageStyleService->getItemStyleSuggestions(
                $userItem,
                $preferences,
                $webSiteNames,
                $userGender
            );

            if (isset($result['error'])) {
                DB::rollBack();
                return $this->error([], $result['error'], 400);
            }

            // Record API hit
            ApiHit::create([
                'user_id' => $userId,
                'success' => true,
            ]);

            DB::commit();

            Log::info('Outfit collection generated', [
                'user_id' => $userId,
                'item_id' => $itemId,
                'has_generated_image' => !empty($result['generated_outfit_image'])
            ]);

            return $this->success($result, 'Outfit collection image generated successfully.', 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Style Item Controller Error', [
                'user_id' => auth('api')->id(),
                'item_id' => $request->input('item_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error([], 'Something went wrong while generating outfit collection.', 500);
        }
    }
}
