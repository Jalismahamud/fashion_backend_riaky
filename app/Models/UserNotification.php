<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $fillable = [
        'user_id',
        'push_notification',
        'daily_notification',
        'weekly_notification',
    ];

    protected $hidden= [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'push_notification' => 'boolean',
        'daily_notification' => 'boolean',
        'weekly_notification' => 'boolean',
    ];

    /**
     * Get the user that owns the notification settings.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
