<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePayoutRequest;
use App\Models\AuditLog;
use App\Models\CoffeePayment;
use App\Models\MpesaBalanceSnapshot;
use App\Models\Payout;
use App\Services\AuditService;
use App\Services\CoffeePaymentReconciler;
use App\Services\OrganizationDarajaClient;
use App\Services\PayoutService;
use App\Services\RecordedBalanceService;
use App\Support\KenyanPhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class AdminController extends Controller
{
    public function dashboard(RecordedBalanceService $balances)
    {
        $success = CoffeePayment::where('status', 'success');
        $stats = ['today_count' => (clone $success)->whereDate('completed_at', today())->count(), 'today_amount' => (int) (clone $success)->whereDate('completed_at', today())->sum('amount'),
            'month_count' => (clone $success)->whereBetween('completed_at', [now()->startOfMonth(), now()->endOfMonth()])->count(), 'month_amount' => (int) (clone $success)->whereBetween('completed_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'total_count' => (clone $success)->count(), 'pending' => CoffeePayment::whereIn('status', ['created', 'initiating', 'pending', 'processing'])->count(),
            'failed' => CoffeePayment::whereIn('status', ['failed', 'cancelled', 'timeout'])->count(), 'payout_success' => Payout::where('status', 'success')->count(), 'payout_pending' => Payout::whereIn('status', ['initiating', 'pending', 'processing', 'timeout'])->count()];

        return view('admin.dashboard', ['stats' => $stats, 'recorded' => $balances->summary(), 'latestBalance' => MpesaBalanceSnapshot::where('request_status', 'success')->latest('received_at')->first(), 'payments' => CoffeePayment::latest()->limit(6)->get(), 'payouts' => Payout::with('administrator')->latest()->limit(6)->get()]);
    }

    public function transactions(Request $request)
    {
        $query = CoffeePayment::query();
        $this->filters($query, $request);

        return view('admin.transactions.index', ['payments' => $query->latest()->paginate(20)->withQueryString()]);
    }

    public function transaction(CoffeePayment $payment)
    {
        return view('admin.transactions.show', compact('payment'));
    }

    public function refreshTransaction(CoffeePayment $payment, CoffeePaymentReconciler $r, AuditService $audit): RedirectResponse
    {
        $r->refresh($payment);
        $audit->record('manual_transaction_refresh', $payment);

        return back()->with('status', 'Status refresh completed safely.');
    }

    public function payouts(Request $request)
    {
        $q = Payout::with('administrator');
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('reference')) {
            $q->where('reference', 'like', '%'.$request->reference.'%');
        }
        if ($request->filled('date_from')) {
            $q->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('min_amount')) {
            $q->where('amount', '>=', (int) $request->min_amount);
        }
        if ($request->filled('max_amount')) {
            $q->where('amount', '<=', (int) $request->max_amount);
        }
        if ($request->filled('phone')) {
            try {
                $q->where('phone_hash', KenyanPhoneNumber::hash(KenyanPhoneNumber::normalize($request->phone)));
            } catch (\InvalidArgumentException) {
                $q->whereRaw('1 = 0');
            }
        }

        return view('admin.payouts.index', ['payouts' => $q->latest()->paginate(20)->withQueryString(), 'requestId' => (string) Str::uuid()]);
    }

    public function payout(Payout $payout)
    {
        $payout->load('administrator');
        $audits = AuditLog::where('auditable_type', Payout::class)->where('auditable_id', $payout->id)->latest('created_at')->get();

        return view('admin.payouts.show', compact('payout', 'audits'));
    }

    public function createPayout(StorePayoutRequest $request, PayoutService $service): RedirectResponse
    {
        try {
            $p = $service->create($request->validated(), $request->user());

            return redirect()->route('admin.payouts.show', $p)->with('status', 'Payout request recorded.');
        } catch (RuntimeException $e) {
            return back()->withErrors(['payout' => $e->getMessage()])->withInput();
        }
    }

    public function balance(RecordedBalanceService $service)
    {
        return view('admin.balance', ['recorded' => $service->summary(), 'latest' => MpesaBalanceSnapshot::latest('requested_at')->first(), 'enabled' => config('daraja.balance.enabled')]);
    }

    public function refreshBalance(OrganizationDarajaClient $daraja, AuditService $audit): RedirectResponse
    {
        if (! config('daraja.balance.enabled')) {
            return back()->withErrors(['balance' => 'M-PESA balance integration is not configured.']);
        }
        $lock = Cache::lock('mpesa-balance-request', 30);
        if (! $lock->get()) {
            return back()->withErrors(['balance' => 'A balance request is already in progress.']);
        }
        try {
            $s = MpesaBalanceSnapshot::create(['public_id' => (string) Str::uuid(), 'request_status' => 'pending', 'requested_at' => now()]);
            $response = $daraja->balance();
            $s->update(['conversation_id' => $response['ConversationID'] ?? null, 'originator_conversation_id' => $response['OriginatorConversationID'] ?? null]);
            $audit->record('balance_refresh_requested', $s);

            return back()->with('status', 'Balance request submitted. Waiting for Safaricom callback.');
        } catch (\Throwable) {
            if (isset($s)) {
                $s->update(['request_status' => 'failed', 'failed_at' => now()]);
            }

            return back()->withErrors(['balance' => 'The balance request could not be submitted.']);
        } finally {
            $lock->release();
        }
    }

    private function filters($q, Request $r): void
    {
        foreach (['status', 'reference', 'mpesa_receipt', 'public_id'] as $f) {
            if ($r->filled($f)) {
                $q->where($f, $f === 'status' ? '=' : 'like', $f === 'status' ? $r->$f : '%'.$r->$f.'%');
            }
        }
        if ($r->filled('date_from')) {
            $q->whereDate('created_at', '>=', $r->date_from);
        }
        if ($r->filled('date_to')) {
            $q->whereDate('created_at', '<=', $r->date_to);
        }
        if ($r->filled('min_amount')) {
            $q->where('amount', '>=', (int) $r->min_amount);
        }
        if ($r->filled('max_amount')) {
            $q->where('amount', '<=', (int) $r->max_amount);
        }
    }
}
