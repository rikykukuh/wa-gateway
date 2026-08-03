<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        if ($request->session()->get('wa_admin_authenticated', false)) {
            $currentHash = AppSetting::find('admin_password_hash')?->value
                ?: (string) config('wa-gateway.admin_password_hash');

            $this->ensureCurrentPassword($validated['current_password'], $currentHash);
            AppSetting::updateOrCreate(
                ['key' => 'admin_password_hash'],
                ['value' => Hash::make($validated['password'])],
            );
        } else {
            $user = User::findOrFail($request->session()->get('wa_user_id'));
            $this->ensureCurrentPassword($validated['current_password'], $user->password);
            $user->update(['password' => $validated['password']]);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Password berhasil diubah. Silakan login kembali.');
    }

    private function ensureCurrentPassword(string $password, string $hash): void
    {
        if ($hash === '' || ! Hash::check($password, $hash)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password lama tidak sesuai.',
            ]);
        }
    }
}
