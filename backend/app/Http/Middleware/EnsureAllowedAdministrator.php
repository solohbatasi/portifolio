<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAllowedAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $allowed = config('admin.allowed_emails', []);
        if (! $user || ! $user->is_active || ! in_array(strtolower($user->email), $allowed, true)) {
            auth()->logout();
            $request->session()->invalidate();

            return redirect()->route('admin.denied');
        }

        return $next($request);
    }
}
