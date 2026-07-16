<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class AuditService
{
    public function record(string $action, ?Model $subject = null, array $metadata = [], ?Request $request = null): void
    {
        AuditLog::create([
            'user_id' => auth()->id(), 'action' => $action,
            'auditable_type' => $subject ? $subject::class : null, 'auditable_id' => $subject?->getKey(),
            'metadata' => $metadata ?: null,
            'ip_hash' => $request ? hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')) : null,
        ]);
    }
}
