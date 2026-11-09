# Extensions category split — handoff

## Status: category split is DONE

Codex already completed this part — confirmed in the live DB and code:
- `app/Models/Quiz::CATEGORIES` now has `Chord Tone Recognition`,
  `Major Chord Extensions`, `Minor Chord Extensions`,
  `Dominant Chord Extensions` as standalone entries (the old `Extensions`
  category is gone from the list).
- Every `Quiz` row's `category` column was updated to match.
- `resources/js/lib/quizAnswerPresets.js`'s `MULTI_ANSWER_PRESETS` was
  re-keyed to the new category names, and pre-emptively added
  `Level 1/2/3` title entries for Minor and Dominant (ahead of those
  actually being built — harmless, just unused until those quizzes exist).

One open question worth a decision from the user: `Chord naming` (the old
standalone quiz, untouched this session) ended up filed under
`Dominant Chord Extensions` category rather than getting its own category
or staying somewhere else. Confirm with the user whether that's
intentional or should move.

What's below is now mostly reference material (the original ask, and the
Major/Minor/Dominant chord-tone tables) rather than an active to-do for
the category split itself — the real remaining work is building Minor and
Dominant.

## Original context (for reference)

Everything described below used to live inside one quiz category called
`Extensions` (`app/Models/Quiz::CATEGORIES`,
`app/Support/QuizCatalog::subcategories()`). The ask was to split it into
separate top-level categories, the same way `7th Degree Chords`,
`9th Degree Chords`, etc. each already stand on their own rather than
being nested under something broader — see Status above, this is done.

Contents at the time of the split:
- `Chord Tone Recognition #1` through `#6` — built, working.
- `Major Chord Extentions · Level 1/2/3` — built, working (note the
  "Extentions" spelling is intentional/existing, not a typo to fix).
- `Minor Chord Extentions` — chord-tone table confirmed, nothing built yet.
- `Dominant Chord Extentions` — not started at all.
- `Chord naming` — pre-existing quiz, untouched this round.

## What's wanted

Split into standalone top-level categories, e.g.:
- `Chord Tone Recognition` (own category, holding #1-#6 or however many
  end up existing)
- `Major Chord Extensions` (own category — expect this to keep growing
  past 3 levels; the user mentioned possibly adding a level built in a
  single key plus a level spanning all 12 keys, structure still evolving)
- `Minor Chord Extensions` (own category, not yet built)
- `Dominant Chord Extensions` (own category, not yet built)
- `Chord naming` (own category, or folded elsewhere — ask the user, not
  yet decided)

## Where to make the category-split change

1. `app/Models/Quiz::CATEGORIES` (~line 33) — add the new top-level
   category names; remove `Extensions` once everything has moved out of
   it (or keep it if something stays behind).
2. `app/Support/QuizCatalog::subcategories()` (~line 101, the
   `'Extensions' => [...]` array) — move each quiz title out into its own
   new category's entry.
3. Every affected `Quiz` row currently has `category = 'Extensions'` in
   the DB — needs a one-off script to update it per quiz to the new
   category name, not a migration. Follow the existing convention in
   `scripts/audio/build_*.php` (plain PHP, `require`'d via
   `php artisan tinker --execute="require base_path('scripts/audio/....php');"`,
   not a Laravel seeder — seeders are for generic dev fixtures only in
   this project, see CLAUDE.md-adjacent convention already established).
4. `app/Support/QuizCatalog::soundsFor()` (~line 215) — has
   `'Extensions' => $chordInstruments`; needs an equivalent entry for
   each new category name (reuse the same `$chordInstruments` value
   unless told otherwise).

## Places keyed by exact quiz TITLE, not category — leave alone unless titles themselves change

These don't need touching just because the category changes:
- `resources/js/lib/quizAnswerPresets.js` → `MULTI_ANSWER_PRESETS` is
  keyed by `(category, title)` pairs — **this one DOES need its outer
  key updated** if the category name changes (currently keyed under
  `Extensions`), even though the title stays the same.
