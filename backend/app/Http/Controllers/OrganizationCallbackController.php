<?php

namespace App\Http\Controllers;

use App\Services\OrganizationCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class OrganizationCallbackController extends Controller
{
    public function b2cResult(Request $r, OrganizationCallbackService $s): JsonResponse
    {
        try {
            $s->payout($r->json()->all());
        } catch (Throwable) {
        }

return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    public function b2cTimeout(Request $r, OrganizationCallbackService $s): JsonResponse
    {
        try {
            $s->payout($r->json()->all(), true);
        } catch (Throwable) {
        }

return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    public function balanceResult(Request $r, OrganizationCallbackService $s): JsonResponse
    {
        try {
            $s->balance($r->json()->all());
        } catch (Throwable) {
        }

return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    public function balanceTimeout(Request $r, OrganizationCallbackService $s): JsonResponse
    {
        try {
            $s->balance($r->json()->all(), true);
        } catch (Throwable) {
        }

return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
