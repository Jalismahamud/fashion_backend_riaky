<?php

namespace App\Http\Controllers\Api\Backend;

use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class ApiCategoryController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $categories = Category::select('id', 'name')->get();

            if ($categories->isEmpty()) {
                return $this->success([], 'No categories found.', 200);
            }

            return $this->success($categories, 'Categories retrieved successfully.');
        } catch (\Exception $e) {
            return $this->error([], 'An error occurred while retrieving categories: ' . $e->getMessage(), 500);
        }
    }

    public function myList()
    {
        try {

            $user = auth('api')->user();
            $categories = Category::whereHas('items', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
                ->with(['items' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }])
                ->paginate(6);

            if ($categories->isEmpty()) {
                return $this->success([], 'No categories found for this user.', 200);
            }

            $result = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'items' => $category->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'item_name' => $item->item_name,
                            'slug' => $item->slug,
                            'image_path' => $item->image_path ?? null,
                        ];
                    }),
                ];
            });

            $paginate = [
                'last_page' => $categories->lastPage(),
                'total' => $categories->total(),
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
            ];

            $response = [
                'paginate' => $paginate,
                'categories' => $result,
            ];

            return $this->success($response, 'User categories with items retrieved successfully.');
        } catch (\Exception $e) {

            Log::info($e->getMessage());
            return $this->error([], 'An error occurred while retrieving user categories: ' . $e->getMessage(), 500);
        }
    }

    public function myListDetails($slug)
    {
        try {
            $category = Category::where('slug', $slug)->first();

            if (!$category) {
                return $this->success([], 'Category not found.', 200);
            }

            $items = $category->items()
                ->select('id', 'category_id', 'item_name', 'slug', 'image_path')
                ->where('user_id', auth('api')->id())
                ->paginate(10);

            $categoryData = [
                'id'   => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ];

            $itemsData = $items->map(function ($item) {
                return [
                    'id'        => $item->id,
                    'item_name' => $item->item_name,
                    'slug'      => $item->slug,
                    'image_path'     => $item->image_path ?? null,
                ];
            });

            $paginate = [
                'last_page'    => $items->lastPage(),
                'total'        => $items->total(),
                'current_page' => $items->currentPage(),
                'per_page'     => $items->perPage(),
            ];

            return $this->success([
                'category' => $categoryData,
                'items'    => $itemsData,
                'paginate' => $paginate,
            ], 'Category details retrieved successfully.');

        } catch (\Exception $e) {

            Log::error('Error fetching category details: ' . $e->getMessage());
            return $this->error([], 'Failed to fetch category details', 500);
        }
    }
}
//
