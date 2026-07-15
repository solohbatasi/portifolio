<?php

namespace App\Http\Requests;

use App\Support\KenyanPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class StoreCoffeePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, callable $fail): void {
                try {
                    KenyanPhoneNumber::normalize((string) $value);
                } catch (InvalidArgumentException) {
                    $fail('Enter a valid Kenyan M-PESA phone number.');
                }
            }],
            'amount' => ['required', 'integer', 'min:'.config('daraja.coffee.minimum'), 'max:'.config('daraja.coffee.maximum')],
            'request_id' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.integer' => 'The amount must be a whole-number KES value.',
        ];
    }
}
