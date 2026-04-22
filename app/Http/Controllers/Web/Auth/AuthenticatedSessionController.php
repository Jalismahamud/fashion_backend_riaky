<?php

namespace App\Http\Controllers\Web\Auth;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Auth\LoginRequest;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {

        $request->authenticate();
        $request->session()->regenerate();
        $user = auth('web')->user();

        if ($user && $user->role === 'admin') {

            Log::info('Admin Logged in', ['user_id' => $user->id, 'email' => $user->email]);
            return redirect()->intended(route('dashboard', absolute: false));
        } else {
            Auth::logout();
            return redirect()->route('login');
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
