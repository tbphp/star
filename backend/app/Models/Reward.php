<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    protected $fillable = [
        'name',
        'image',
        'star_cost',
        'is_redeemed',
        'redeemed_at',
    ];

    protected $casts = [
        'star_cost' => 'integer',
        'is_redeemed' => 'boolean',
        'redeemed_at' => 'datetime',
    ];

    /**
     * Get children participating in this reward
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'reward_children')
            ->withPivot('deduction_amount')
            ->withTimestamps();
    }

    /**
     * Get star records related to this reward (redemption records)
     */
    public function starRecords(): HasMany
    {
        return $this->hasMany(StarRecord::class);
    }

    /**
     * Calculate total stars from all participating children
     */
    public function getTotalStarsAttribute(): int
    {
        return $this->children()->sum('star_count');
    }

    /**
     * Check if reward goal is achieved
     */
    public function getIsAchievedAttribute(): bool
    {
        return $this->total_stars >= $this->star_cost;
    }
}
