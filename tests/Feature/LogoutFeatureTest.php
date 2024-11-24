<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class LogoutFeatureTest extends TestCase
{
   /**
     * Test successful logout.
     *
     * @return void
     */
    public function test_user_can_logout_successfully()
    {
        // Create a user instance
        $user = User::factory()->create();

        // Authenticate the user with Sanctum
        Sanctum::actingAs($user);

        // Perform a POST request to the logout route
        $response = $this->postJson('/api/logout');

        // Assert the response status and content
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Logged out successfully']);
    }

}
