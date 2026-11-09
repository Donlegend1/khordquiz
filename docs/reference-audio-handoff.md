# Reference Audio ("Listen Mode") — handoff for Codex

Written 5 November 2026, continuing from the Claude session that rebuilt this
feature. Read this before touching `reference_audio_tracks`, `ReferenceAudioTracks`,
or the member-facing Listen Mode UI.

## What changed this session (read first — it's a different architecture now)

Reference audio used to be resolved **eagerly**: the category page called
`ReferenceAudioTracks::present()`, which resolved every stored track for all
28 instruments, on every page load, for every quiz shown. That was fine while
quizzes only had a handful of reference clips, but several categories now
have hundreds to low thousands of raw track rows (Chord Tone Recognition #2
alone had 2,112), and resolving 28× that on every page load is exactly the
kind of thing that caused a real 30-second admin timeout earlier in this
project (`NoteSample::instrumentFiles()` memoization bug — now fixed, but the
eager-resolve pattern itself was still wasteful regardless).

It's now **lazy**:

- `ReferenceAudioTracks::pool(array $tracks): array` — cheap. Groups the raw
  `reference_audio_tracks` rows into distinct clips (by label/name/recipe,
  same grouping key as before) and returns just `{id, label, name}` per
  group. **No audio resolution at all** — safe to call on every category
  page load regardless of pool size. This is what `QuizController::category()`
  now sends to the frontend as `referenceAudioPool` (replacing the old fully-
  resolved `referenceAudioTracks` prop — that prop name is gone).
- `ReferenceAudioTracks::resolveOne(array $tracks, string $groupId, string $sound): ?array`
  — resolves exactly one clip, for one instrument, on demand. Called by a new
  route, `quiz.reference-audio-clip` (`GET /quiz/play/{quiz:slug}/reference-audio/{group}?sound=X`,
  `QuizController::referenceAudioClip()`), which the frontend hits once per
  "Next" press.
