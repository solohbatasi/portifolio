<?php

namespace App\Models;

use App\Enums\CoffeePaymentStatus;
use Illuminate\Database\Eloquent\Model;

class CoffeePayment extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'phone_encrypted', 'phone_hash', 'merchant_request_id', 'checkout_request_id',
        'response_description', 'customer_message', 'result_description', 'mpesa_receipt',
        'reconciliation_warning', 'callback_amount',
    ];

    protected function casts(): array
    {
        return [
            'phone_encrypted' => 'encrypted',
            'status' => CoffeePaymentStatus::class,
            'transaction_date' => 'datetime',
            'callback_received_at' => 'datetime',
            'last_status_query_at' => 'datetime',
            'initiated_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
