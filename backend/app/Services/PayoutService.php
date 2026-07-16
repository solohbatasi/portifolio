<?php

namespace App\Services;

use App\Exceptions\DarajaException;
use App\Models\Payout;
use App\Models\User;
use App\Support\KenyanPhoneNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class PayoutService
{
    public function __construct(private readonly OrganizationDarajaClient $daraja, private readonly RecordedBalanceService $balances, private readonly AuditService $audit) {}

    public function create(array $data, User $user): Payout
    {
        if ($existing = Payout::where('request_id', $data['request_id'])->first()) {
            return $existing;
        }
        if (! config('daraja.b2c.enabled')) {
            throw new RuntimeException('B2C payouts are not configured.');
        }
        $phone = KenyanPhoneNumber::normalize($data['phone']);
        $amount = (int) $data['amount'];
        $creationLock = Cache::lock('payout-creation', 20);
        if (! $creationLock->get()) {
            throw new RuntimeException('Another payout is being prepared. Please try again shortly.');
        }
        try {
            $payout = DB::transaction(function () use ($data, $user, $phone, $amount): Payout {
                DB::table('payouts')->lockForUpdate()->get();
                $balance = $this->balances->summary();
                if (config('daraja.b2c.require_balance_check') && $amount > $balance['available']) {
                    throw new RuntimeException('The payout exceeds the available recorded balance.');
                }
                $dailyLimit = config('daraja.b2c.daily_limit');
                if ($dailyLimit && Payout::whereDate('created_at', today())->whereNotIn('status', ['failed', 'cancelled'])->sum('amount') + $amount > $dailyLimit) {
                    throw new RuntimeException('The configured daily payout limit would be exceeded.');
                }

                return Payout::create(['public_id' => (string) Str::uuid(), 'request_id' => $data['request_id'],
                    'reference' => 'PAYOUT-'.strtoupper(substr(hash('sha256', $data['request_id']), 0, 8)), 'phone_encrypted' => $phone,
                    'phone_hash' => KenyanPhoneNumber::hash($phone), 'phone_masked' => KenyanPhoneNumber::mask($phone), 'amount' => $amount,
                    'command_id' => config('daraja.b2c.command_id'), 'remarks' => $data['remarks'], 'occasion' => $data['occasion'] ?? null,
                    'status' => 'initiating', 'initiated_by' => $user->id]);
            });
        } finally {
            $creationLock->release();
        }
        $this->audit->record('payout_created', $payout, ['amount' => $amount]);
        try {
            $response = $this->daraja->b2c($phone, $amount, $payout->remarks, $payout->occasion);
            $payout->update(['status' => 'pending', 'conversation_id' => $response['ConversationID'] ?? null,
                'originator_conversation_id' => $response['OriginatorConversationID'] ?? null, 'initiated_at' => now()]);
            $this->audit->record('payout_submitted', $payout, ['status' => 'pending']);
        } catch (DarajaException $e) {
            $payout->update(['status' => $e->uncertain ? 'processing' : 'failed', 'failed_at' => $e->uncertain ? null : now(), 'result_description' => $e->getMessage()]);
        }

        return $payout->refresh();
    }
}
