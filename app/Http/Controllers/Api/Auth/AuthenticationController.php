<?php

namespace App\Http\Controllers\Api\Auth;

use Exception;

use Carbon\Carbon;
use App\Models\User;
use App\Mail\SendOtpMail;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class AuthenticationController extends Controller
{
    use ApiResponse;

    //Register a new user
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            if ($validator->fails()) {
                return $this->error([], $validator->errors()->first(), 422);
            }


            $validatedData = $validator->validated();

            $otp = rand(1000, 9999);
            $otpExpiresAt = now()->addMinutes(5);


            $cacheKey = 'register_data_' . $validatedData['email'];
            $cacheData = [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'otp' => $otp,
                'otp_expires_at' => $otpExpiresAt,
            ];

            Cache::put($cacheKey, $cacheData, 300);

            Mail::to($validatedData['email'])->send(new SendOtpMail($otp, $validatedData['name']));

            return $this->success(
                [
                    'email' => $validatedData['email'],
                    'otp' => $otp,
                ],
                'OTP sent successfully. Please verify within 5 minutes.',
                200,
            );
        } catch (Exception $e) {

            Log::error($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }


    public function registrationVerifyOtp(Request $request)
    {
        $validator = validator()->make($request->all(), [
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:4'],
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $email = $request->email;
        $otp = $request->otp;

        $cacheKey = 'register_data_' . $email;
        $cachedData = Cache::get($cacheKey);

        if (!$cachedData) {
            return $this->error([], 'OTP has expired or data not found. Please register again.', 410);
        }

        if ($cachedData['otp'] != $otp) {
            return $this->error([], 'Invalid OTP.', 409);
        }

        if (Carbon::now()->gt(Carbon::parse($cachedData['otp_expires_at']))) {
            Cache::forget($cacheKey);
            return $this->error([], 'OTP has expired.', 410);
        }


        $user = User::create([
            'name' => $cachedData['name'],
            'email' => $cachedData['email'],
            'password' => $cachedData['password'],
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
            'is_otp_verified' => true,
        ]);

        Cache::forget($cacheKey);


        $token = auth('api')->login($user);


        $expiresInMinutes = auth('api')->factory()->getTTL();
        $expiresAt = now()->addMinutes($expiresInMinutes)->toDateTimeString();

        $userData = [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'chique_auth_token' => $token,
            'expires_in_minutes' => $expiresInMinutes,
            'expires_at' => $expiresAt,
            'is_style_profile' => $user->getIsStyleProfileAttribute()
        ];

        return $this->success($userData, 'Registration completed successfully.', 200);
    }


    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            if ($validator->fails()) {
                return $this->error([], $validator->errors()->first(), 422);
            }

            $credentials = $validator->validated();

            $user = User::where('email', $credentials['email'])->first();
            if (!$user) {
                return $this->error([], 'Email is incorrect or not found in our database.', 404);
            }

            if (!Hash::check($credentials['password'], $user->password)) {
                return $this->error([], 'Password is incorrect.', 401);
            }

            if (!($token = auth('api')->attempt($credentials))) {
                return $this->error([], 'Invalid email or password.', 401);
            }

            $user = auth('api')->user();


            $expiresInMinutes = auth('api')->factory()->getTTL();
            $expiresAt = now()->addMinutes($expiresInMinutes)->toDateTimeString();

            $userData = [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'chique_auth_token' => $token,
                'expires_in_minutes' => $expiresInMinutes,
                'expires_at' => $expiresAt,
                'is_style_profile' => $user->getIsStyleProfileAttribute(),
            ];

            return $this->success($userData, 'Successfully Logged In', 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }


    public function logout()
    {
        try {
            if (Auth::check('api')) {
                Auth::logout('api');
                return $this->success([],'Successfully logged out.', 200);
            } else {
                return $this->error([false], 'User not Authenticated.', 401);
            }
        } catch (Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }
}
