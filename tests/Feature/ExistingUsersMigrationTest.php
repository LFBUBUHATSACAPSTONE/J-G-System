<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExistingUsersMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
    }

    public function test_migration_preserves_an_existing_users_table_and_creates_missing_companion_tables(): void
    {
        Schema::create('users', function ($table): void {
            $table->id();
            $table->string('first_name');
        });
        DB::table('users')->insert(['id' => 17, 'first_name' => 'Existing']);

        $this->runUsersMigration();

        $this->assertDatabaseHas('users', ['id' => 17, 'first_name' => 'Existing']);
        $this->assertTrue(Schema::hasTable('password_reset_tokens'));
        $this->assertTrue(Schema::hasTable('sessions'));
    }

    public function test_migration_creates_users_table_without_duplicate_unique_indexes_on_a_fresh_database(): void
    {
        $this->runUsersMigration();

        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasColumn('users', 'email'));
        $this->assertTrue(Schema::hasColumn('users', 'phone'));
    }

    private function runUsersMigration(): void
    {
        Artisan::call('migrate', [
            '--path' => database_path('migrations/0001_01_01_000000_create_users_table.php'),
            '--realpath' => true,
            '--force' => true,
        ]);
    }
}
