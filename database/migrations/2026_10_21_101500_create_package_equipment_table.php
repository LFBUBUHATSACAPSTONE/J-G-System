<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The equipment of every package: one row per equipment line of config/package-equipment.php.
 *
 * The admin package modal (App\Http\Controllers\Admin\PackageController) saves what the admin
 * types here, and the landing page's Package Comparison Table reads it back, so the values the
 * clients see are the ones the admin typed instead of the ones hard-coded in the config file.
 *
 * The packages that already exist are seeded here from config('package-equipment.defaults'), so
 * a fresh migrate reproduces the values the comparison table used to print. insertOrIgnore()
 * keeps the rows that are already there: the migration never overwrites saved data and never
 * removes a row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_equipment', function (Blueprint $table) {
            $table->id();
            // packages.id is a string key, so this column has the same type and cascades with it.
            $table->string('package_id');
            // The equipment line this row belongs to (a row_key of config/package-equipment.php).
            $table->string('row_key', 100);
            // What the admin typed for that line ("2 Units", "Yes", "Mackie DL16SE", ...).
            $table->string('value', 255);
            $table->timestamps();

            // A package has one value per line.
            $table->unique(['package_id', 'row_key']);
            $table->foreign('package_id')->references('id')->on('packages')->cascadeOnDelete();
        });

        $this->seedFromConfig();
    }

    public function down(): void
    {
        Schema::dropIfExists('package_equipment');
    }

    /**
     * Copy the config defaults of the packages that are already in the database into the new
     * table. Only packages that exist are seeded, and only the lines the config knows, so
     * nothing is invented and nothing that is already saved is touched.
     */
    private function seedFromConfig(): void
    {
        $defaults = config('package-equipment.defaults', []);
        if (! is_array($defaults) || $defaults === []) {
            return;
        }

        $packageIds = DB::table('packages')->pluck('id')->all();
        $timestamp = now();
        $rows = [];

        foreach ($packageIds as $packageId) {
            foreach ($defaults[$packageId] ?? [] as $rowKey => $value) {
                $value = trim((string) $value);
                if ($value === '') {
                    continue;
                }

                $rows[] = [
                    'package_id' => $packageId,
                    'row_key' => $rowKey,
                    'value' => $value,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('package_equipment')->insertOrIgnore($rows);
        }
    }
};