- `present()` still exists (kept for the admin editor's preview and for
  `tests/Feature/QuizReferenceInstrumentTest.php`'s broader coverage), but
  **don't use it to build anything shown to members** — it's the eager path
  this rework moved away from.

Frontend (`resources/js/Pages/Member/QuizCategory.jsx`, `ReferenceAudioModal`):
rebuilt as a **shuffle-bag**, not the old "N of M" Previous/Next browser:

- No Previous button. Next only.
- On open (and whenever the pool or selected instrument changes): the full
  pool is shuffled once (Fisher-Yates), then played straight through one
  clip per "Next" press.
- Only once every clip in that shuffled pass has played does it reshuffle
  for a fresh pass — never repeats a clip within a pass, and the reshuffle
  also avoids landing the just-finished clip back in slot one (no repeat
  across the pass boundary either).
- If a clip fails to resolve for the current instrument (narrow-range
  instruments won't cover every answer), it transparently tries the next
  one in the shuffled queue rather than showing a false "unavailable."
- `hasReferenceAudio` (whether the Listen Mode button shows at all) is now
  just `(quiz.referenceAudioPool?.length ?? 0) > 0` — cheap, not per-sound.
  If the current instrument genuinely has nothing, the modal itself says so
  once opened, rather than the button being hidden/shown per instrument on
  every page load.

**A real bug this surfaced and fixed**: `ReferenceAudioTracks::prepare()`'s
path dispatch is a chain of `if/elseif` checks. A newer path convention
(`reference-intervals/live-v1/{melodic|harmonic}/{slug}/{root}-{second}.wav`,
used by the Intervals category's per-key clips) was silently falling into an
older, more generic `reference-intervals/` branch meant for a different
convention — that branch detects timing via `str_contains($path, '/harmonic-')`
(hyphen), which never matches `/harmonic/` (slash), so harmonic clips played
melodic (sequential) instead of simultaneous, and it was also ignoring the
exact root/second encoded in the path and re-deriving its own generic
best-fit pitch, silently collapsing all 12 keys to the same one pitch. Fixed
by adding a dedicated branch for `reference-intervals/live-v1/` ahead of the
generic one, routing straight through `NoteSample::resolve($path)` (which
already reads timing + exact pitches directly from the path — no guessing
needed). **If you add or touch any new path convention, check where it lands
in that if/elseif chain** — a convention that's a strict prefix of an earlier,
more generic branch's `str_starts_with()` check will silently get swallowed
by it.

## The content rule — same for every quiz

`reference_audio_tracks` should contain **one row per real, distinct playable
clip this quiz actually has** — not a hand-picked subset, not one example per
answer label when the quiz has several real variants of that same answer
(different octave, different key, different voicing, …). Reuse that
question's own already-attached `quiz_question_audio` path (prefer Piano,
fall back to any available instrument) as the track's `path`/`sound`; the
label is just the question's `answer` text. `pool()`'s grouping already keeps
two rows with the same label but a different underlying path (e.g. "Do Re Mi"
in two different octaves, or "Major 2nd" in two different keys) as separate
clips — don't try to dedupe by label yourself.

Concretely, this session:
- **Melodic Dictation** (7 quizzes): went from a hand-curated 6-8 examples
  per quiz to literally every real question row — e.g. 3-Note Stepwise ended
  at 84 (28 patterns × 3 recorded octave placements, all genuinely distinct
  audio).
- **Intervals category** (6 quizzes: Melodic/Harmonic × Diatonic/Non-diatonic/General):
  went from one fixed-key example per interval name to all 12 keys (see next
  section — the question bank itself was expanded first, then reference
  audio rebuilt from the new full bank).
- **Melodic/Harmonic Second-Seventh** (12 quizzes): already exactly matched
  this rule (7 real answers each = 7 reference clips) — explicitly **do not**
  expand these to multiple keys. Confirmed directly: "No, melodic and
  harmonic second through seventh is different. We're not doing it like
  that." These stay as-is.

## A related but separate change: all-12-keys question content

Not reference-audio-specific, but you'll see it in the DB: the Intervals
category's question bank itself (not just its reference audio) was expanded
from one fixed register per interval name to all 12 keys, bounded by each
instrument's real recorded range — see `IntervalSamples::rootsForSemitones()`
(new) and `scripts/audio/build_interval_all_keys.php` (new, has a dry-run
mode by default, `--apply` to write; deletes the old single-key question per
answer and replaces it with one row per playable key). This also fixed itself
into `buildQuestionSequence()`'s existing fair-shuffle-with-sound-filtering
logic with no controller changes needed — a narrow instrument's smaller pool
is already handled there. **Do not apply this same "all 12 keys" treatment to
Melodic/Harmonic Second-Seventh** — same explicit instruction as above, this
is Intervals-category-only. Whether any *other* category's question content
(not just reference audio) should eventually get the all-12-keys treatment
hasn't been decided — don't assume it from this precedent.

## What's actually pending for you

1. **Relative Tone Pitch #1 and #2** — still on the *old* reference audio (a
   single "C major scale" reference clip, tempo already adjusted this
   session via `ReferenceAudioTracks::notesForIntervals()`'s spacing param,
   0.4 → 0.55). These were explicitly in scope for the full-pool shuffle-bag
   rebuild ("Apart from the first two quizzes in the relative pitch
   quiz" — the first two are Find the Note and Find the Pitch, which stay as
   they are; RTP1/RTP2 are not the excluded ones) but never actually got
   converted before this handoff. Their real answer pool is the 7 solfège
   notes (Do–Ti) for RTP1, and whatever RTP2's wider-register pool is — check
   `QuizController`'s existing RTP1/RTP2 special-casing (`SINGLE_DEGREE_OCTAVE_UP_SLUGS`,
   the `Relative Tone Pitch #2` exclusion in `liveNotesFromAnswer()`) before
   assuming the same per-question-row reuse approach applies cleanly.

