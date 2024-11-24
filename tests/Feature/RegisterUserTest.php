<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterUserTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_registers_a_user_successfully()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('api/register', $data);

        // Assert the response status is 201 (created)
        $response->assertStatus(201);

        // Assert the response contains a user and token
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'token',
        ]);

        // Assert the user is stored in the database
        $this->assertDatabaseHas('users', [
            'email' => 'johndoe@example.com',
        ]);
    }

    /** @test */
    public function it_returns_validation_errors_if_data_is_invalid()
    {
        $data = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
        ];

        $response = $this->postJson('api/register', $data);

        // Assert the response status is 422 (unprocessable entity)
        $response->assertStatus(422);

        // Assert the response contains the validation errors
        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /** @test */
    public function it_returns_error_if_email_is_already_taken()
    {
        // Create an existing user
        User::factory()->create([
            'email' => 'johndoe@example.com',
        ]);

        $data = [
            'name' => 'Jane Doe',
            'email' => 'johndoe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('api/register', $data);

        // Assert the response status is 422 (unprocessable entity)
        $response->assertStatus(422);

        // Assert the response contains the validation error for email
        $response->assertJsonValidationErrors(['email']);
    }
}
