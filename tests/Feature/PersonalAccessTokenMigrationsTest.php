<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PersonalAccessTokenMigrationsTest extends TestCase
{
    private const MIGRATIONS = [
        '2026_09_25_035911_create_personal_access_tokens_table.php',
        '2026_09_25_060814_create_personal_access_tokens_table.php',
        '2026_10_01_130557_create_personal_access_tokens_table.php',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
    }

    public function test_duplicate_migrations_skip_an_existing_token_table_and_preserve_records(): void
    {
        $this->createTokenTable();
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id' => 1,
            'name' => 'Existing token',
            'token' => str_repeat('a', 64),
            'abilities' => '["*"]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->runTokenMigrations();

        $this->assertSame(1, DB::table('personal_access_tokens')->count());
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Existing token']);
        $this->assertSame(3, DB::table('migrations')->whereIn(
            'migration',
            array_map(fn (string $file) => pathinfo($file, PATHINFO_FILENAME), self::MIGRATIONS)
        )->count());
    }

    public function test_duplicate_migrations_create_the_token_table_once_on_a_fresh_database(): void
    {
        $this->runTokenMigrations();

        $this->assertTrue(Schema::hasTable('personal_access_tokens'));
        $this->assertTrue(Schema::hasColumns('personal_access_tokens', [
            'id',
            'tokenable_type',
            'tokenable_id',
            'name',
            'token',
            'abilities',
            'expires_at',
        ]));
        $this->assertSame(3, DB::table('migrations')->whereIn(
            'migration',
            array_map(fn (string $file) => pathinfo($file, PATHINFO_FILENAME), self::MIGRATIONS)
        )->count());
    }

    private function createTokenTable(): void
    {
        Schema::create('personal_access_tokens', function ($table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    private function runTokenMigrations(): void
    {
        foreach (self::MIGRATIONS as $migration) {
            Artisan::call('migrate', [
                '--path' => database_path("migrations/{$migration}"),
                '--realpath' => true,
                '--force' => true,
            ]);
        }
    }
}
