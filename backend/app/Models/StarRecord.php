<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StarRecord extends Model
{
    protected $fillable = [
        'child_id',
        'amount',
        'type',
        'reward_id',
        'reason',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    /**
     * Get the child this record belongs to
     */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /**
     * Get the reward if this is a redemption record
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }
}
