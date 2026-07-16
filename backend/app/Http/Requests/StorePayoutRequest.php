<?php

namespace App\Http\Requests;

use App\Support\KenyanPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class StorePayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $max = config('daraja.b2c.maximum');

        return ['phone' => ['required', 'string', 'max:30', function ($a, $v, $fail) {
            try {
                KenyanPhoneNumber::normalize((string) $v);
            } catch (InvalidArgumentException) {
                $fail('Enter a valid Kenyan Safaricom number.');
            }
        }],
            'amount' => array_filter(['required', 'integer', 'min:'.config('daraja.b2c.minimum'), $max ? 'max:'.$max : null]),
            'remarks' => ['required', 'string', 'max:100'], 'occasion' => ['nullable', 'string', 'max:100'], 'request_id' => ['required', 'uuid']];
    }
}
