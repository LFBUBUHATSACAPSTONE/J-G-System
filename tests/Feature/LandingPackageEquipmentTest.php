<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The equipment the admin types in Admin > Packages is what the clients read on the landing
 * page's Package Comparison Table. These tests run the real migrations (so the packages and
 * their equipment are seeded from the config), edit a package as the admin and check that the
 * landing page shows the new values.
 */
class LandingPackageEquipmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Artisan::call('migrate', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_the_seeded_equipment_is_stored_and_shown_on_the_landing_page(): void
    {
        $this->assertDatabaseHas('package_equipment', [
            'package_id' => 'budget-wedding',
            'row_key' => 'mixer',
            'value' => 'Mackie ProFX16',
        ]);

        $this->get(route('user.landing'))
            ->assertOk()
            ->assertSee('Mackie ProFX16');
    }

    public function test_equipment_saved_by_the_admin_shows_up_for_the_clients(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->patchJson(route('admin.packages.update', ['package' => 'budget-wedding']), [
            'name' => 'Budget Wedding',
            'price' => 18000,
            'features' => 'Best for simple weddings',
            'equipment' => [
                'mixer' => 'Mackie DL16SE',
                'led_wall' => '9x12 GTOP',
            ],
        ])->assertOk()
            ->assertJsonPath('data.equipment.mixer', 'Mackie DL16SE');

        $this->actingAs(User::factory()->make());

        $this->get(route('user.landing'))
            ->assertOk()
            ->assertSee('Mackie DL16SE')
            ->assertSee('9x12 GTOP')
            // The value the admin replaced is gone.
            ->assertDontSee('9x12 Lightlink');
    }

    public function test_the_package_of_a_client_carries_its_equipment(): void
    {
        $package = Package::query()->with('equipment')->find('luxe-lite');

        $this->assertSame('Mackie DL16SE', $package->toPublicArray()['equipment']['mixer']);
        $this->assertSame('4 Units + Subwoofer', $package->toPublicArray()['equipment']['main_speaker']);
    }
}
