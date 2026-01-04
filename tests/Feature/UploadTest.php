<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_to_login(): void
    {
        $response = $this->get(route('upload'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_upload()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('upload'));
        $response->assertStatus(200);
    }
}
