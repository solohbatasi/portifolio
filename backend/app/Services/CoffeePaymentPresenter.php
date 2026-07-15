<?php

namespace App\Services;

use App\Models\CoffeePayment;

final class CoffeePaymentPresenter
{
    /** @return array{payment_id: string, status: string, amount: int, phone: string, message: string} */
    public function present(CoffeePayment $payment): array
    {
        return [
            'payment_id' => $payment->public_id,
            'status' => $payment->status->value,
            'amount' => $payment->amount,
            'phone' => $payment->phone_masked,
            'message' => $payment->status->message(),
        ];
    }
}
