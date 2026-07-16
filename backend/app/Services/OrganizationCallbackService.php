<?php

namespace App\Services;

use App\Models\MpesaBalanceSnapshot;
use App\Models\Payout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class OrganizationCallbackService
{
    public function __construct(private readonly AuditService $audit) {}

    public function payout(array $payload, bool $timeout = false): void
    {
        $result = data_get($payload, 'Result');
        if (! is_array($result)) {
            return;
        }
        $conversation = $result['ConversationID'] ?? null;
        $originator = $result['OriginatorConversationID'] ?? null;
        if (! is_string($conversation) && ! is_string($originator)) {
            return;
        }
        $payout = Payout::where(function ($query) use ($conversation, $originator): void {
            if (is_string($conversation)) {
                $query->where('conversation_id', $conversation);
            }
            if (is_string($originator)) {
                $query->orWhere('originator_conversation_id', $originator);
            }
        })->first();
        if (! $payout) {
            return;
        }
        DB::transaction(function () use ($payout, $result, $timeout): void {
            $locked = Payout::whereKey($payout->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->status === 'success') {
                return;
            }
            $code = (string) ($result['ResultCode'] ?? 'unknown');
            $params = $this->parameters(data_get($result, 'ResultParameters.ResultParameter', []));
            $locked->update(['status' => $timeout ? 'timeout' : ($code === '0' ? 'success' : 'failed'), 'result_code' => $code,
                'result_description' => $this->safe($result['ResultDesc'] ?? null), 'transaction_id' => $this->safe($params['TransactionID'] ?? $params['TransactionReceipt'] ?? null, 64),
                'callback_received_at' => now(), 'completed_at' => ! $timeout && $code === '0' ? now() : null,
                'failed_at' => ! $timeout && $code !== '0' ? now() : null, 'timeout_at' => $timeout ? now() : null]);
            $this->audit->record('payout_callback_processed', $locked, ['result_code' => $code, 'timeout' => $timeout]);
        });
    }

    public function balance(array $payload, bool $timeout = false): void
    {
        $result = data_get($payload, 'Result');
        if (! is_array($result)) {
            return;
        }
        $conversation = $result['ConversationID'] ?? null;
        $originator = $result['OriginatorConversationID'] ?? null;
        if (! is_string($conversation) && ! is_string($originator)) {
            return;
        }
        $snapshot = MpesaBalanceSnapshot::where(function ($query) use ($conversation, $originator): void {
            if (is_string($conversation)) {
                $query->where('conversation_id', $conversation);
            }
            if (is_string($originator)) {
                $query->orWhere('originator_conversation_id', $originator);
            }
        })->latest()->first();
        if (! $snapshot) {
            return;
        }
        if ($snapshot->request_status === 'success') {
            return;
        }
        $code = (string) ($result['ResultCode'] ?? 'unknown');
        $params = $this->parameters(data_get($result, 'ResultParameters.ResultParameter', []));
        $balances = $this->parseBalances((string) ($params['AccountBalance'] ?? ''));
        $snapshot->update(['request_status' => $timeout ? 'timeout' : ($code === '0' ? 'success' : 'failed'), 'result_code' => $code,
            'result_description' => $this->safe($result['ResultDesc'] ?? null), 'working_account_balance' => $balances['Working Account'] ?? null,
            'utility_account_balance' => $balances['Utility Account'] ?? null, 'charges_paid_account_balance' => $balances['Charges Paid Account'] ?? null,
            'other_balances' => array_diff_key($balances, array_flip(['Working Account', 'Utility Account', 'Charges Paid Account'])),
            'received_at' => $timeout ? null : now(), 'failed_at' => $timeout || $code !== '0' ? now() : null]);
    }

    private function parameters(mixed $items): array
    {
        $out = [];
        foreach (is_array($items) ? $items : [] as $item) {
            if (is_array($item) && isset($item['Key'])) {
                $out[$item['Key']] = $item['Value'] ?? null;
            }
        }

        return $out;
    }

    private function parseBalances(string $value): array
    {
        $out = [];
        foreach (explode('&', $value) as $part) {
            if (preg_match('/^([^|]+)\|KES\|(-?\d+(?:\.\d+)?)$/', trim($part), $m)) {
                [$whole, $fraction] = array_pad(explode('.', $m[2], 2), 2, '0');
                $out[trim($m[1])] = (int) $whole + ((int) str_pad($fraction, 2, '0') >= 50 ? 1 : 0);
            }
        }

        return $out;
    }

    private function safe(mixed $value, int $length = 255): ?string
    {
        return is_scalar($value) ? Str::limit(strip_tags((string) $value), $length, '') : null;
    }
}
