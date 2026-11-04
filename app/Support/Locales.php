<?php

namespace App\Support;

/**
 * The languages a member can pick under Settings → Language, keyed by the label
 * stored on users.language (the native name — the same value the mobile app
 * sends) and mapped to the locale code whose lang/{code}.json holds the UI copy.
 *
 * English is the default for everyone: a null/unknown users.language resolves
 * to 'en', and every untranslated string falls back to its English key.
 */
class Locales
{
    public const DEFAULT_LABEL = 'English';

    public const DEFAULT_CODE = 'en';

    /** @var array<string, string> label => locale code */
    public const MAP = [
        'English' => 'en',
        'Français' => 'fr',
        'Español' => 'es',
        'Português' => 'pt',
        'Deutsch' => 'de',
        'Nederlands' => 'nl',
        'Italiano' => 'it',
        '한국어' => 'ko',
        '中文' => 'zh',
        'हिन्दी' => 'hi',
        '日本語' => 'ja',
    ];

    /**
     * @return list<string>
     */
    public static function labels(): array
    {
        return array_keys(self::MAP);
    }

    public static function codeFor(?string $label): string
    {
        return self::MAP[$label ?? ''] ?? self::DEFAULT_CODE;
    }

    /**
     * The JSON translation table for a locale (empty for English, whose keys
     * are the strings themselves).
     *
     * @return array<string, string>
     */
    public static function translations(string $code): array
    {
        if ($code === self::DEFAULT_CODE) {
            return [];
        }

        static $cache = [];

        if (! array_key_exists($code, $cache)) {
            $path = lang_path("{$code}.json");
            $cache[$code] = is_file($path) ? (json_decode((string) file_get_contents($path), true) ?: []) : [];
        }

        return $cache[$code];
    }
}
