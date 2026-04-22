<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'stripe_coupon_id',
        'name',
        'description',
        'type',
        'amount',
        'currency',
        'max_redemptions',
        'current_redemptions',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'amount' => 'decimal:2',
    ];

    /**
     * Check if coupon is valid
     */
    public function isValid()
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check expiry
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        // Check redemption limit
        if ($this->max_redemptions && $this->current_redemptions >= $this->max_redemptions) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can use this coupon
     */
    public function canBeUsedBy($userId)
    {
        // Check if user already used this coupon
        $alreadyUsed = \App\Models\CouponUsage::where('coupon_id', $this->id)
            ->where('user_id', $userId)
            ->exists();

        return !$alreadyUsed;
    }

    /**
     * Mark coupon as used by user
     */
    public function markAsUsed($userId)
    {
        // Create usage record
        \App\Models\CouponUsage::create([
            'coupon_id' => $this->id,
            'user_id' => $userId,
            'used_at' => now(),
        ]);

        // Increment redemption count
        $this->increment('current_redemptions');
    }

    /**
     * Get formatted discount amount
     */
    public function getFormattedDiscountAttribute()
    {
        if ($this->type === 'percentage') {
            return $this->amount . '%';
        }

        return '$' . number_format($this->amount, 2);
    }
}