2. **Every other category** — Basic Triads, Add 9, 7th/9th/11th/13th Degree
   Chords + their Secondary variants, Chord Tone Recognition, Major/Minor/Dominant
   Chord Extensions, Chord Naming, Chord Progressions (Cadences, Diatonic
   Progression #1/#2, Progression Build #1/#2, Progression Recognition #1-4,
   2-5-1 chord degree, Chordal Progressions), Modal Harmony, Modal Voicings,
   Scales. These already have substantial `reference_audio_tracks` data from
   earlier work (some quite large — Secondary 11th Chords · General had 1,192
   raw rows, Chord Tone Recognition #2 had 2,112). **Audit each one** against
   the content rule above (genuinely the full real distinct pool, not
   partial) and re-verify it actually resolves correctly through
   `ReferenceAudioTracks::prepare()`'s current branch order (the harmonic/key
   bug above is exactly the kind of thing to check for — anywhere a path
   convention might collide with an earlier, more generic branch). Rebuild
   whichever ones don't already satisfy this, same pattern as Melodic
   Dictation / Intervals: one track per real question row, reusing its
   already-attached audio.

3. Performance is already handled for you — `pool()` stays cheap no matter
   how many rows you put in `reference_audio_tracks`, and the frontend
   resolves one clip at a time. You don't need to build anything new for
   scale, just populate the data correctly and make sure `prepare()`
   actually resolves what you point it at.


## Codex completion — Basic Triads onward, 5 November 2026

Completed the authorized rebuild **one quiz at a time, in category/lesson order**,
starting with all eight Basic Triads lessons, then Add 9 and each subsequent
category through Scales. Relative Pitch, Melodic Dictation and Intervals were
not rebuilt in this operation. RTP1/RTP2 remain pending as described above.

**169 published quizzes / 18 categories / 35,359 distinct within-quiz reference
clips / 913,938 verified playable clip–instrument combinations.** Each quiz uses
its actual question attachments, preferring a live-playable Piano source and
falling back to another real attached source. Different keys/registers/voicings
with the same label remain distinct. Exact duplicate grouping identities within
one quiz are stored once. Narrow instrument coverage is respected.

| Category | Quizzes | Reference clips |
| --- | ---: | ---: |
| Basic Triads | 8 | 402 |
| Add 9 | 8 | 520 |
| 7th Degree Chords | 8 | 554 |
| Secondary 7th Chords | 9 | 1,212 |
| 9th Degree Chords | 6 | 780 |
| Secondary 9th Chords | 7 | 1,500 |
| 11th Degree Chords | 6 | 1,092 |
| Secondary 11th Chords | 7 | 1,688 |
| 13th Degree Chords | 6 | 260 |
| Chord Tone Recognition | 6 | 4,224 |
| Major Chord Extensions | 13 | 2,685 |
| Minor Chord Extensions | 13 | 2,271 |
| Dominant Chord Extensions | 8 | 1,014 |
| Chord Naming | 6 | 2,717 |
| Chord Progressions | 11 | 12,548 |
| Modal Harmony | 8 | 546 |
| Modal Voicings | 18 | 637 |
| Scales | 21 | 709 |

Reusable command: `app/Console/Commands/RebuildReferencePools.php`.

```sh
# Audit only by default; --quiz accepts a real quiz slug for a single lesson.
php -d memory_limit=1024M artisan audio:rebuild-reference-pools --category="Basic Triads"
# Save only after each lesson verifies successfully; stop on the first failure.
php -d memory_limit=1024M artisan audio:rebuild-reference-pools --category="Basic Triads" --apply
# No category option means all categories from Basic Triads onward in order.
```

