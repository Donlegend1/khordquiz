<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_privacy_policy_page_renders(): void
    {
        $this->get('/privacy-policy')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Legal')->where('page', 'privacy'));
    }

    public function test_terms_page_renders(): void
    {
        $this->get('/terms')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Legal')->where('page', 'terms'));
    }
}
