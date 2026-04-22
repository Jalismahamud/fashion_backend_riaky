<?php

namespace App\Http\Controllers\Api\Backend;

use Exception;
use App\Models\Item;
use App\Helper\Helper;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ApiItemController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $items = Item::with(['category', 'user'])->where('user_id', auth('api')->id())->orderBy('created_at', 'desc')->paginate(6);

            if ($items->isEmpty()) {
                return $this->success([], 'No items found.', 200);
            }

            $itemData = $items->getCollection()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'category_id' => $item->category_id,
                    'clouth_type' => $item->clouth_type,
                    'material' => $item->material,
                    'pattern' => $item->pattern,
                    'color' => $item->color,
                    'season' => $item->season,
                    'item_name' => $item->item_name,
                    'slug' => $item->slug,
                    'image' => !empty($item->image) && is_string($item->image) ? url($item->image) : null,
                    'image_path' => $item->image_path,
                    'buying_info' => $item->buying_info,
                    'site_link' => $item->site_link,
                    'created_at' => $item->created_at->diffForHumans(),
                ];
            })->toArray();

            $paginate = [
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
            ];

            $response = [
                'paginate' => $paginate,
                'items' => $itemData,
            ];

            return $this->success($response, 'Items retrieved successfully.', 200);
        } catch (Exception $e) {
            Log::error('Error fetching items: ' . $e->getMessage());
            return $this->error([], 'Failed to fetch items', 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'required|exists:categories,id',
                'clouth_type' => 'nullable|string',
                'material' => 'nullable|string',
                'pattern' => 'nullable|string',
                'color' => 'nullable|string',
                'season' => 'nullable|string',
                'item_name' => 'nullable|string',
                'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,bmp,tiff,heif,heic,jfif',
                'image_path' => 'nullable|string',
                'buying_info' => 'nullable|string',
                'site_link' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->error([], $validator->errors()->first(), 422);
            }

            $data = $validator->validated();
            $data['user_id'] = Auth::id();

            if ($request->hasFile('image')) {
                $imagePath = Helper::uploadImage($request->file('image'), 'items');
                $data['image'] = $imagePath;
            }

            $item = Item::create($data);

            $cacheKey = "weather_suggestion_user_" . Auth::id();
            Cache::forget($cacheKey);

           $item['image'] = $item->image ? url($item->image) : null;

            return $this->success($item, 'Item created successfully.', 201);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function show($slug)
    {
        try {
            $item = Item::with(['category', 'user'])->where('slug', $slug)->first();
            if (!$item) {
                return $this->error([], 'Item not found', 404);
            }

            $item = [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'category_id' => $item->category_id,
                'clouth_type' => $item->clouth_type,
                'material' => $item->material,
                'pattern' => $item->pattern,
                'color' => $item->color,
                'season' => $item->season,
                'item_name' => $item->item_name,
                'slug' => $item->slug,
                'image'   => url($item->image),
                'image_path' => $item->image_path,
                'buying_info' => $item->buying_info,
                'site_link' => $item->site_link,
                'created_at' => $item->created_at->diffForHumans(),
            ];

            return $this->success($item, 'Item retrieved successfully.', 200);
        } catch (Exception $e) {
            Log::error('Error fetching item: ' . $e->getMessage());
            return $this->error([], 'Failed to fetch item', 500);
        }
    }

    public function update(Request $request, $slug)
    {
        try {

            $item = Item::where('slug', $slug)->first();
            if (!$item) {
                return $this->error([], 'Item not found', 404);
            }

            if ($item->user_id !== Auth::id()) {
                return $this->error([], 'You are not authorized to update this item', 403);
            }

            $validator = Validator::make($request->all(), [
                'category_id' => 'sometimes|exists:categories,id',
                'clouth_type' => 'nullable|string',
                'material' => 'nullable|string',
                'pattern' => 'nullable|string',
                'color' => 'nullable|string',
                'season' => 'nullable|string',
                'item_name' => 'nullable|string',
                'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,bmp,tiff,heif,heic,jfif',
                'image_path' => 'nullable|string',
                'buying_info' => 'nullable|string',
                'site_link' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->error([], $validator->errors()->first(), 422);
            }

            $data = $validator->validated();


            if ($request->hasFile('image')) {

                if ($item->image) {
                    Helper::deleteImage($item->image, 'items');
                }

                $imagePath = Helper::uploadImage($request->file('image'), 'items');
                $data['image'] = $imagePath;
            }

            $item->update($data);

            $cacheKey = "weather_suggestion_user_" . Auth::id();
            Cache::forget($cacheKey);

            return $this->success($item, 'Item updated successfully.', 200);
        } catch (Exception $e) {

            Log::error($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }


    public function destroy($slug)
    {
        try {
            $item = Item::where('slug', $slug)->first();
            if (!$item) {
                return $this->error([], 'Item not found', 404);
            }

            if ($item->user_id !== Auth::id()) {
                return $this->error([], 'You are not authorized to delete this item', 403);
            }

            if ($item->image) {
                Helper::deleteImage($item->image, 'items');
            }

            if ($item->image_path) {
                Helper::deleteImageFromLink($item->image_path);
            }

            $item->delete();

            $cacheKey = "weather_suggestion_user_" . Auth::id();
            Cache::forget($cacheKey);

            return $this->success([], 'Item deleted successfully.', 200);
        } catch (Exception $e) {

            Log::error($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }
}
