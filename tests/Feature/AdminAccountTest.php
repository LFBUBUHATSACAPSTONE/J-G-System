<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Admin Account page (routes/admin/account.php, AccountController).
 *
 * PATCH answers JSON only (contract in resources/js/admin/account.js):
 *   200 {ok, message, admin{first_name, last_name, middle_initial, email, phone, avatar_url}}
 *   422 {message, errors}  |  429 {message}  |  401 {message}
 *
 * The account IS the signed-in user row: updates are written in place, nothing is deleted.
 */
class AdminAccountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_account_page_renders(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->get(route('admin.account'))
            ->assertOk();
    }

    public function test_patch_updates_profile_fields_without_a_current_password(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->patchJson(route('admin.account.update'), [
                'first_name' => '  Juan  ',
                'last_name' => 'Dela Cruz',
                'middle_initial' => 'b',
                'phone' => '09171234567',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('admin.first_name', 'Juan')
            ->assertJsonPath('admin.last_name', 'Dela Cruz')
            ->assertJsonPath('admin.middle_initial', 'B')
            ->assertJsonPath('admin.phone', '09171234567');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'middle_initial' => 'B',
            'phone' => '09171234567',
        ]);
    }

    public function test_patch_saves_a_full_middle_name_and_keeps_the_initial_in_sync(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->patchJson(route('admin.account.update'), [
                'middle_name' => '  Dela Cruz  ',
            ])
            ->assertOk()
            ->assertJsonPath('admin.middle_name', 'Dela Cruz')
            ->assertJsonPath('admin.middle_initial', 'D');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'middle_name' => 'Dela Cruz',
            'middle_initial' => 'D',
        ]);
    }

    public function test_saving_the_name_row_accepts_a_blank_middle_name(): void
    {
        $user = $this->admin(['middle_name' => 'Santos', 'middle_initial' => 'S']);

        $this->actingAs($user)
            ->patchJson(route('admin.account.update'), [
                'first_name' => 'Juan',
                'middle_name' => '',
                'last_name' => 'Dela Cruz',
            ])
            ->assertOk()
            ->assertJsonPath('admin.middle_name', null)
            ->assertJsonPath('admin.middle_initial', null);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'middle_name' => null,
            'middle_initial' => null,
        ]);
    }

    public function test_changing_the_email_requires_the_current_password(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->patchJson(route('admin.account.update'), [
                'email' => 'new@example.com',
                'current_password' => 'wrong-password',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.current_password.0', 'Your current password is incorrect.');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email]);
    }

    public function test_changing_the_email_with_the_current_password_saves_and_answers_json(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->patchJson(route('admin.account.update'), [
                'email' => 'new@example.com',
                'current_password' => 'secret-password',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('admin.email', 'new@example.com');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'new@example.com']);
    }

    public function test_changing_the_password_requires_the_current_password_and_hashes_the_new_one(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->patchJson(route('admin.account.update'), [
                'password' => 'BrandNewPass1!',
                'password_confirmation' => 'BrandNewPass1!',
                'current_password' => 'secret-password',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertTrue(Hash::check('BrandNewPass1!', $user->fresh()->password));
    }

    public function test_weak_password_is_rejected(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->patchJson(route('admin.account.update'), [
                'password' => 'short',
                'password_confirmation' => 'short',
                'current_password' => 'secret-password',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['password']]);

        $this->assertTrue(Hash::check('secret-password', $user->fresh()->password));
    }

    public function test_invalid_phone_and_duplicate_email_are_rejected(): void
    {
        $user = $this->admin();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($user)
            ->patchJson(route('admin.account.update'), [
                'phone' => '12345',
                'email' => 'taken@example.com',
                'current_password' => 'secret-password',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['phone', 'email']]);
    }

    public function test_guests_cannot_update_the_account(): void
    {
        $this->patchJson(route('admin.account.update'), [
            'first_name' => 'Nobody',
        ])
            ->assertStatus(401)
            ->assertJsonStructure(['message']);
    }

    public function test_repeated_wrong_current_passwords_are_rate_limited(): void
    {
        $user = $this->admin();

        foreach (range(1, 5) as $attempt) {
            $this->actingAs($user)
                ->patchJson(route('admin.account.update'), [
                    'email' => 'new@example.com',
                    'current_password' => "wrong-{$attempt}",
                ])
                ->assertStatus(422);
        }

        $this->actingAs($user)
            ->patchJson(route('admin.account.update'), [
                'email' => 'new@example.com',
                'current_password' => 'wrong-6',
            ])
            ->assertStatus(429)
            ->assertJsonStructure(['message']);
    }

    public function test_avatar_upload_is_stored_and_replaces_the_previous_photo(): void
    {
        Storage::fake('public');

        $user = $this->admin(['avatar_path' => 'avatars/old.png']);
        Storage::disk('public')->put('avatars/old.png', 'old-bytes');

        $this->actingAs($user)
            ->patchJson(route('admin.account.update'), [
                'avatar' => UploadedFile::fake()->create('photo.png', 20, 'image/png'),
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $path = $user->fresh()->avatar_path;

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertMissing('avatars/old.png');
        $this->assertSame(asset('storage/'.$path), $this->actingAs($user->fresh())
            ->getJson(route('admin.account'))
            ->assertOk()
            ->json('admin.avatar_url'));
    }

    private function admin(array $overrides = []): User
    {
        return User::factory()->admin()->create(array_merge([
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ], $overrides));
    }
}
