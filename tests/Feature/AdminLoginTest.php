<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'wa-gateway.admin_email' => 'admin@example.com',
            'wa-gateway.admin_password_hash' => Hash::make('correct-password'),
        ]);
    }

    public function test_login_page_is_available(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Masuk ke dashboard');
    }

    public function test_admin_can_login_and_open_dashboard(): void
    {
        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'correct-password',
        ])->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertSee('WhatsApp Devices');
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->from('/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->get('/')->assertRedirect('/login');
    }

    public function test_admin_can_logout(): void
    {
        $this->withSession(['wa_admin_authenticated' => true])
            ->post('/logout')
            ->assertRedirect('/login');

        $this->get('/')->assertRedirect('/login');
    }
}
