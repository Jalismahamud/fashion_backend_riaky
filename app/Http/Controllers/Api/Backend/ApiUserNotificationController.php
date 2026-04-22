<?php

namespace App\Http\Controllers\Api\Backend;

use Exception;
use Carbon\Carbon;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ApiUserNotificationController extends Controller
{
    use ApiResponse;

    public function notificationStatus(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->error([], 'User not authenticated.', 401);
            }

            $notificationSettings = UserNotification::where('user_id', $user->id)->first();
            if (!$notificationSettings) {
                return $this->error([], 'Notification settings not found.', 404);
            }

            return $this->success($notificationSettings, 'Notification settings retrieved successfully.', 200);
        } catch (Exception $e) {

            Log::info($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function toggleNotification(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => ['required', 'in:push_notification,daily_notification,weekly_notification'],
            ]);

            if ($validator->fails()) {
                return $this->error([], $validator->errors()->first(), 422);
            }

            $user = Auth::user();
            if (!$user) {
                return $this->error([], 'User not authenticated.', 401);
            }

            $notificationSettings = UserNotification::where('user_id', $user->id)->first();
            if (!$notificationSettings) {
                return $this->error([], 'Notification settings not found.', 404);
            }

            $type = $request->input('type');
            $currentValue = $notificationSettings->$type;
            $notificationSettings->$type = !$currentValue;
            $notificationSettings->save();

            return $this->success([
                'type' => $type,
                'value' => $notificationSettings->$type
            ], 'Notification setting toggled successfully.', 200);
        } catch (Exception $e) {

            Log::info($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }


    // user notifications
    // public function allNotifications()
    // {
    //     try {
    //         $user = Auth::user();
    //         if (!$user) {
    //             return $this->error([], 'User not authenticated.', 401);
    //         }

    //         // Fetch all database notifications for the authenticated user
    //         $notifications = $user->notifications()->orderBy('created_at', 'desc')->whereNull('read_at')->get();

    //         if ($notifications->isEmpty()) {
    //             return $this->success([], 'No notifications found.', 200);
    //         }

    //         $notifications = $notifications->map(function ($notification) {
    //             return [
    //                 'id' => $notification->id,
    //                 'data' => $notification->data,
    //                 'read_at' => $notification->read_at,
    //                 'created_at' => $notification->created_at->diffForHumans()
    //             ];
    //         });

    //         return $this->success($notifications, 'Notifications retrieved successfully.', 200);
    //     } catch (\Exception $e) {

    //         Log::info($e->getMessage());
    //         return $this->error([], $e->getMessage(), 500);
    //     }
    // }



    public function allNotifications()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->error([], 'User not authenticated.', 401);
            }


            $notifications = $user->notifications()
                ->orderBy('created_at', 'desc')
                ->whereNull('read_at')
                ->get();

            if ($notifications->isEmpty()) {
                return $this->success([], 'No notifications found.', 200);
            }

            $grouped = [];

            foreach ($notifications as $notification) {
                $createdAt = Carbon::parse($notification->created_at);
                $dateKey = '';

                if ($createdAt->isToday()) {
                    $dateKey = 'Today';
                } elseif ($createdAt->isYesterday()) {
                    $dateKey = 'Yesterday';
                } else {
                    $dateKey = $createdAt->format('d M Y');
                }

                $grouped[$dateKey][] = [
                    'id' => $notification->id,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $createdAt->diffForHumans()
                ];
            }


            $ordered = [];
            if (!empty($grouped['Today'])) {
                $ordered['Today'] = $grouped['Today'];
            }
            if (!empty($grouped['Yesterday'])) {
                $ordered['Yesterday'] = $grouped['Yesterday'];
            }


            unset($grouped['Today'], $grouped['Yesterday']);
            if (!empty($grouped)) {

                $remaining = collect($grouped)->sortByDesc(function ($_, $key) {
                    return Carbon::createFromFormat('d M Y', $key)->timestamp;
                })->toArray();

                $ordered = array_merge($ordered, $remaining);
            }

            return $this->success($ordered, 'Notifications retrieved successfully.', 200);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }


    public function readNotification($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->error([], 'User not authenticated.', 401);
            }

            $notification = $user->notifications()->where('id', $id)->first();

            if (!$notification) {
                return $this->error([], 'Notification not found.', 404);
            }

            $notification->markAsRead();

            return $this->success([], 'Notification marked as read successfully.', 200);
        } catch (Exception $e) {

            Log::info($e->getMessage());
            return $this->error([], $e->getMessage(), 500);
        }
    }
}
