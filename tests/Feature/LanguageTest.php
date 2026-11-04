<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_default_to_english(): void
    {
        $user = User::factory()->create(['language' => null]);

        $this->actingAs($user)
            ->get(route('settings'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'en')
                ->where('language', 'English')
                ->where('translations', []));
    }

    public function test_member_can_switch_language_and_the_ui_follows(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('settings.language'), ['language' => 'Français'])
            ->assertRedirect();

        $this->assertSame('Français', $user->fresh()->language);
        $this->assertSame('Français', UserPreference::forUser($user)->language);

        $this->actingAs($user->fresh())
            ->get(route('settings'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'fr')
                ->where('language', 'Français')
                ->where('translations', Locales::translations('fr')));
    }

    public function test_unknown_languages_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('settings.language'), ['language' => 'Klingon'])
            ->assertSessionHasErrors('language');

        $this->assertNull($user->fresh()->language);
    }

    public function test_every_locale_has_a_translation_file_for_every_key(): void
    {
        $english = null;

        foreach (Locales::MAP as $code) {
            if ($code === Locales::DEFAULT_CODE) {
                continue;
            }

            $path = lang_path("{$code}.json");
            $this->assertFileExists($path);

            $keys = array_keys(json_decode(file_get_contents($path), true));
            $english ??= $keys;
            $this->assertEqualsCanonicalizing($english, $keys, "lang/{$code}.json is out of sync — run node scripts/i18n-extract.mjs --write");
        }
    }
}
