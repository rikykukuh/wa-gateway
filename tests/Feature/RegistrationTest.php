<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['wa-gateway.api_key' => 'master-test-key']);
    }

    public function test_visitor_can_register_but_cannot_login_before_admin_approval(): void
    {
        $this->post('/register', [
            'name' => 'Riky',
            'email' => 'riky@example.com',
            'password' => 'password-rahasia',
            'password_confirmation' => 'password-rahasia',
        ])->assertRedirect('/login')
            ->assertSessionHas('status');

        $user = User::with('apiClient')->where('email', 'riky@example.com')->firstOrFail();
        $this->assertFalse($user->apiClient->is_active);

        $this->from('/login')->post('/login', [
            'email' => 'riky@example.com',
            'password' => 'password-rahasia',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_activate_account_then_user_gets_scoped_dashboard_access(): void
    {
        $this->post('/register', [
            'name' => 'Riky',
            'email' => 'riky@example.com',
            'password' => 'password-rahasia',
            'password_confirmation' => 'password-rahasia',
        ]);
        $client = ApiClient::where('email', 'riky@example.com')->firstOrFail();

        $this->withToken('master-test-key')
            ->patchJson("/api/v1/clients/{$client->id}", ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->post('/login', [
            'email' => 'riky@example.com',
            'password' => 'password-rahasia',
        ])->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('WhatsApp Devices')
            ->assertDontSee('API Clients');
    }
}
