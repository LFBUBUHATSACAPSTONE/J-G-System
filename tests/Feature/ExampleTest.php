<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The home page is not a page of its own: it sends whoever is signed in to their own home.
     * A guest lands on the landing page, a customer on the landing page as well, and an admin on
     * the admin dashboard (see App\Services\Auth\CurrentAccount).
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertRedirect(route('user.landing'));
    }
}
