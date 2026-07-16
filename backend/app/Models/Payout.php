<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['phone_encrypted' => 'encrypted', 'amount' => 'integer', 'initiated_at' => 'datetime', 'callback_received_at' => 'datetime', 'completed_at' => 'datetime', 'failed_at' => 'datetime', 'timeout_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}
