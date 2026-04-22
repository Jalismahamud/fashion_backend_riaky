<?php

namespace App\Http\Controllers\Web\Backend\Settings;

use App\Helper\Helper;
use App\Models\SocialLink;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class SocialLinkController extends Controller
{
    //show socal medial links page
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = SocialLink::latest('id'); // query builder, ok for yajra

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group">
                    <button class="editBtn btn btn-primary"
                        data-id="' . $data->id . '"
                        data-name="' . htmlspecialchars($data->name) . '"
                        data-url="' . htmlspecialchars($data->url) . '">
                        Edit
                    </button>

                    <a href="#" onclick="showDeleteConfirm(' . $data->id . ')" class="btn btn-danger text-white" title="Delete">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>';
                })
                ->addColumn('icon', function ($data) {
                    if ($data->icon) {
                        return '<img src="' . asset($data->icon) . '" alt="Icon" width="50">';
                    }
                    return '-';
                })
                ->rawColumns(['action', 'icon'])
                ->make(true);
        }

        return view('backend.layouts.settings.social_link'); // fixed spelling
    }


    //store social profile
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:100',
            'url'  => 'required|url|max:255',
            'icon' => 'nullable|image|mimes:jpg,jpeg,png,svg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ]);
        }

        $socialLink = new SocialLink();
        $socialLink->name = $request->name;
        $socialLink->url  = $request->url;

        // Icon upload
        if ($request->hasFile('icon')) {
            $socialLink->icon = Helper::uploadImage($request->file('icon'), 'social_icons', time());
        }

        $socialLink->save();

        return response()->json([
            'success' => true,
            'message' => 'Social link added successfully!',
        ]);
    }

    //update social profile
    public function update(Request $request, $id)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:100',
            'url'  => 'required|url|max:255',
            'icon' => 'nullable|image|mimes:jpg,jpeg,png,svg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ]);
        }

        $socialLink = SocialLink::findOrFail($id);
        $socialLink->name = $request->name;
        $socialLink->url  = $request->url;

        // Icon upload + old icon delete
        if ($request->hasFile('icon')) {
            if ($socialLink->icon) {
                Helper::deleteImage($socialLink->icon); // delete old icon
            }

            // Upload new image using helper
            $socialLink->icon = Helper::uploadImage($request->file('icon'), 'social_icons', time());
        }

        $socialLink->save();

        return response()->json([
            'success' => true,
            'message' => 'Social link updated successfully!',
        ]);
    }


    //delete social profile
    public function destroy($id)
    {

        $item = SocialLink::find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        if ($item->icon) {
            Helper::deleteImage($item->icon);
        }
        $item->delete();

        return response()->json(['success' => true, 'message' => 'Item deleted successfully']);
    }
}
