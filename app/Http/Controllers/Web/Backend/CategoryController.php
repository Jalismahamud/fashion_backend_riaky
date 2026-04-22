<?php

namespace App\Http\Controllers\Web\Backend;

use Exception;
use App\Models\Category;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Category::latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
                              <a href="#" onclick="showEditModal(' . $data->id . ', \'' . addslashes($data->name) . '\')" class="btn btn-primary text-white" title="Edit">
                              <i class="bi bi-pencil"></i>
                              </a>
                              <a href="#" onclick="showDeleteConfirm(' . $data->id . ')" type="button" class="btn btn-danger text-white" title="Delete">
                              <i class="bi bi-trash"></i>
                            </a>
                            </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('backend.layouts.category.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            Category::create(['name' => $request->name]);
            return response()->json(['success' => true, 'message' => 'Category created successfully.']);
        } catch (Exception $e) {
            Log::error('Category Create Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $category = Category::find($id);

            if(!$category) {
                return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
            }

            $category->update(['name' => $request->name]);
            return response()->json(['success' => true, 'message' => 'Category updated successfully.']);
        } catch (Exception $e) {
            Log::error('Category Update Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $category = Category::find($id);

            if(!$category) {
                return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
            }

            $category->delete();
            return response()->json(['success' => true, 'message' => 'Category deleted successfully.']);
        } catch (Exception $e) {
            Log::error('Category Delete Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
