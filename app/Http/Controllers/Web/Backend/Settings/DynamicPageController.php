<?php

namespace App\Http\Controllers\Web\Backend\Settings;

use Exception;
use App\Models\User;
use App\Models\DynamicPage;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class DynamicPageController extends Controller {

    use ApiResponse;

    public function index(Request $request) {


        if ($request->ajax()) {
            $data = DynamicPage::latest();
            if (!empty($request->input('search.value'))) {
                $searchTerm = $request->input('search.value');
                $data->where('page_title', 'LIKE', "%$searchTerm%");
            }
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('page_content', function ($data) {
                    $page_content       = $data->page_content;
                    $short_page_content = strlen($page_content) > 100 ? substr($page_content, 0, 100) . '...' : $page_content;
                    return '<p>' . $short_page_content . '</p>';
                })

                ->addColumn('status', function ($data) {
                    $backgroundColor = $data->status == "active" ? '#4CAF50' : '#ccc';
                    $sliderTranslateX = $data->status == "active" ? '26px' : '2px';
                    $sliderStyles = "position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background-color: white; border-radius: 50%; transition: transform 0.3s ease; transform: translateX($sliderTranslateX);";

                    $status = '<div class="form-check form-switch" style="margin-left:40px; position: relative; width: 50px; height: 24px; background-color: ' . $backgroundColor . '; border-radius: 12px; transition: background-color 0.3s ease; cursor: pointer;">';
                    $status .= '<input onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" class="form-check-input" id="customSwitch' . $data->id . '" getAreaid="' . $data->id . '" name="status" style="position: absolute; width: 100%; height: 100%; opacity: 0; z-index: 2; cursor: pointer;">';
                    $status .= '<span style="' . $sliderStyles . '"></span>';
                    $status .= '<label for="customSwitch' . $data->id . '" class="form-check-label" style="margin-left: 10px;"></label>';
                    $status .= '</div>';

                    return $status;
                })
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
                              <a href="' . route('admin.dynamic_page.edit', ['id' => $data->id]) . '" type="button" class="btn btn-primary text-white" title="Edit">
                              <i class="bi bi-pencil"></i>
                              </a>
                              <!---<a href="#" onclick="showDeleteConfirm(' . $data->id . ')" type="button" class="btn btn-danger text-white" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a> -->
                            </div>';
                })


                ->rawColumns(['page_content', 'status', 'action'])
                ->make();
        }
        return view('backend.layouts.settings.dynamic_page.index');
    }


    public function edit(int $id) {
        try{
            if (User::find(auth()->user()->id)) {
                $data = DynamicPage::find($id);
                return view('backend.layouts.settings.dynamic_page.edit', compact('data'));
            }
            return redirect()->route('admin.dynamic_page.index');
        }catch(Exception $e){
            return redirect()->route('admin.dynamic_page.index')->with('t-error', 'Permission Denied');
        }

    }


    public function update(Request $request, int $id) {
        try {
            if (User::find(auth()->user()->id)) {
                $validator = Validator::make($request->all(), [
                    // 'page_title'   => 'nullable|string',
                    'page_content' => 'nullable|string',
                ]);

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator)->withInput();
                }

                $data = DynamicPage::find($id);
                $data->update([
                    // 'page_title'   => $request->page_title,
                    // 'page_slug'    => Str::slug($request->page_title),
                    'page_content' => $request->page_content,
                ]);

                return redirect()->route('admin.dynamic_page.index')->with('t-success', 'Dynamic Page Updated Successfully.');
            }
        } catch (Exception) {
            return redirect()->route('admin.dynamic_page.index')->with('t-error', 'Dynamic Page failed to update');
        }
        return redirect()->route('admin.dynamic_page.index');
    }

    /**
     * Change the status of the specified dynamic page.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function status(int $id) {
        $data = DynamicPage::findOrFail($id);
        if ($data->status == 'active') {
            $data->status = 'inactive';
            $data->save();

            return response()->json([
                'success' => false,
                'message' => 'Unpublished Successfully.',
                'data'    => $data,
            ]);
        } else {
            $data->status = 'active';
            $data->save();

            return response()->json([
                'success' => true,
                'message' => 'Published Successfully.',
                'data'    => $data,
            ]);
        }
    }


    public function destroy(int $id) {
        $page = DynamicPage::find($id);
        $page->delete();
        return response()->json([
            't-success' => true,
            'message'   => 'Deleted successfully.',
        ]);
    }


    // Dynamic pages api routes

    public function privacyPolicy()
    {
        try {

            $data = DynamicPage::where('page_slug', 'privacy-policy')->get();

            if (!$data) {
                return $this->success([], 'Privacy Policy data not found.', 200);
            }

            return $this->success($data, 'Privacy policy data retrieved successfully.', 200);
        } catch (Exception $e) {

            Log::error($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }
    public function aboutUs()
    {
        try {

            $data = DynamicPage::where('page_slug', 'about-us')->get();

            if (!$data) {
                return $this->success([], 'About Us data not found.', 200);
            }

            return $this->success($data, 'About Us data retrieved successfully.', 200);
        } catch (Exception $e) {

            Log::error($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }
}
