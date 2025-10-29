<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Child extends Model
{
    protected $table = 'children';

    protected $fillable = [
        'name',
        'birthday',
        'gender',
        'avatar',
        'star_count',
    ];

    protected $casts = [
        'birthday' => 'date',
        'star_count' => 'integer',
    ];

    /**
     * Get all star records for this child
     */
    public function starRecords(): HasMany
    {
        return $this->hasMany(StarRecord::class);
    }

    /**
     * Get rewards this child is participating in
     */
    public function rewards(): BelongsToMany
    {
        return $this->belongsToMany(Reward::class, 'reward_children')
            ->withPivot('deduction_amount')
            ->withTimestamps();
    }

    /**
     * Calculate age from birthday
     */
    public function getAgeAttribute(): int
    {
        return $this->birthday->age;
    }
}
