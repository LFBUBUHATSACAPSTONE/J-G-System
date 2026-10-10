<?php

namespace Tests\Feature;

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
}
