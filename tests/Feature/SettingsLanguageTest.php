<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_german_and_dutch_can_be_saved_and_loaded_in_settings(): void
    {
        foreach (['Deutsch', 'Nederlands'] as $language) {
            $user = User::factory()->create(['language' => 'English']);
            $this->assertContains($language, QuizCatalog::LANGUAGES);

            $this->actingAs($user)->from(route('settings'))
                ->patch(route('settings.language'), ['language' => $language])
                ->assertRedirect(route('settings'));

            $this->assertSame($language, $user->fresh()->language);
            $props = $this->get(route('settings'))->assertOk()->viewData('page')['props'];
            $this->assertSame($language, $props['language']);
        }
    }

    public function test_unsupported_language_does_not_replace_the_saved_language(): void
    {
        $user = User::factory()->create(['language' => 'Deutsch']);
        $this->actingAs($user)->patch(route('settings.language'), ['language' => 'invalid'])
            ->assertSessionHasErrors('language');
        $this->assertSame('Deutsch', $user->fresh()->language);
    }

    public function test_guests_cannot_update_a_language(): void
    {
        $this->patch(route('settings.language'), ['language' => 'Deutsch'])
            ->assertRedirect(route('login'));
    }
}
