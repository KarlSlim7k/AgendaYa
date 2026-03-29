<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $defaultRoute = Gate::forUser($request->user())->allows('platform-admin')
            ? route('admin.dashboard', absolute: false)
            : route('dashboard', absolute: false);

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($defaultRoute.'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended($defaultRoute.'?verified=1');
    }
}