Before saving each quiz, every live-playable instrument resolution is compared
against the question path's exact `NoteSample::resolve()` note URLs and offsets.
This checks instrument identity, key/register, chord voicing and simultaneous or
sequential timing through the current `ReferenceAudioTracks::prepare()` dispatch.
No path-dispatch mismatch or source-less question was found during this run.
No question banks, attempts, difficulty assignments or native mobile content were
changed. Only `quizzes.reference_audio_tracks` was updated in the database.

Previous pools and per-quiz verification reports are backed up under
`storage/app/private/reference-pool-backups/20261105-193517-34618b/` and the
subsequent timestamped directories through `20261105-193820-b10c8e/`. Each quiz's
JSON backup contains its ID, title and previous reference tracks; `report.json`
contains before/after clip counts and verification counts for that category.

The existing lazy pool/one-clip route and Next-only shuffle bag remain. The two
existing pure shuffle functions were moved unchanged from QuizCategory into
`resources/js/lib/referenceAudio.js` for direct regression tests. Replaced the
obsolete unit tests importing the removed eager helper with tests for full-pass
coverage, no pass-boundary repeats, source immutability and empty/single pools.
Updated misleading comments about old Previous/inversion-tab browsing.

Validation: 7 backend tests / 613 assertions (`ReferencePoolRebuildTest` plus
`QuizReferenceInstrumentTest`), 3 shuffle tests, production build and PHP Pint.
The new rebuild tests cover dry-run safety, preserved register variants,
idempotence, unchanged question rows and retaining old references on failure.


## RTP #1/#2 conversion completed — 6 November 2026

Both remaining Relative Tone Pitch references are now full live-mix pools:

- **RTP #1:** 7 real question choices, 196 verified note/instrument variants
  (all seven choices on all 28 instruments). This question bank has no attached
  audio variants, so the builder uses the same `NoteSample::fromAnswer()` fallback
  and instrument-specific register as actual quiz playback.
- **RTP #2:** 35 question/register choices, 705 verified note/instrument variants.
  Uses each question's actual attached path for that exact instrument, preserving
  its register. Piano, EP and Upright Piano cover all 35; narrower instruments
  retain their actual smaller pools. No question-bank or grading changes.

Added `audio:rebuild-relative-tone-references 1|2 [--apply]` (audit-only by default).
It refuses to save if a question has no playable source, backs up old references,
and verifies clip/register identity, selected-instrument playback and range skips.

`ReferenceAudioTracks::groups()` now supports an optional stable `clip_key` for
question-based groups whose actual source filenames/registers differ by instrument.
Existing tracks without a clip key keep their original grouping. `attached_only`
prevents these explicit per-question pools from inventing a missing instrument's
register by substituting a different source. Both options are used by this RTP
builder only; member listing remains cheap and playback stays lazy/Next-only.

Local backups and verification reports:
- `storage/app/private/reference-pool-backups/20261106-072339-rtp1-017a98/`
- `storage/app/private/reference-pool-backups/20261106-072406-rtp2-b895b0/`

Find the Note, Find the Pitch and the Second–Seventh pair quizzes were not changed.
The RTP #1 web Quick Guide no longer incorrectly says references play in scale
order. Native mobile content/audio connections were not touched.

Checks: 10 reference-backend tests (645 assertions), including the new
`RelativeToneReferencePoolTest`; 3 shuffle tests; PHP Pint and production build.


## RTP home-note button — 6 November 2026

Added the tonic aid to web RTP #1 and #2, reusing the existing live-mix tonic
resolver. It plays one fixed Do in the attempt's exact selected instrument and
appropriate register, independent of the question's answer/register. It does not
change the question or grading. Label: **Play home note (Do)**, with no helper
description below it. Green secondary pill, minimum 44px touch
height, visible keyboard focus, playback busy state and retryable error message.
Playback is cleaned up when moving to the next question. Existing Melodic
Dictation labels remain unchanged. Updated both RTP web Quick Guides to explain
the optional aid. Mobile RTP #1/#2 now show the same button below the audio player
as a design-only control, including dark-mode styling. No native audio connection
was added.
