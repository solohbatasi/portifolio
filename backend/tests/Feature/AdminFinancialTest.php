<?php

namespace Tests\Feature;

use App\Enums\CoffeePaymentStatus;
use App\Models\CoffeePayment;
use App\Models\MpesaBalanceSnapshot;
use App\Models\Payout;
use App\Models\User;
use App\Services\RecordedBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminFinancialTest extends TestCase
{
    use RefreshDatabase;

    public function test_recorded_balance_and_pending_reservations_use_confirmed_records(): void
    {
        $user = $this->user();
        $this->payment(1000, CoffeePaymentStatus::Success);
        $this->payment(500, CoffeePaymentStatus::Failed);
        $this->payout($user, 200, 'success');
        $this->payout($user, 300, 'pending');
        $this->payout($user, 400, 'failed');
        $this->assertSame(['inflows' => 1000, 'successful_payouts' => 200, 'recorded' => 800, 'reserved' => 300, 'available' => 500], app(RecordedBalanceService::class)->summary());
    }

    public function test_admin_pages_require_authentication_and_use_public_ids(): void
    {
        $payment = $this->payment(100, CoffeePaymentStatus::Success);
        $this->get('/admin/transactions')->assertRedirect('/admin/login');
        $this->actingAs($this->user())->get('/admin/transactions/'.$payment->public_id)->assertOk()->assertDontSee($payment->checkout_request_id);
    }

    public function test_disabled_b2c_and_balance_are_safe(): void
    {
        $user = $this->user();
        config()->set(['daraja.b2c.enabled' => false, 'daraja.balance.enabled' => false]);
        $this->actingAs($user)->post('/admin/balance/refresh')->assertSessionHasErrors('balance');
        $this->actingAs($user)->post('/admin/payouts', ['phone' => '0716933897', 'amount' => 100, 'remarks' => 'Test', 'request_id' => (string) Str::uuid()])->assertSessionHasErrors('payout');
    }

    public function test_b2c_and_balance_callbacks_are_idempotent_and_parse_parameters_by_name(): void
    {
        $user = $this->user();
        $payout = $this->payout($user, 200, 'pending');
        $payout->update(['conversation_id' => 'conversation-1', 'originator_conversation_id' => 'originator-1']);
        $payload = ['Result' => ['ResultCode' => 0, 'ResultDesc' => 'Completed', 'ConversationID' => 'conversation-1',
            'OriginatorConversationID' => 'originator-1', 'ResultParameters' => ['ResultParameter' => [
                ['Key' => 'TransactionReceipt', 'Value' => 'RECEIPT1'], ['Key' => 'TransactionID', 'Value' => 'TXN1'],
            ]]]];
        $this->postJson('/api/mpesa/b2c/result', $payload)->assertOk();
        $this->postJson('/api/mpesa/b2c/result', $payload)->assertOk();
        $this->assertSame('success', $payout->refresh()->status);
        $this->assertSame('TXN1', $payout->transaction_id);

        $snapshot = MpesaBalanceSnapshot::create(['public_id' => (string) Str::uuid(), 'conversation_id' => 'balance-1', 'request_status' => 'pending', 'requested_at' => now()]);
        $this->postJson('/api/mpesa/balance/result', ['Result' => ['ResultCode' => 0, 'ResultDesc' => 'OK', 'ConversationID' => 'balance-1',
            'ResultParameters' => ['ResultParameter' => [['Key' => 'AccountBalance', 'Value' => 'Utility Account|KES|20.00&Working Account|KES|900.00']]]]])->assertOk();
        $this->assertSame('success', $snapshot->refresh()->request_status);
        $this->assertSame(900, $snapshot->working_account_balance);
    }

    private function user(): User
    {
        config()->set('admin.allowed_emails', ['solomonbatasi@gmail.com']);

        return User::firstOrCreate(['email' => 'solomonbatasi@gmail.com'], ['name' => 'Solomon', 'is_active' => true]);
    }

    private function payment(int $amount, CoffeePaymentStatus $status): CoffeePayment
    {
        return CoffeePayment::create(['public_id' => (string) Str::uuid(), 'request_id' => (string) Str::uuid(), 'reference' => 'COFFEE-'.Str::upper(Str::random(8)), 'amount' => $amount, 'phone_encrypted' => '254716933897', 'phone_hash' => hash('sha256', Str::random()), 'phone_masked' => '2547****897', 'status' => $status, 'checkout_request_id' => 'checkout-'.Str::random(), 'completed_at' => $status === CoffeePaymentStatus::Success ? now() : null]);
    }

    private function payout(User $u, int $amount, string $status): Payout
    {
        return Payout::create(['public_id' => (string) Str::uuid(), 'request_id' => (string) Str::uuid(), 'reference' => 'PAYOUT-'.Str::upper(Str::random(8)), 'phone_encrypted' => '254716933897', 'phone_hash' => hash('sha256', Str::random()), 'phone_masked' => '2547****897', 'amount' => $amount, 'command_id' => 'BusinessPayment', 'remarks' => 'Test', 'status' => $status, 'initiated_by' => $u->id]);
    }
}
