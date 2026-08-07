<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_can_open_the_database_login_form(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }

    public function test_guest_is_redirected_to_login_from_business_routes(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->from('/')->post('/login')
            ->assertRedirect('/')
            ->assertSessionHasErrors(['email', 'password']);
    }
}
