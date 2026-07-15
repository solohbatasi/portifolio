<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCoffeePaymentRequest;
use App\Models\CoffeePayment;
use App\Services\CoffeePaymentPresenter;
use App\Services\CoffeePaymentReconciler;
use App\Services\CoffeePaymentService;
use App\Support\KenyanPhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;

class CoffeePaymentController extends Controller
{
    public function store(
        StoreCoffeePaymentRequest $request,
        CoffeePaymentService $payments,
        CoffeePaymentPresenter $presenter,
    ): JsonResponse {
        $validated = $request->validated();
        if ($existing = CoffeePayment::where('request_id', $validated['request_id'])->first()) {
            return response()->json($presenter->present($existing));
        }

        $normalized = KenyanPhoneNumber::normalize($validated['phone']);
        $phoneKey = 'coffee-phone:'.KenyanPhoneNumber::hash($normalized);
        if (RateLimiter::tooManyAttempts($phoneKey, 3)) {
            return response()->json(['message' => 'Too many payment attempts for this number. Please wait before trying again.'], 429);
        }
        RateLimiter::hit($phoneKey, 600);

        $payment = $payments->create($validated['request_id'], $normalized, (int) $validated['amount']);

        return response()->json($presenter->present($payment), 202);
    }

    public function show(
        CoffeePayment $payment,
        CoffeePaymentReconciler $reconciler,
        CoffeePaymentPresenter $presenter,
    ): JsonResponse {
        $payment = $reconciler->refresh($payment);

        return response()->json($presenter->present($payment));
    }
}
