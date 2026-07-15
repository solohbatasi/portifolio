<?php

namespace App\Enums;

enum CoffeePaymentStatus: string
{
    case Created = 'created';
    case Initiating = 'initiating';
    case Pending = 'pending';
    case Processing = 'processing';
    case Success = 'success';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Timeout = 'timeout';
    case Reversed = 'reversed';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Success, self::Failed, self::Cancelled, self::Timeout, self::Reversed], true);
    }

    public function message(): string
    {
        return match ($this) {
            self::Created => 'Preparing your payment request.',
            self::Initiating => 'Sending the payment request.',
            self::Pending => 'Check your phone and complete the M-PESA prompt.',
            self::Processing => 'Your payment is being confirmed.',
            self::Success => 'Thank you for supporting my work.',
            self::Cancelled => 'The M-PESA request was cancelled.',
            self::Timeout => 'The payment request timed out. You can start a new request.',
            self::Failed => 'The payment could not be completed.',
            self::Reversed => 'The payment was reversed.',
        };
    }
}
