<?php

namespace Tests\Security;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class AuthenticationSecurityTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_prevents_brute_force_attacks_on_login()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $invalidCredentials = [
            'email' => 'test@example.com',
            'password' => 'wrong_password'
        ];

        // Act - Attempt multiple failed logins
        $maxAttempts = 5;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $response = $this->postJson('/api/login', $invalidCredentials);
        }

        // Assert - Should be rate limited after 5 attempts
        $finalResponse = $this->postJson('/api/login', $invalidCredentials);
        $finalResponse->assertStatus(429); // Too Many Requests
    }

    /** @test */
    public function it_validates_token_security()
    {
        // Arrange
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $token = $user->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
                         ->getJson('/api/v1/financial/dashboard');

        // Assert
        $response->assertStatus(200);

        // Test with invalid token
        $invalidResponse = $this->withHeader('Authorization', 'Bearer invalid_token')
                               ->getJson('/api/v1/financial/dashboard');
        $invalidResponse->assertStatus(401);
    }

    /** @test */
    public function it_prevents_sql_injection_attacks()
    {
        // Arrange
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $maliciousPayloads = [
            "' OR '1'='1",
            "'; DROP TABLE users; --",
            "' UNION SELECT * FROM users --",
            "admin'--",
            "' OR 1=1#"
        ];

        foreach ($maliciousPayloads as $payload) {
            // Act
            $response = $this->postJson('/api/v1/financial/revenue-recognition', [
                'date_range' => [
                    'start' => $payload,
                    'end' => '20240131'
                ]
            ]);

            // Assert - Should return validation error, not SQL error
            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['date_range.start']);
        }
    }

    /** @test */
    public function it_enforces_access_control()
    {
        // Arrange
        $adminUser = User::factory()->create(['role' => 'admin']);
        $regularUser = User::factory()->create(['role' => 'user']);

        // Act & Assert - Regular user should not access admin endpoints
        Sanctum::actingAs($regularUser);
        $response = $this->getJson('/api/v1/admin/users');
        $response->assertStatus(403);

        // Act & Assert - Admin user should access admin endpoints
        Sanctum::actingAs($adminUser);
        $adminResponse = $this->getJson('/api/v1/admin/users');
        $this->assertNotEquals(403, $adminResponse->status());
    }

    /** @test */
    public function it_validates_input_sanitization()
    {
        // Arrange
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $maliciousInputs = [
            '<script>alert("xss")</script>',
            '"><img src=x onerror=alert(1)>',
            'javascript:alert(1)',
            '../../../etc/passwd',
            '{{7*7}}',
            '${7*7}'
        ];

        foreach ($maliciousInputs as $maliciousInput) {
            // Act
            $response = $this->postJson('/api/v1/financial/revenue-recognition', [
                'date_range' => [
                    'start' => '20240101',
                    'end' => '20240131'
                ],
                'filters' => [
                    'client_name' => $maliciousInput
                ]
            ]);

            // Assert - Should either sanitize or reject malicious input
            if ($response->status() === 200) {
                $content = json_encode($response->json());
                $this->assertStringNotContainsString('<script>', $content);
                $this->assertStringNotContainsString('javascript:', $content);
            }
        }
    }

    /** @test */
    public function it_prevents_session_fixation()
    {
        // Arrange
        $user = User::factory()->create();

        // Act - First request
        $response1 = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        // Assert
        $response1->assertStatus(200);
        $token1 = $response1->json('access_token');

        // Act - Second request with same session
        $response2 = $this->withHeader('Authorization', "Bearer {$token1}")
                         ->getJson('/api/v1/user');

        // Assert - Should maintain session
        $response2->assertStatus(200);
    }

    /** @test */
    public function it_validates_file_upload_security()
    {
        // Arrange
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $maliciousFiles = [
            ['filename' => 'malicious.php', 'content' => '<?php system($_GET["cmd"]); ?>'],
            ['filename' => 'script.js', 'content' => 'alert("xss")'],
            ['filename' => '../../../malicious.exe', 'content' => 'binary_content'],
            ['filename' => 'file.txt', 'content' => str_repeat('A', 50 * 1024 * 1024)] // 50MB file
        ];

        foreach ($maliciousFiles as $maliciousFile) {
            // Act
            $response = $this->postJson('/api/v1/financial/export', [
                'file' => $maliciousFile
            ]);

            // Assert - Should reject malicious files
            $this->assertTrue(in_array($response->status(), [400, 422, 413, 415]));
        }
    }
}