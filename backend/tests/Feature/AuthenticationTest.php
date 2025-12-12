<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

describe('Authentication API', function () {
    describe('login endpoint', function () {
        test('user can login with valid credentials', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('password123'),
            ]);

            $response = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
                'device_name' => 'Mobile App',
            ]);

            $response->assertOk()
                ->assertJsonStructure([
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                ]);

            $this->assertNotEmpty($response->json('token'));
        });

        test('user cannot login with wrong password', function () {
            User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('correct_password'),
            ]);

            $response = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'wrong_password',
                'device_name' => 'Mobile App',
            ]);

            $response->assertUnauthorized();
        });

        test('user cannot login with non-existent email', function () {
            $response = $this->postJson('/api/login', [
                'email' => 'nonexistent@example.com',
                'password' => 'password123',
                'device_name' => 'Mobile App',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        test('login fails with missing email', function () {
            $response = $this->postJson('/api/login', [
                'password' => 'password123',
                'device_name' => 'Mobile App',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        test('login fails with missing password', function () {
            $response = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'device_name' => 'Mobile App',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        test('login fails with missing device_name', function () {
            $response = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['device_name']);
        });

        test('login fails with invalid email format', function () {
            $response = $this->postJson('/api/login', [
                'email' => 'not-an-email',
                'password' => 'password123',
                'device_name' => 'Mobile App',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        test('multiple logins from same user create separate tokens', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('password123'),
            ]);

            $response1 = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
                'device_name' => 'Phone',
            ]);

            $response2 = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
                'device_name' => 'Tablet',
            ]);

            $token1 = $response1->json('token');
            $token2 = $response2->json('token');

            $this->assertNotEmpty($token1);
            $this->assertNotEmpty($token2);
            $this->assertNotEquals($token1, $token2);
        });
    });

    describe('logout endpoint', function () {
        test('authenticated user can logout', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-device')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/logout');

            $response->assertOk();
        });

        test('unauthenticated user cannot logout', function () {
            $response = $this->postJson('/api/logout');

            $response->assertUnauthorized();
        });

        test('logout endpoint exists and returns success', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-device')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/logout');

            $response->assertOk();
        });
    });

    describe('token management', function () {
        test('authenticated user can access protected routes with valid token', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-device')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson('/api/tasks');

            $response->assertOk();
        });

        test('user with invalid token cannot access protected routes', function () {
            $response = $this->withHeader('Authorization', 'Bearer invalid-token')
                ->getJson('/api/tasks');

            $response->assertUnauthorized();
        });

        test('user without token cannot access protected routes', function () {
            $response = $this->getJson('/api/tasks');

            $response->assertUnauthorized();
        });

        test('user can maintain multiple active sessions', function () {
            $user = User::factory()->create();
            $token1 = $user->createToken('Phone')->plainTextToken;
            $token2 = $user->createToken('Tablet')->plainTextToken;

            $response1 = $this->withHeader('Authorization', "Bearer {$token1}")
                ->getJson('/api/tasks');
            $response1->assertOk();

            $response2 = $this->withHeader('Authorization', "Bearer {$token2}")
                ->getJson('/api/tasks');
            $response2->assertOk();
        });
    });
});
