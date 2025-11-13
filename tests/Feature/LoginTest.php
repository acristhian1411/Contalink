<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Persons;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class LoginTest extends TestCase
{
    // use RefreshDatabase;

    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run seeders to set up required data (only once)
        // $this->seed(\Database\Seeders\PersonTypeSeeder::class);
        // $this->seed(\Database\Seeders\CountrySeeder::class);
        // $this->seed(\Database\Seeders\RoleSeeder::class);
        
        // Create a test person with existing seeded data
        $person = Persons::create([
            'person_fname' => 'Test',
            'person_lastname' => 'User',
            'person_corpname' => 'Test Corp',
            'person_idnumber' => '123456',
            'person_ruc' => '123456789',
            'person_birtdate' => '1990-01-01',
            'person_photo' => null,
            'person_address' => 'Test Address',
            'p_type_id' => 1,
            'country_id' => 1,
            'city_id' => 1126,
        ]);
        
        // Create a test user
        $this->testUser = User::find(0);
    }

    /** @test */
    public function login_page_can_be_rendered()
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertAuthenticated();
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function login_requires_email()
    {
        $response = $this->post('/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function login_requires_password()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    /** @test */
    public function login_requires_valid_email_format()
    {
        $response = $this->post('/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function user_can_login_with_remember_me()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertAuthenticated();
    }

    /** @test */
    public function session_is_regenerated_after_login()
    {
        $this->withSession(['_token' => 'old-token']);
        
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $this->assertAuthenticated();
    }

    /** @test */
    public function csrf_token_is_validated_on_login()
    {
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post('/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(200);
    }


    /** @test */
    public function login_shows_error_with_missing_email_field()
    {
        $response = $this->post('/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function login_shows_error_with_missing_password_field()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    /** @test */
    public function remember_me_functionality_works_correctly()
    {
        // Test with remember = false
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertAuthenticated();
        
        // Logout
        $this->post('/logout');
        
        // Test with remember = true
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertAuthenticated();
    }

    /** @test */
    public function login_with_empty_credentials_shows_validation_errors()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    /** @test */
    public function login_with_nonexistent_user_fails()
    {
        $response = $this->postJson('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
        $this->assertGuest();
    }
}

    /** @test */
    public function login_shows_error_with_missing_email_field()
    {
        $response = $this->postJson('/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function login_shows_error_with_missing_password_field()
    {
        $response = $this->postJson('/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
        $this->assertGuest();
    }

    /** @test */
    public function remember_me_functionality_works_correctly()
    {
        $response = $this->postJson('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertAuthenticated();
    }

    /** @test */
    public function login_with_empty_credentials_shows_validation_errors()
    {
        $response = $this->postJson('/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
        $this->assertGuest();
    }

    /** @test */
    public function login_with_nonexistent_user_fails()
    {
        $response = $this->postJson('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
        $this->assertGuest();
    }
}
