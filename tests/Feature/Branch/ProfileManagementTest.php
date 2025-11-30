<?php

namespace Tests\Feature\Branch;

use App\Models\User;
use App\Models\Upload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function user_can_view_profile_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('branch.account.profile'));

        $response->assertStatus(200);
        $response->assertViewIs('branch.account.profile');
    }

    /** @test */
    public function user_can_update_profile_information()
    {
        $response = $this->actingAs($this->user)
            ->put(route('branch.account.profile.update'), [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'mobile' => '+254712345678',
                'address' => '123 Test Street, Nairobi',
            ]);

        $response->assertRedirect(route('branch.account.profile'));
        $response->assertSessionHas('success', 'Profile updated successfully!');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'mobile' => '+254712345678',
            'address' => '123 Test Street, Nairobi',
        ]);
    }

    /** @test */
    public function user_cannot_update_profile_with_invalid_data()
    {
        $response = $this->actingAs($this->user)
            ->put(route('branch.account.profile.update'), [
                'name' => '', // Required field
                'email' => 'invalid-email', // Invalid email
            ]);

        $response->assertSessionHasErrors(['name', 'email']);
    }

    /** @test */
    public function user_cannot_use_duplicate_email()
    {
        $otherUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('branch.account.profile.update'), [
                'name' => 'Test User',
                'email' => 'existing@example.com',
            ]);

        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function user_can_upload_profile_image()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('profile.jpg', 800, 800);

        $response = $this->actingAs($this->user)
            ->put(route('branch.account.profile.update'), [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'image' => $file,
            ]);

        $response->assertRedirect(route('branch.account.profile'));
        
        // Verify upload record was created
        $this->assertDatabaseHas('uploads', [
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function user_cannot_upload_invalid_image()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->actingAs($this->user)
            ->put(route('branch.account.profile.update'), [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'image' => $file,
            ]);

        $response->assertSessionHasErrors(['image']);
    }

    /** @test */
    public function user_cannot_upload_oversized_image()
    {
        Storage::fake('public');

        // Create a file larger than 2MB
        $file = UploadedFile::fake()->image('large.jpg')->size(3000);

        $response = $this->actingAs($this->user)
            ->put(route('branch.account.profile.update'), [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'image' => $file,
            ]);

        $response->assertSessionHasErrors(['image']);
    }

    /** @test */
    public function guest_cannot_access_profile_page()
    {
        $response = $this->get(route('branch.account.profile'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_update_profile()
    {
        $response = $this->put(route('branch.account.profile.update'), [
            'name' => 'Test',
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect(route('login'));
    }
}
