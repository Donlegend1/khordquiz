import { usePage } from '@inertiajs/react';

// UI translation for the member's chosen language (Settings → Language, stored on
// users.language). The server shares `translations` — the lang/{locale}.json table
// for that language — and `locale` on every Inertia response (HandleInertiaRequests).
//
// Keys are the English copy itself, so English needs no table and any string that
// hasn't been translated yet simply shows in English. Placeholders use Laravel's
// `:name` style:
//
//   const t = useTrans();
//   t('Welcome back, :name', { name: user.first_name })
//
// After adding or changing a t('...') string, run `node scripts/i18n-extract.mjs`
// to add the new key to every lang/*.json file, then translate it there.

// Marks a string in a module-level constant (where hooks can't run) as a
// translation key without translating it — render it later with t(value).
// The extractor collects tk('...') literals the same as t('...').
export const tk = (key) => key;

export function translate(translations, key, replacements = {}) {
    if (key === null || key === undefined) return key;

    let line = translations?.[key] || key;

    // Longest names first so :quiz never clobbers :quizzes (same as Laravel).
    const entries = Object.entries(replacements).sort(([a], [b]) => b.length - a.length);

    for (const [name, value] of entries) {
        line = line.split(`:${name}`).join(String(value));
    }

    return line;
}

export function useTrans() {
    const { translations } = usePage().props;

    return (key, replacements) => translate(translations, key, replacements);
}

// BCP 47 tag for Intl/toLocaleString, e.g. 'zh' → 'zh-CN'.
const INTL_LOCALES = { en: 'en-US', fr: 'fr-FR', es: 'es-ES', pt: 'pt-BR', de: 'de-DE', nl: 'nl-NL', it: 'it-IT', ko: 'ko-KR', zh: 'zh-CN', hi: 'hi-IN', ja: 'ja-JP' };

export function useLocale() {
    const { locale } = usePage().props;

    return INTL_LOCALES[locale] ?? 'en-US';
}
