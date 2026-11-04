// Collects every translatable string — t('...')/tk('...') calls in resources/js,
// __('...') calls in app/, and the QuizCatalog names — and syncs them into
// lang/{locale}.json.
//
//   node scripts/i18n-extract.mjs           report missing/unused keys per locale
//   node scripts/i18n-extract.mjs --write   also add missing keys (English copy as a
//                                           placeholder) and drop unused ones
//
// Keys are the English text itself (see resources/js/lib/i18n.js), so a key left
// with its English value just renders in English until someone translates it.

import { readFileSync, readdirSync, statSync, writeFileSync, existsSync } from 'node:fs';
import { join, extname } from 'node:path';
import { execFileSync } from 'node:child_process';

const ROOT = new URL('..', import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1');
const LOCALES = ['fr', 'es', 'pt', 'de', 'nl', 'it', 'ko', 'zh', 'hi', 'ja'];
const write = process.argv.includes('--write');

function walk(dir, exts, out = []) {
    for (const name of readdirSync(dir)) {
        const path = join(dir, name);
        if (statSync(path).isDirectory()) walk(path, exts, out);
        else if (exts.includes(extname(name))) out.push(path);
    }
    return out;
}

// A JS/PHP string literal: '...', "..." or `...` (template literals without ${}).
const LITERAL = String.raw`'((?:[^'\\]|\\.)*)'|"((?:[^"\\]|\\.)*)"|\`((?:[^\`\\$]|\\.)*)\``;
const JS_CALL = new RegExp(String.raw`(?<![\w.])tk?\(\s*(?:${LITERAL})`, 'g');
const PHP_CALL = new RegExp(String.raw`(?<![\w>])__\(\s*(?:${LITERAL})`, 'g');

function unescape(raw) {
    return raw.replace(/\\(.)/g, (_, c) => ({ n: '\n', t: '\t' })[c] ?? c);
}

const keys = new Set();
const collect = (files, pattern) => {
    for (const file of files) {
        for (const m of readFileSync(file, 'utf8').matchAll(pattern)) {
            const key = unescape(m[1] ?? m[2] ?? m[3]);
            // 'auth.password' style keys live in lang/{locale}/*.php, not the JSON tables.
            if (/^[a-z_]+\.[a-z_.]+$/.test(key)) continue;
            keys.add(key);
        }
    }
};

collect(walk(join(ROOT, 'resources/js'), ['.js', '.jsx']), JS_CALL);
collect(walk(join(ROOT, 'app'), ['.php']), PHP_CALL);

// Data-driven labels the UI renders with t(value): quiz categories, quiz titles,
// instruments and sound types from App\Support\QuizCatalog.
const catalog = execFileSync('php', ['-r', `
    require 'vendor/autoload.php';
    use App\\Support\\QuizCatalog;
    $names = [];
    foreach (QuizCatalog::all() as $category => $titles) {
        $names[] = $category;
        foreach ($titles as $title) {
            $names[] = $title;
            array_push($names, ...QuizCatalog::soundsFor($category, $title));
        }
    }
    echo json_encode(array_values(array_unique([...$names, ...QuizCatalog::allInstruments()])));
`], { cwd: ROOT, encoding: 'utf8' });
for (const name of JSON.parse(catalog)) keys.add(name);

// Answer labels (chord qualities, inversions, intervals, modes…) live as plain
// values in quizAnswerPresets.js — they're also stored/compared server-side, so
// they're translated only at display with t(value). Collect every word-like
// literal there; note names and solfège syllables are skipped.
const SOLFEGE = new Set(['Doh', 'Reh', 'Mi', 'Fah', 'Sol', 'Lah', 'Ti', 'Do', 'Re', 'Fa', 'So', 'La', 'Di', 'Ri', 'Fi', 'Si', 'Li', 'Ra', 'Me', 'Se', 'Le', 'Te']);
const isSolfege = (s) => s.split(/[\s\-–,/]+/).filter(Boolean).every((part) => SOLFEGE.has(part));
const presets = readFileSync(join(ROOT, 'resources/js/lib/quizAnswerPresets.js'), 'utf8')
    .replace(/\/\/.*$/gm, '');
for (const m of presets.matchAll(/'([A-Z][^'\n]*[a-z][^'\n]*)'/g)) {
    if (!isSolfege(m[1]) && !/^[A-G][#♯b♭]?(\/|$)/.test(m[1])) keys.add(m[1]);
}

const sorted = [...keys].sort((a, b) => a.localeCompare(b));
console.log(`${sorted.length} translatable strings`);

for (const locale of LOCALES) {
    const path = join(ROOT, 'lang', `${locale}.json`);
    const table = existsSync(path) ? JSON.parse(readFileSync(path, 'utf8')) : {};
    const missing = sorted.filter((k) => !(k in table));
    const unused = Object.keys(table).filter((k) => !keys.has(k));
    const untranslated = sorted.filter((k) => table[k] === k && /[a-z]{3}/i.test(k));

    // A translation must keep every :placeholder of its key, or the value
    // silently never shows up.
    const broken = sorted.filter((k) => {
        if (!(k in table)) return false;
        const need = k.match(/:[A-Za-z]\w*/g) ?? [];
        return need.some((p) => !table[k].includes(p));
    });

    console.log(`${locale}: ${missing.length} missing, ${unused.length} unused, ${untranslated.length} same as English, ${broken.length} with broken placeholders`);
    for (const k of broken) console.log(`  ✗ ${JSON.stringify(k)} → ${JSON.stringify(table[k])}`);

    if (write) {
        const next = {};
        for (const k of sorted) next[k] = table[k] ?? k;
        writeFileSync(path, JSON.stringify(next, null, 4) + '\n');
    }
}
