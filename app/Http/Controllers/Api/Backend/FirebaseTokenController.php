<?php

namespace App\Http\Controllers\Api\Backend;

use Exception;
use App\Models\User;
use App\Helper\Helper;
use Illuminate\Http\Request;
use App\Models\FirebaseToken;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class FirebaseTokenController extends Controller
{
    public function test()
    {
        $user = User::find(auth('api')->user()->id);

        if ($user && $user->firebaseTokens) {
            $notifyData = [
                'title' => "test title",
                'body'  => "test body",
                'icon'  => env('APP_ICON')
            ];

            foreach ($user->firebaseTokens as $firebaseToken) {
                Helper::sendNotifyMobile($firebaseToken->token, $notifyData);
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Token saved successfully',
            'data'    => $user ? $user->firebaseTokens : [],
            'code'    => 200,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token'     => 'required|string',
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }

        $firebase = FirebaseToken::where('user_id', auth('api')->user()->id)
            ->where('device_id', $request->device_id)
            ->first();

        if ($firebase) {
            $firebase->delete();
        }

        try {
            $data = new FirebaseToken();
            $data->user_id = auth('api')->user()->id;
            $data->token = $request->token;
            $data->device_id = $request->device_id;
            $data->status = "active";
            $data->save();

            return response()->json([
                'status'  => true,
                'message' => 'Token saved successfully',
                'data'    => $data,
                'code'    => 200,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'No records found',
                'code'    => 418,
                'data'    => [],
            ], 418);
        }
    }

    public function getToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }

        $user_id = auth('api')->user()->id;
        $device_id = $request->device_id;

        $data = FirebaseToken::where('user_id', $user_id)
            ->where('device_id', $device_id)
            ->get();

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => 'No records found',
                'code'    => 404,
                'data'    => [],
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Token fetched successfully',
            'data'    => $data,
            'code'    => 200,
        ], 200);
    }

    public function deleteToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }

        $user = FirebaseToken::where('user_id', auth('api')->user()->id)
            ->where('device_id', $request->device_id)
            ->first();

        if ($user) {
            $user->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Token deleted successfully',
                'code'    => 200,
            ], 200);
        } else {
            return response()->json([
                'status'  => false,
                'message' => 'No records found',
                'code'    => 404,
            ], 404);
        }
    }
}