- `app/Http/Controllers/Admin/AudioQuizController.php` →
  `CHORD_TONE_QUIZ_TITLES`, `MAJOR_CHORD_EXTENSION_QUIZ_TITLES`, and the
  `$multiExtensionLevels` array (~line 340) are keyed by quiz **title**
  only — no change needed for a pure category move.
- `app/Http/Controllers/QuizController.php` → same title-based dispatch
  for live-mix resolution (`ChordToneRecognitionSamples`/
  `MajorChordExtensionSamples`) — title-keyed, category-independent, no
  change needed for a pure category move.

## Still outstanding — independent of the category split, can happen in either order

### The 7-level pattern — applies to ALL THREE of Major, Minor, and Dominant

Same shape for every one of the three chord-extension categories: each
has 3 chord-tone groups (see each category's own section below for the
actual chords). For each category:
- Levels 1-6 = each of its 3 groups, done twice — once in the key of F
  only, once across all 12 keys (group 1 key-of-F, group 1 all-keys,
  group 2 key-of-F, group 2 all-keys, group 3 key-of-F, group 3 all-keys,
  in that order).
- **Level 7 = "General"** — every chord from all 3 groups combined, all
  12 keys. This is the same "combine everything into one review pool"
  idea already used elsewhere in this project (e.g. Secondary 9th/11th
  Chords' own "General" level), just numbered as Level 7 here instead of
  being its own untitled quiz.

So: `Major Chord Extentions · Level 7`, `Minor Chord Extentions · Level 7`,
`Dominant Chord Extentions · Level 7` — each the full combined pool for
its category. Minor and Dominant need their own Levels 1-6 built first
(see their sections below for the group breakdown); Major's Levels 1-6
are already built (see immediately below) and just need Level 7 added
on top.

### Major Chord Extensions (reference — already built, not a to-do)

Spelled out here so Codex has the same level of detail as Minor's, as a
reference for the exact pattern Minor/Dominant should follow. Chord-tone
groups (major triad base `[0, 4, 7]`):

**Group 1** (6 chords, triad + color tone, no 7th):
| Chord | Tones | In C |
|---|---|---|
| Major 6 | 1, 3, 5, 6 | C E G A |
| Major 7 | 1, 3, 5, 7 | C E G B |
| Major add ♭9 | 1, 3, 5, ♭9 | C E G D♭ |
| Major add 9 | 1, 3, 5, 9 | C E G D |
| Major add ♯11 | 1, 3, 5, ♯11 | C E G F♯ |
| Major add 9(♯11) | 1, 3, 5, 9, ♯11 | C E G D F♯ |

**Group 2** (6 chords, 6-chords):
| Chord | Tones | In C |
|---|---|---|
| Major 6/9 | 1, 3, 5, 6, 9 | C E G A D |
| Major 6(♯11) | 1, 3, 5, 6, ♯11 | C E G A F♯ |
| Major 6/9(♯11) | 1, 3, 5, 6, 9, ♯11 | C E G A D F♯ |
| Major 6(♭9, ♯11) | 1, 3, 5, 6, ♭9, ♯11 | C E G A D♭ F♯ |
| Major 6(♭9, 11) | 1, 3, 5, 6, ♭9, 11 | C E G A D♭ F |
| Major 6(9, 11) | 1, 3, 5, 6, 9, 11 | C E G A D F |

**Group 3** (9 chords, 7-chords with extensions):
| Chord | Tones | In C |
|---|---|---|
| Major 7 | 1, 3, 5, 7 | C E G B |
| Major 9 | 1, 3, 5, 7, 9 | C E G B D |
| Major 7(♯11) | 1, 3, 5, 7, ♯11 | C E G B F♯ |
| Major 7(13) | 1, 3, 5, 7, 13 | C E G B A |
| Major 9(♯11) | 1, 3, 5, 7, 9, ♯11 | C E G B D F♯ |
| Major 13(9, ♯11) | 1, 3, 5, 7, 9, ♯11, 13 | C E G B D F♯ A |
| Major 7(♯11, 13) | 1, 3, 5, 7, ♯11, 13 | C E G B F♯ A |
| Major 7(9, 13) | 1, 3, 5, 7, 9, 13 | C E G B D A |
| Major 7(♭9, ♯11) | 1, 3, 5, 7, ♭9, ♯11 | C E G B D♭ F♯ |

Note "Major 7" is deliberately listed in both Group 1 and Group 3 — that's
from the user's own original lists, not a mistake, and both occurrences
carry through into the combined pools below.

### Quiz-level structure — SUPERSEDED, rebuild to 6 levels

The 3-level combined structure below was built, then the user asked for
something different immediately after — **do not build the 3-level
version described in this paragraph; it's kept only as history.** ~~`Major
Chord Extentions · Level 1` = Group 1 + Group 2 combined (12 qualities);
`Level 2` = Group 3 only (9 qualities); `Level 3` = Groups 1+2+3 combined
(21 qualities).~~

**Build this instead — 6 levels, single-key-then-all-keys per group:**
- `Major Chord Extentions · Level 1` = Group 1 (6 "add" chords), **key of
  F only**.
- `Major Chord Extentions · Level 2` = Group 1, same 6 chords, **all 12
  keys**.
- `Major Chord Extentions · Level 3` = Group 2 (6 "6-chords"), **key of F
  only**.
- `Major Chord Extentions · Level 4` = Group 2, same 6 chords, **all 12
  keys**.
- `Major Chord Extentions · Level 5` = Group 3 (9 "7-chord extensions"),
  **key of F only**.
- `Major Chord Extentions · Level 6` = Group 3, same 9 chords, **all 12
  keys**.

So there are now 6 DB quizzes, not 3 — rename/repurpose the existing 3
and create 3 new ones (same pattern used earlier in this project: rename
quiz 93 in place, `Quiz::firstOrCreate` for the new ones, update
`QuizCatalog::subcategories()`'s `Major Chord Extensions` entry to list
all 6 titles in order).

The underlying audio-path groupings (`major-chord-extensions/level1/`,
`level2/`, `level3/` in storage/the resolver) still correspond to the
original Group 1/2/3 above (NOT the new 6 quiz levels — same fixed
namespace, decoupled from which DB quiz references it, same "level" word
used for two different things, don't conflate them). The F-only levels
(1/3/5) just filter `MCE_KEYS` down to `['f' => 'F']` when building that
quiz's pool; the all-12-keys levels (2/4/6) use the full key list, same
as before. Rework
`scripts/audio/build_major_chord_extensions.php`'s `MCE_QUIZ_LEVEL_SOURCES`
map (or replace it with a new map) to express this 6-entry
quiz-level → (source group, key scope) structure.

5 variants per chord via the doubling/open-voicing/gap-filling rules
(see Minor's section below for the exact rule text — identical rules,
just triad base `[0, 4, 7]` instead of `[0, 3, 7]`). Every chord gets 5
except Major add ♯11, which only yields 2 distinct valid voicings (4
tones, 11-semitone gap, not enough room under the ≤3-doubled-tones cap to
reach 5) — accepted as-is, not a bug.

**Still to add: `Major Chord Extentions · Level 7`** = "General" — all 21
qualities (Groups 1+2+3 combined, including the Major 7 duplication),
all 12 keys. Not built yet — this is new, on top of the 6 levels above.

### Minor Chord Extensions
Chord-tone table confirmed (18 chords across 3 groups — see the
conversation history for the exact degree breakdown the user confirmed):

**Group 1** (6 chords, triad + color tone, no 7th):
Minor 6, Minor 7, Minor add ♭9, Minor add 9, Minor add 11, Minor add 9/11

**Group 2** (4 chords, 6-chords):
Minor 6/9, Minor 6 add 11, Minor 6/9 add 11, Minor 6(♭9, 11)

**Group 3** (8 chords, 7-chords with extensions):
Minor 7, Minor 9, Minor 7(11), Minor 7(13), Minor 11, Minor 13,
Minor 7(11, 13), Minor 7(9, 13)

(Minor 11 and Minor 13 include the full stack — 9th implied under 11,
9th+11th implied under 13 — per standard jazz chord-symbol convention,
confirmed with the user. Everything else is only the tones explicitly
named, same rule as Major.)

Nothing is built for this yet. Mirror
`app/Support/MajorChordExtensionSamples.php` exactly:
- Minor triad base is `[0, 3, 7]` (not `[0, 4, 7]`).
- Same doubling/open-voicing/gap-filling rules: a single chord tone may
  appear at most twice in a voicing; at most 3 distinct tones may be
  doubled per voicing; no two adjacent tones (sorted bottom-to-top) may
  be more than 7 semitones apart — gaps wider than that get filled by
  inserting the smallest-fitting next-octave copy of some other chord
  tone (greedy smallest-fit, confirmed with the user).
- `v1` must go through the SAME gap-fill pass as every other variant —
  this was a real bug caught and fixed for Major: the close-position
  baseline isn't automatically gap-safe for sparser chords (e.g. a chord
  that jumps straight from the 5th to an 11th/13th with nothing between).
- 5 variants per chord where possible; accept fewer for chords where the
  rules genuinely can't produce 5 distinct voicings (this happened for
  Major add ♯11, which only yields 2) — don't pad with duplicates.
- **Answer format is multi-select, NOT single-chord-name radio.** This
  was gotten wrong the first time for Major and had to be redone. The
  question's `choices` column is always the full fixed
  `EXTENSION_CHOICES` token vocabulary
  (`['6', '7', '♭9', '9', '11', '♯11', '♭13', '13']`, matches
  `resources/js/lib/quizAnswerPresets.js`'s `EXTENSION_CHOICES` exactly);
  `answer` is a comma-and-space-joined subset of those tokens actually
  present in the chord (e.g. Minor 6 → `"6"`, Minor 7(11, 13) →
  `"7, 11, 13"`), derived from the same interval table the audio
  resolver uses so the two can never drift apart — see
  `mceAnswerTokens()` in `scripts/audio/build_major_chord_extensions.php`
  for the exact pattern to copy (degree-offset → token mapping, sorted
  into `EXTENSION_CHOICES`'s own order).
- No pre-rendered audio — live-mix only from the start. The build script
  must check per-instrument resolvability and only attach an instrument
  when it actually resolves for that specific (key, quality, variant);
  don't attach a dead reference. Some wide voicings will exceed
  narrower-range instruments (Trombone, Celesta, Vibraphone, Voice, ...)
  no matter the octave shift — same limit already hit by the
  9th/11th/13th Degree Chords categories and by Major Chord Extensions.
  Verify with a script that checks every created `QuizQuestionAudio` row
  resolves via `NoteSample::resolve()`, and that every `QuizQuestion` has
  at least one working instrument attached (zero orphan questions).
- Bass Guitar, Tuba, and Upright Bass excluded, same as every chord
  category this session.
- Wire the new resolver into `NoteSample::resolve()` (path prefix
  dispatch), `QuizController.php`'s title-based live-mix dispatch match
  arm, and `AudioQuizController.php`'s equivalent (both the
  `missingInstrumentPreviews()` dispatch and the exclusion-list
  constant).
- After building, check the admin page's memory/timing for the largest
  resulting quiz the same way this session did (`php -d memory_limit=...`
  tinker invocation of `quizPayload()`, checking peak memory stays well
  under the 768M ceiling already raised in `AudioQuizController::
  quizPayload()` for exactly this reason).

**Quiz-level structure — same 7-level pattern as Major (see that section
above for the full rule):**
- `Minor Chord Extentions · Level 1` = Group 1 (6 chords), key of F only.
- `Minor Chord Extentions · Level 2` = Group 1, all 12 keys.
- `Minor Chord Extentions · Level 3` = Group 2 (4 chords), key of F only.
- `Minor Chord Extentions · Level 4` = Group 2, all 12 keys.
- `Minor Chord Extentions · Level 5` = Group 3 (8 chords), key of F only.
- `Minor Chord Extentions · Level 6` = Group 3, all 12 keys.
- `Minor Chord Extentions · Level 7` = "General" — all 18 qualities
  (Groups 1+2+3 combined), all 12 keys.

Only one `Minor Chord Extentions` quiz row currently exists (a
placeholder) — this needs to become 7 quiz rows, same
rename-one-create-the-rest approach as Major.

### Dominant Chord Extensions
Not built yet, but the chord-tone groups ARE now confirmed — **3 groups,
superseding an earlier 2-group version** (unlike Major/Minor, this one
isn't split by chord structure — it's all dominant-7th-plus-tensions — so
the grouping dimension is **how many tensions are stacked on, further
split by altered-vs-natural 9th**, confirmed with the user, balanced at
5/5/3). Dominant 7th base is `[0, 4, 7, 10]` (1, 3, 5, ♭7).

**Group 1** (5 chords, altered 9th, two tensions):
| Chord | Tones | In C |
|---|---|---|
| Dom7(♭9, ♯11) | 1,3,5,♭7,♭9,♯11 | C E G B♭ D♭ F♯ |
| Dom7(♭9, 13) | 1,3,5,♭7,♭9,13 | C E G B♭ D♭ A |
| Dom7(♭9, ♭13) | 1,3,5,♭7,♭9,♭13 | C E G B♭ D♭ A♭ |
| Dom7(♯9, ♯11) | 1,3,5,♭7,♯9,♯11 | C E G B♭ D♯ F♯ |
| Dom7(♯9, ♭13) | 1,3,5,♭7,♯9,♭13 | C E G B♭ D♯ A♭ |

**Group 2** (5 chords, natural 9th or no 9th, two tensions):
| Chord | Tones | In C |
|---|---|---|
| Dom7(9, ♯11) | 1,3,5,♭7,9,♯11 | C E G B♭ D F♯ |
| Dom7(9, 13) | 1,3,5,♭7,9,13 | C E G B♭ D A |
| Dom7(9, ♭13) | 1,3,5,♭7,9,♭13 | C E G B♭ D A♭ |
| Dom7(9, 11) | 1,3,5,♭7,9,11 | C E G B♭ D F |
| Dom7(♯11, 13) | 1,3,5,♭7,♯11,13 | C E G B♭ F♯ A |

**Group 3** (3 chords, three tensions):
| Chord | Tones | In C |
|---|---|---|
| Dom7(♭9, ♯11, 13) | 1,3,5,♭7,♭9,♯11,13 | C E G B♭ D♭ F♯ A |
| Dom7(9, ♯11, 13) | 1,3,5,♭7,9,♯11,13 | C E G B♭ D F♯ A |
| Dom7(♭9, ♯9, ♭13) | 1,3,5,♭7,♭9,♯9,♭13 | C E G B♭ D♭ D♯ A♭ |

**Quiz-level structure — same 7-level pattern as Major/Minor:**
- `Dominant Chord Extentions · Level 1` = Group 1 (5 chords), key of F
  only.
- `Dominant Chord Extentions · Level 2` = Group 1, all 12 keys.
- `Dominant Chord Extentions · Level 3` = Group 2 (5 chords), key of F
  only.
- `Dominant Chord Extentions · Level 4` = Group 2, all 12 keys.
- `Dominant Chord Extentions · Level 5` = Group 3 (3 chords), key of F
  only.
- `Dominant Chord Extentions · Level 6` = Group 3, all 12 keys.
- `Dominant Chord Extentions · Level 7` = "General" — all 13 qualities
  (Groups 1+2+3 combined), all 12 keys.

Two existing quiz rows (`Dominant Chord Extentions` placeholder, and
`Chord naming` which the user deliberately filed under this category —
see Status section at top) — the placeholder needs to become 7 quiz rows,
same rename-one-create-the-rest approach as Major; `Chord naming` is
unrelated and stays as its own separate quiz.

Uses a different token vocabulary than Major/Minor:
`DOMINANT_EXTENSION_CHOICES = ['♭9', '9', '♯9', '11', '♯11', '♭13', '13']`
(already defined in `quizAnswerPresets.js`) — note there's no `'6'` or
`'7'` token in this list (every Dominant chord already has the ♭7 as a
given, it's never itself the "answer"), so the `answer`-token-derivation
helper (`mceAnswerTokens()`-equivalent) needs its own degree-to-token map
for this vocabulary rather than reusing Major/Minor's `MCE_DEGREE_TO_TOKEN`.

Same doubling/open-voicing/gap-filling voicing rules as Major/Minor (≤2
occurrences per tone, ≤3 distinct tones doubled per voicing, no adjacent
gap >7 semitones, smallest-fitting-copy gap fill, `v1` must go through
the same gap-fill pass as every other variant — same bug class already
caught once for Major). Same live-mix-only, no-pre-rendered-audio,
skip-instruments-that-don't-resolve approach as Major/Minor. Same
Bass Guitar/Tuba/Upright Bass exclusion.

### Change existing C-only single-key levels to F instead

New request, unrelated to the Extensions split/7-level work above, but
noted here since it's the same kind of catalog-wide content change. For
every quiz level across the catalog that's currently built in the key of
C only — the "Level 1"-style single-key levels of Basic Triads, Add 9,
7th Degree Chords, Secondary 7th Chords, 9th Degree Chords, Secondary 9th
Chords, 11th Degree Chords, Secondary 11th Chords, and 13th Degree Chords
(NOT their 12-key levels, which are unaffected since they already include
F as one of the 12) — switch the implicit key from C to F, for the same
"more balanced" reasoning that made the new Major/Minor/Dominant Chord
Extensions levels use F for their single-key levels.

This is a small, non-destructive change, not a rebuild:
- None of these single-key levels' audio filenames ever encoded a key in
  the first place (e.g. `level1/piano/diminished7.wav`, no `c-` prefix) —
  the key is purely implicit in code, defaulted to C. So this needs **no
  database changes, no audio_path changes, no rebuild, and no attempt
  resets** — just a resolver code change.
- The fix is one line per file: change `$key = 0;` (0 = C, the default
  fallback used when a path has no key segment) to `$key = 5;` (F is 5
  semitones above C, matching the `KEYS` array's own index for `'f'`) in
  each of these `app/Support/` resolver classes:
  `BasicTriadSamples.php`, `AddNineSamples.php`,
  `SeventhDegreeChordSamples.php`, `SecondarySeventhChordSamples.php`,
  `NinthDegreeChordSamples.php`, `SecondaryNinthChordSamples.php`,
  `EleventhDegreeChordSamples.php` (covers `SecondaryEleventhChordSamples`
  too, which inherits this method), `ThirteenthDegreeChordSamples.php`.
  (Confirmed via `grep -n "key = 0" app/Support/*.php` — exactly one match
  per file, already verified as the right line in each.)
- Do NOT touch `ChordToneRecognitionSamples.php` (always 12-key, no
  single-key level exists there) or the new chord-extension resolvers
  (`MajorChordExtensionSamples`, `MinorChordExtensionSamples`,
  `DominantChordExtensionSamples` — their single-key levels are already
  F by design, that's what this change is modeled after).

**After the code change, re-verify coverage — this part DOES touch the
database, but only additively/subtractively, not a full rebuild:**
F is a different register than C, so which instruments successfully
resolve a given chord at that register can shift (same narrow-range
limit already hit repeatedly this session — Trombone, Celesta,
Vibraphone, Voice, etc.). For every affected single-key level:
- Re-run the same `NoteSample::resolve()` + `sampleExists()` coverage
  check used when each category was originally built, against the new
  F-based pitches.
- Remove any `QuizQuestionAudio` row that no longer resolves (same
  "never attach a dead reference" rule used throughout — e.g. the
  selective-deletion approach used for 9th/11th/13th Degree Chords'
  pre-rendered-audio cleanup is the right model here, just for live-mix
  attachment rows instead of files).
- Optionally attach any instrument that now newly resolves at F but
  didn't at C (lower priority than removing broken ones, but worth doing
  for completeness).
- Confirm zero orphan questions (every question still has at least one
  working instrument) the same way this was checked for Major Chord
  Extensions.

### A real bug already fixed — no action needed, just context
`QuizController::answer()` graded multi-select answers with exact string
equality, which broke whenever the submitted tap order differed from the
stored answer's canonical order. Fixed via a new `answersMatch()` helper
(falls back to order-independent token-set comparison only when the
stored answer contains `", "`, otherwise unchanged exact-match behavior
for every other answer type). Nothing else needs to change for this —
just know it's already handled correctly for any new multi-select quiz.
