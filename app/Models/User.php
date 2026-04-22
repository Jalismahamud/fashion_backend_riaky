<?php

namespace App\Models;

use Laravel\Cashier\Billable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;


class User extends Authenticatable implements JWTSubject
{

    use HasFactory, Billable;

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',

        'otp',
        'is_otp_verified',
        'otp_expires_at',
        'role',

        'reset_password_token',
        'reset_password_token_expire_at',

        'is_google_signin',
        'google_id',
        'is_apple_signin',
        'apple_id',
        'gender',
        'address',
        'latitude',
        'longitude',
    ];


    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'is_otp_verified' => 'boolean',
            'reset_password_token_expires_at' => 'datetime',
            'password' => 'hashed',
            'last_alive_check_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    public function getIsStyleProfileAttribute(): bool
    {
        return $this->style_profiles()->exists();
    }

    public function style_profiles()
    {
        return $this->hasOne(StyleQuizAnswer::class, 'user_id', 'id');
    }

    public function getAvatarAttribute($value): string | null
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        if (request()->is('api/*') && !empty($value)) {

            return url($value);
        }
        return $value;
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'user_id', 'id');
    }

    public function chats()
    {
        return $this->hasMany(ChatHistory::class);
    }
}
