<?php

namespace App\Http\Controllers;

use App\Services\DarajaCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DarajaCallbackController extends Controller
{
    public function __invoke(Request $request, DarajaCallbackService $callbacks): JsonResponse
    {
        try {
            $payload = $request->json()->all();
            $callbacks->handle(is_array($payload) ? $payload : []);
        } catch (Throwable $exception) {
            Log::error('M-PESA callback processing failed safely.', ['failure' => $exception::class]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
