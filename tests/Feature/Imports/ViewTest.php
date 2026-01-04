<?php

namespace Tests\Feature\Imports;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_to_login(): void
    {
        $response = $this->get(route('imports.view', ['id' => 1]));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_view()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('imports.view', ['id' => 1]));
        $response->assertStatus(200);
    }
}
