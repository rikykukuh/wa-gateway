<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\User;
use App\Support\ApiKeyGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:api_clients,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($validated) {
            $key = ApiKeyGenerator::generate();
            $client = ApiClient::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'key_prefix' => $key['prefix'],
                'api_key_hash' => $key['hash'],
                'encrypted_api_key' => Crypt::encryptString($key['plain_text']),
                'daily_message_limit' => 60,
                'min_delay_seconds' => 30,
                'is_active' => false,
            ]);

            User::create([
                'api_client_id' => $client->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
        });

        return redirect()->route('login')->with(
            'status',
            'Pendaftaran berhasil. Tunggu administrator mengaktifkan akun Anda.',
        );
    }
}
