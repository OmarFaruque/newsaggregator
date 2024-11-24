<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class LoginFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function testUserCanLoginSuccessfully()
    {
        // Create a user in the database
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123')
        ]);

        // Send login request
        $response = $this->postJson('/api/login', [
            'email' => 'testuser@example.com',
            'password' => 'password123'
        ]);

        // Assertions
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'user' => ['id', 'email', 'name'],
            'token'
        ]);
    }

    public function testLoginFailsWithInvalidCredentials()
    {
        // Send login request with invalid credentials
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword'
        ]);

        // Assertions
        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'Unauthorized'
        ]);
    }

    public function testValidationErrorsOnLogin()
    {
        // Send login request with missing fields
        $response = $this->postJson('/api/login', []);

        // Assertions
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'errors' => ['email', 'password']
        ]);
    }
}
