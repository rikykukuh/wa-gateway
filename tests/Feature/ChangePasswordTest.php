<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\User;
use App\Support\ApiKeyGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'wa-gateway.admin_email' => 'admin@example.com',
            'wa-gateway.admin_password_hash' => Hash::make('admin-password-lama'),
        ]);
    }

    public function test_admin_can_change_password_and_login_with_the_new_password(): void
    {
        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'admin-password-lama',
        ])->assertRedirect('/');

        $this->put('/change-password', [
            'current_password' => 'admin-password-lama',
            'password' => 'admin-password-baru',
            'password_confirmation' => 'admin-password-baru',
        ])->assertRedirect('/login')
            ->assertSessionHas('status');

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'admin-password-baru',
        ])->assertRedirect('/');
    }

    public function test_user_can_change_password_but_wrong_current_password_is_rejected(): void
    {
        $key = ApiKeyGenerator::generate();
        $client = ApiClient::create([
            'name' => 'Riky',
            'email' => 'riky@example.com',
            'key_prefix' => $key['prefix'],
            'api_key_hash' => $key['hash'],
            'encrypted_api_key' => Crypt::encryptString($key['plain_text']),
            'is_active' => true,
        ]);
        User::create([
            'api_client_id' => $client->id,
            'name' => 'Riky',
            'email' => 'riky@example.com',
            'password' => 'user-password-lama',
        ]);

        $this->post('/login', [
            'email' => 'riky@example.com',
            'password' => 'user-password-lama',
        ])->assertRedirect('/');

        $this->from('/change-password')->put('/change-password', [
            'current_password' => 'password-salah',
            'password' => 'user-password-baru',
            'password_confirmation' => 'user-password-baru',
        ])->assertRedirect('/change-password')
            ->assertSessionHasErrors('current_password');

        $this->put('/change-password', [
            'current_password' => 'user-password-lama',
            'password' => 'user-password-baru',
            'password_confirmation' => 'user-password-baru',
        ])->assertRedirect('/login');

        $this->post('/login', [
            'email' => 'riky@example.com',
            'password' => 'user-password-baru',
        ])->assertRedirect('/');
    }
}
