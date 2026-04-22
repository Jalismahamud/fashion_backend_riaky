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

class OpenAiStyleController extends Controller
{
    use ApiResponse;

    protected $imageStyleService;

    public function __construct(OpenAiStyleService $imageStyleService)
    {
        $this->imageStyleService = $imageStyleService;
    }

    /**
     * Generate outfit suggestions from user's existing wardrobe
     * Accepts image_path instead of file upload
     */
    public function styleItem(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'item_id' => 'required|integer|exists:items,id',
                'image_path' => 'nullable|string', // Optional: can override item's existing image
            ]);

            if ($validator->fails()) {
                return $this->error([], $validator->errors()->first(), 422);
            }

            $itemId = $request->input('item_id');
            $imagePath = $request->input('image_path'); // Get image_path from request
            $userId = auth('api')->id();

            $userItem = Item::where('id', $itemId)
                ->where('user_id', $userId)
                ->with(['category', 'user'])
                ->first();

            if (!$userItem) {
                return $this->error([], 'This item does not belong to your wardrobe.', 403);
            }

            // Use provided image_path or fall back to item's existing image
            $finalImagePath = $imagePath ?? $userItem->image;

            // Check if image path exists
            if (!$finalImagePath) {
                return $this->error([], 'Item must have an image path to generate outfit suggestions.', 400);
            }

            // Validate that the image file exists on server
            $imageExists = $this->validateImagePath($finalImagePath);
            if (!$imageExists) {
                return $this->error([], 'The provided image path does not exist on the server.', 404);
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

            Log::info('Processing outfit suggestions from wardrobe', [
                'user_id' => $userId,
                'item_id' => $itemId,
                'item_type' => $userItem->clouth_type,
                'image_path' => $finalImagePath,
                'gender' => $userGender,
                'style_type' => $preferences['type'] ?? 'Unknown'
            ]);

            // Get website names
            $webSiteNames = \App\Models\WebSiteName::pluck('name')->toArray();
            if (empty($webSiteNames)) {
                $webSiteNames = ['https://www.daraz.com.bd'];
            }

            // Get matching items from user's wardrobe
            // Pass the image_path to the service
            $result = $this->imageStyleService->getItemStyleSuggestions(
                $userItem,
                $preferences,
                $webSiteNames,
                $userGender,
                $finalImagePath // Pass image_path to service
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

            Log::info('Outfit suggestions generated from wardrobe', [
                'user_id' => $userId,
                'item_id' => $itemId,
                'matched_items_count' => count($result['generated_outfit_image'] ?? [])
            ]);

            return $this->success($result, 'Outfit suggestions generated successfully from your wardrobe.', 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Style Item Controller Error', [
                'user_id' => auth('api')->id(),
                'item_id' => $request->input('item_id'),
                'image_path' => $request->input('image_path'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error([], 'Something went wrong while generating outfit suggestions.', 500);
        }
    }

    /**
     * Validate that the image path exists on the server
     */
    private function validateImagePath($imagePath)
    {
        if (!$imagePath) {
            return false;
        }

        // If it's a full URL, extract the path
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            $imagePath = parse_url($imagePath, PHP_URL_PATH);
        }

        // Clean the path
        $imagePath = ltrim($imagePath, '/');

        // Check if file exists in public directory
        $fullPath = public_path($imagePath);

        if (file_exists($fullPath) && is_file($fullPath)) {
            return true;
        }

        Log::warning('Image path validation failed', [
            'provided_path' => $imagePath,
            'full_path' => $fullPath,
            'exists' => file_exists($fullPath)
        ]);

        return false;
    }
}
