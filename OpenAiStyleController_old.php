<?php

// namespace App\Http\Controllers\Api\Backend;

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

class OpenAiStyleControllerOld extends Controller
{
    use ApiResponse;

    protected $imageStyleService;

    public function __construct(OpenAiStyleService $imageStyleService)
    {
        $this->imageStyleService = $imageStyleService;
    }

    /**
     * Get AI styling suggestions for a specific item from user's wardrobe
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
                ->with('category')
                ->first();

            if (!$userItem) {
                return $this->error([], 'This item does not belong to your wardrobe.', 403);
            }

            $profileResponse = app(UserProfileController::class)->profile($request)->getData(true);
            $profileData = $profileResponse['data'] ?? [];
            $preferences = $profileData['style_profile'] ?? [
                'type' => 'Mixed Style',
                'details' => '',
                'keywords' => ''
            ];

            Log::info('Processing item styling request', [
                'user_id' => $userId,
                'item_id' => $itemId,
                'style_type' => $preferences['type'] ?? 'Unknown'
            ]);

            $webSiteNames = \App\Models\WebSiteName::pluck('name')->toArray();
            if (empty($webSiteNames)) {
                $webSiteNames = ['https://amazon.com'];
            }
            $preferences['websites'] = $webSiteNames;

            $result = $this->imageStyleService->getItemStyleSuggestions($userItem, $preferences, $webSiteNames);

            if (isset($result['error'])) {
                DB::rollBack();
                return $this->error([], $result['error'], 400);
            }

            ApiHit::create([
                'user_id' => $userId,
                'success' => true,
            ]);

            DB::commit();

            return $this->success($result, 'Item styling suggestion generated successfully.', 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Style Item Controller Error', [
                'user_id' => auth('api')->id(),
                'item_id' => $request->input('item_id'),
                'error' => $e->getMessage()
            ]);

            return $this->error([], 'Something went wrong while generating styling suggestions.', 500);
        }
    }

}
