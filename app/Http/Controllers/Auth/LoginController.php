<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('wa_authenticated', false)) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $emailMatches = hash_equals(
            mb_strtolower((string) config('wa-gateway.admin_email')),
            mb_strtolower($credentials['email']),
        );
        $passwordHash = AppSetting::find('admin_password_hash')?->value
            ?: (string) config('wa-gateway.admin_password_hash');

        if ($emailMatches && $passwordHash !== '' && Hash::check($credentials['password'], $passwordHash)) {
            $request->session()->regenerate();
            $request->session()->put([
                'wa_authenticated' => true,
                'wa_admin_authenticated' => true,
            ]);

            return redirect()->intended(route('dashboard'));
        }

        $user = User::query()->with('apiClient')->where('email', $credentials['email'])->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'Email atau password tidak valid.'])
                ->onlyInput('email');
        }

        if (! $user->apiClient?->is_active) {
            return back()
                ->withErrors(['email' => 'Akun Anda belum diaktifkan atau sedang dinonaktifkan oleh administrator.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put([
            'wa_authenticated' => true,
            'wa_admin_authenticated' => false,
            'wa_user_id' => $user->id,
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
