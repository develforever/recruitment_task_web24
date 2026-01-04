<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ImportsTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_redirect_to_login(): void
    {
        $response = $this->get(route('imports'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_imports()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('imports'));
        $response->assertStatus(200);
    }
}
