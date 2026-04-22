<?php

namespace App\Http\Controllers\Web\Backend;

use Exception;
use App\Models\WebSiteName;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class WebSiteNameController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = WebSiteName::latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('created_at', function ($data) {
                    return $data->created_at ? $data->created_at->format('Y-m-d') : '---';
                })
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
                ->rawColumns(['action','created_at'])
                ->make(true);
        }
        return view('backend.layouts.website.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|url',
        ]);

        try {
            WebSiteName::create(['name' => $request->name]);
            return response()->json(['success' => true, 'message' => 'Website name created successfully.']);
        } catch (Exception $e) {

            Log::error('Website Name Create Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        try {
            $website = WebSiteName::find($id);

            if (!$website) {
                return response()->json(['success' => false, 'message' => 'Website name not found.'], 404);
            }

            $website->update(['name' => $request->name]);
            return response()->json(['success' => true, 'message' => 'Website name updated successfully.']);
        } catch (Exception $e) {

            Log::error('Website Name Update Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $website = WebSiteName::find($id);

            if (!$website) {
                return response()->json(['success' => false, 'message' => 'Website name not found.'], 404);
            }

            $website->delete();
            return response()->json(['success' => true, 'message' => 'Website name deleted successfully.']);
        } catch (Exception $e) {

            Log::error('Website Name Delete Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
