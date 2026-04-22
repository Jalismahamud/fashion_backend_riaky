<?php

namespace App\Http\Controllers\Web\Backend;

use App\Models\User;
use App\Helper\Helper;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class UserListController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = User::select('id', 'name','email', 'avatar', 'created_at')
                        ->where('role', '!=', 'admin')
                        ->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('avatar', function ($data) {
                    if ($data->avatar) {
                        $url = asset($data->avatar);
                        return '<img src="' . $url . '" alt="avatar" width="50px" height="50px">';
                    } else {
                        return '---';
                    }
                })
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
                              <a href="#" onclick="showDeleteConfirm(' . $data->id . ')" type="button" class="btn btn-danger text-white" title="Delete">
                              <i class="bi bi-trash"></i>
                            </a>
                            </div>';
                })
                ->addColumn('email', function ($data) {
                    return $data->email ?? '---';
                })

                ->addColumn('created_at', function ($data) {
                    return $data->created_at ? $data->created_at->format('Y-m-d') : '---';
                })
                ->rawColumns(['avatar', 'email', 'city', 'created_at', 'action'])
                ->make(true);
        }
        return view("backend.layouts.user.index");
    }



        public function destroy(int $id): JsonResponse {
            $data = User::findOrFail($id);
            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            if ($data->avatar) {
                Helper::deleteImage($data->avatar);
            }

            $data->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully!',
            ], 200);
        }



}
