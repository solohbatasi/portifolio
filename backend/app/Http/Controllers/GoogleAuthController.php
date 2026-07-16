<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        abort_if(app()->environment('production') && config('admin.allowed_emails') === [], 503, 'Administrator access is not configured.');

        return Socialite::driver('google')->scopes(['openid', 'email', 'profile'])->redirect();
    }

    public function callback(Request $request, AuditService $audit): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('admin.login')->withErrors(['login' => 'Google authentication could not be completed.']);
        }

        $email = strtolower(trim((string) $google->getEmail()));
        $verified = data_get($google->user, 'email_verified');
        if ($email === '' || $verified === false || ! in_array($email, config('admin.allowed_emails', []), true)) {
            $audit->record('google_login_denied', null, ['email_hash' => $email ? hash('sha256', $email) : null], $request);

            return redirect()->route('admin.denied');
        }

        $user = User::firstOrNew(['email' => $email]);
        if ($google->getName()) {
            $user->name = $google->getName();
        } elseif (! $user->exists) {
            $user->name = 'Solomon Batasi';
        }
        $user->google_id = $google->getId();
        $user->avatar_url = $google->getAvatar();
        $user->last_login_at = now();
        $user->last_login_ip_hash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        $user->save();
        Auth::login($user);
        $request->session()->regenerate();
        $audit->record('google_login_success', $user, [], $request);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request, AuditService $audit): RedirectResponse
    {
        $audit->record('logout', $request->user(), [], $request);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
