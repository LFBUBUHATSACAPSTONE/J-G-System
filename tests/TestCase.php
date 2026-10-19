<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Persist an admin account (role 'admin') and sign it in. Admin routes are gated by
     * ['auth', 'role:admin'], so every admin-area test needs one.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function actingAsAdmin(array $attributes = []): static
    {
        return $this->actingAs(User::factory()->admin()->create($attributes));
    }
}
