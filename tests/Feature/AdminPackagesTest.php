<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPackagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('packages', function (Blueprint $table) {
            $table->string('id', 120)->primary();
            $table->string('name', 100)->unique();
            $table->unsignedInteger('price');
            $table->boolean('available')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('features');
            $table->timestamps();
        });

        // The equipment of a package: one row per equipment line (row_key) of
        // config/package-equipment.php. Same shape as the migration.
        Schema::create('package_equipment', function (Blueprint $table) {
            $table->id();
            $table->string('package_id', 120);
            $table->string('row_key', 100);
            $table->string('value', 255);
            $table->timestamps();

            $table->unique(['package_id', 'row_key']);
            $table->foreign('package_id')->references('id')->on('packages')->cascadeOnDelete();
        });

        // Admin routes are gated by ['auth', 'role:admin']. This file builds its own minimal
        // schema (no users table), so sign in with an in-memory admin — the package endpoints
        // never read the user row itself.
        $this->actingAs(User::factory()->admin()->make());
    }

    public function test_admin_can_create_update_list_and_change_package_availability(): void
    {
        $created = $this->postJson(route('admin.packages.store'), [
            'name' => 'New Audio Package',
            'price' => 12000,
            'features' => "  • Clear sound\n\n- Event lighting ",
        ])->assertCreated()
            ->assertJsonPath('data.id', 'new-audio-package')
            ->assertJsonPath('data.features.0', 'Clear sound')
            ->assertJsonPath('data.features.1', 'Event lighting');

        $id = $created->json('data.id');

        $this->patchJson(route('admin.packages.update', ['package' => $id]), [
            'name' => 'New Audio Package Plus',
            'price' => 14000,
            'features' => "Premium sound\nLighting",
        ])->assertOk()
            ->assertJsonPath('data.name', 'New Audio Package Plus')
            ->assertJsonPath('data.price', 14000);

        $this->patchJson(route('admin.packages.availability', ['package' => $id]), [
            'availability' => 'unavailable',
        ])->assertOk()
            ->assertJsonPath('data.available', false);

        $this->getJson(route('admin.packages.data'))->assertOk()
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.available', false);
    }

    public function test_create_returns_json_validation_errors(): void
    {
        $this->post(route('admin.packages.store'), [
            'name' => '',
            'price' => 0,
            'features' => " \n • ",
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'price', 'features']);
    }

    public function test_missing_package_returns_json_not_found(): void
    {
        $this->patch(route('admin.packages.availability', ['package' => 'not-here']), [
            'availability' => 'unavailable',
        ])->assertNotFound()
            ->assertExactJson(['message' => 'Package not found.']);
    }

    public function test_equipment_of_a_new_package_is_saved_and_returned_as_json(): void
    {
        $created = $this->postJson(route('admin.packages.store'), [
            'name' => 'Sound Package',
            'price' => 12000,
            'features' => "Clear sound\nEvent lighting",
            'equipment' => [
                'audio_system' => 'Premium',
                'main_speaker' => '2 Units',
                'wireless_mics' => 'yes',
            ],
        ])->assertCreated()
            ->assertJsonPath('data.equipment.audio_system', 'Premium')
            ->assertJsonPath('data.equipment.main_speaker', '2 Units')
            // Every spelling of "yes" is saved as "Yes", so the comparison table shows a check.
            ->assertJsonPath('data.equipment.wireless_mics', 'Yes');

        $id = $created->json('data.id');

        $this->assertDatabaseHas('package_equipment', [
            'package_id' => $id,
            'row_key' => 'audio_system',
            'value' => 'Premium',
        ]);
        $this->assertDatabaseHas('package_equipment', [
            'package_id' => $id,
            'row_key' => 'main_speaker',
            'value' => '2 Units',
        ]);
    }

    public function test_equipment_of_a_package_can_be_edited(): void
    {
        $id = $this->postJson(route('admin.packages.store'), [
            'name' => 'Sound Package',
            'price' => 12000,
            'features' => 'Clear sound',
            'equipment' => [
                'audio_system' => 'Premium',
                'main_speaker' => '2 Units',
            ],
        ])->assertCreated()->json('data.id');

        $this->patchJson(route('admin.packages.update', ['package' => $id]), [
            'name' => 'Sound Package',
            'price' => 12000,
            'features' => 'Clear sound',
            'equipment' => [
                'audio_system' => 'Basic',
            ],
        ])->assertOk()
            ->assertJsonPath('data.equipment.audio_system', 'Basic')
            ->assertJsonPath('data.equipment.main_speaker', null);

        // The line the admin cleared is gone; the one they typed is stored.
        $this->assertDatabaseMissing('package_equipment', [
            'package_id' => $id,
            'row_key' => 'main_speaker',
        ]);
        $this->assertDatabaseHas('package_equipment', [
            'package_id' => $id,
            'row_key' => 'audio_system',
            'value' => 'Basic',
        ]);
    }

    public function test_an_update_without_equipment_keeps_the_saved_equipment(): void
    {
        $id = $this->postJson(route('admin.packages.store'), [
            'name' => 'Sound Package',
            'price' => 12000,
            'features' => 'Clear sound',
            'equipment' => ['audio_system' => 'Premium'],
        ])->assertCreated()->json('data.id');

        $this->patchJson(route('admin.packages.update', ['package' => $id]), [
            'name' => 'Sound Package Renamed',
            'price' => 13000,
            'features' => 'Clear sound',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Sound Package Renamed')
            ->assertJsonPath('data.equipment.audio_system', 'Premium');

        $this->assertDatabaseHas('package_equipment', [
            'package_id' => $id,
            'row_key' => 'audio_system',
            'value' => 'Premium',
        ]);
    }

    public function test_invalid_equipment_comes_back_as_a_json_validation_error(): void
    {
        $this->postJson(route('admin.packages.store'), [
            'name' => 'Sound Package',
            'price' => 12000,
            'features' => 'Clear sound',
            'equipment' => ['main_speaker' => 'No'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['equipment.main_speaker']);

        $this->postJson(route('admin.packages.store'), [
            'name' => 'Sound Package',
            'price' => 12000,
            'features' => 'Clear sound',
            'equipment' => ['main_speaker' => str_repeat('a', 61)],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['equipment.main_speaker']);

        // Nothing was saved for the rejected package.
        $this->assertDatabaseCount('package_equipment', 0);
    }

    public function test_the_admin_page_hands_the_saved_equipment_to_the_modal(): void
    {
        $id = $this->postJson(route('admin.packages.store'), [
            'name' => 'Sound Package',
            'price' => 12000,
            'features' => 'Clear sound',
            'equipment' => ['mixer' => 'Mackie DL16SE'],
        ])->assertCreated()->json('data.id');

        $this->get(route('admin.packages'))
            ->assertOk()
            ->assertSee('Mackie DL16SE');
    }
}
