<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MpesaBalanceSnapshot extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['other_balances' => 'encrypted:array', 'requested_at' => 'datetime', 'received_at' => 'datetime', 'failed_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
