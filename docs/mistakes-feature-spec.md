# Mistakes page — implementation spec

## Context

`resources/js/Pages/Member/Mistakes.jsx` is currently a static mockup: the
`confusions` array is hardcoded sample data, and the "Review mistakes" button
is inert (`title="Coming soon"`, no handler). The route just renders the page
with no props (`routes/web.php:75`).

Design reference (approved, not yet wired to real data):
https://claude.ai/artifact/7T2gAJD3Rz1SjRtpvgHAo4 — adds a per-quiz switcher
above the existing card, and two listen buttons per mistake row ("Hear what
you picked" / "Hear the correct answer") so the user can compare the two
answers by ear, not just read them.

## What already exists to build on

Per-question answer history is **already recorded** — this is not a new
table. `quiz_attempt_answers` (migration
`2026_10_15_093544_create_quiz_attempt_answers_table.php`, model
`App\Models\QuizAttemptAnswer`) stores one row per graded question per
attempt: `quiz_attempt_id`, `quiz_question_id`, `position`, `picked` (string),
`correct` (bool). `QuizController::results()` (line ~516) already reads this
table to power the "Review your answers" panel on `QuizResults.jsx`, including
per-row audio playback (`AnswerReview` component, same file, line ~48) via
`new Audio(url)` with a play/pause toggle.

Audio resolution for an answer string already exists and does **not** require
a pre-rendered file: `QuizController::liveNotesFromAnswer(Quiz $quiz,
QuizQuestion $question, ?string $sound)` (line ~835) dispatches to
`NoteSample::fromAnswer()` / `IntervalSamples::fromAnswer()` /
`BasicTriadSamples::forQuestion()` / `AddNineSamples::forQuestion()` depending
on category, and `questionAudioUrl()` (line ~795) wraps the result into a
single playable URL when it resolves to a pre-rendered combo, or
`questionLiveNotes()` returns the live-mix note list. The one gap: today this
only ever resolves `$question->answer` (the *correct* answer) — never an
arbitrary string. Resolving a wrong `picked` answer's audio needs the same
dispatch logic driven by a plain string instead of a `QuizQuestion`.

## What's missing

1. An aggregation query turning rows in `quiz_attempt_answers` into "most
   confused pairs," scoped to a user and a quiz.
2. A resolver for arbitrary answer-string audio (not just a question's own
   correct answer), so the "picked" side can be played.
3. A route + controller action that returns real data to `Mistakes.jsx`.
4. Frontend: the quiz switcher and the two listen buttons, per the design
   artifact above.

## 1. Backend — aggregation query

New method, e.g. `ProgressController::mistakesFor(Quiz $quiz, User $user,
int $limit = 10)`:

```php
QuizAttemptAnswer::query()
    ->select('quiz_attempt_answers.picked', 'quiz_attempt_answers.quiz_question_id')
    ->selectRaw('COUNT(*) as occurrences')
    ->selectRaw('MAX(quiz_attempt_answers.id) as latest_id') // to recover which sound to play back with
    ->join('quiz_attempts', 'quiz_attempts.id', '=', 'quiz_attempt_answers.quiz_attempt_id')
    ->where('quiz_attempts.user_id', $user->id)
    ->where('quiz_attempts.quiz_id', $quiz->id)
    ->where('quiz_attempt_answers.correct', false)
    ->groupBy('quiz_attempt_answers.picked', 'quiz_attempt_answers.quiz_question_id')
    ->having('occurrences', '>=', self::MISTAKE_MIN_OCCURRENCES)
    ->orderByDesc('occurrences')
    ->limit($limit)
    ->get();
```

**Minimum-occurrence threshold**: a pair only shows up once the user has made
it at least twice — a single wrong answer is noise, not yet a pattern worth
surfacing. `const MISTAKE_MIN_OCCURRENCES = 2;` on the controller, used in the
`having()` above. This also changes the "quizzes with mistakes" list that
powers the pill switcher (§3) — it must apply the same `HAVING occurrences >=
2` per `(quiz_id, quiz_question_id, picked)` group before deciding whether a
quiz has anything to show at all, not just when listing rows for the already-
selected quiz. A quiz where every wrong answer was only ever picked once
should not appear in the switcher, and should fall back to the existing empty
state ("No mistakes yet") if it's the only quiz the user has played.

Group by `(quiz_question_id, picked)`, not `(answer, picked)` — two different
questions can share the same correct answer text (e.g. two "Major 3rd"
questions at different octaves/instruments), and collapsing them would
overcount or misattribute occurrences. Join each group back to its
`QuizQuestion.answer` for display, and resolve the "which instrument to play
this pair in" from the attempt behind `latest_id` (`QuizAttemptAnswer::find($latest_id)->attempt->sound`)
— always the most recent occurrence's sound, so playback uses whatever
instrument the user most recently heard this mistake in.

This mirrors the existing `$attempt->answers()` pattern in
`QuizController::results()` — same table, same `correct` boolean, just
grouped across attempts instead of scoped to one.

## 2. Backend — resolve audio for an arbitrary answer string

Refactor `QuizController::liveNotesFromAnswer()` so its body takes a plain
`string $answer` instead of pulling `$question->answer` internally, and have
the existing call sites pass `$question->answer` explicitly:

```php
// @return list<array{url: string, offset: float}>|null
private function liveNotesFromAnswerString(Quiz $quiz, string $answer, ?string $sound): ?array
{
    // body unchanged, just s/$question->answer/$answer/ throughout
}

private function liveNotesFromAnswer(Quiz $quiz, QuizQuestion $question, ?string $sound): ?array
{
    return $this->liveNotesFromAnswerString($quiz, $question->answer, $sound);
}
```

Two call sites need `Quiz` category context that currently comes from
`$question` for the `Add 9` / `Basic Triads` branches
(`AddNineSamples::forQuestion($question, $slug)`,
`BasicTriadSamples::forQuestion($question, $slug)` — these take the whole
question, not just its answer, likely because they need more than the answer
string e.g. root/inversion encoded elsewhere on the row). For a `picked`
string that is guaranteed to be one of that same question's own `choices`
(never a free-text value — see §4), the cleanest fix is to resolve relative to
the **question itself** with its `answer` temporarily swapped, not to try to
re-derive chord structure from a bare string:

```php
private function liveNotesFromPicked(Quiz $quiz, QuizQuestion $question, string $picked, ?string $sound): ?array
{
    $pickedQuestion = clone $question;
    $pickedQuestion->answer = $picked;

    return $this->liveNotesFromAnswer($quiz, $pickedQuestion, $sound);
}
```

This keeps every existing branch (`Intervals`, `Find the Pitch`, `Add 9`,
`Basic Triads`, default) working unchanged, since they all end up reading
`$question->answer` one way or another. Use this for the "picked" button;
reuse the existing `liveNotesFromAnswer($quiz, $question, $sound)` unchanged
for the "correct" button, exactly as `QuizResults` already does.

Exclude the rare case where `picked` was `null` (no answer submitted, if that
state exists) or isn't itself a valid choice for that question — the listen
button for "picked" is simply omitted from the row in that case (same
`canPlayCorrect`-style boolean the existing `AnswerReview` component already
uses).

## 3. Route + controller action

```php
// routes/web.php, replacing the current line 75
Route::get('/mistakes', [MistakesController::class, 'index'])->name('mistakes');
Route::get('/mistakes/{quiz}', [MistakesController::class, 'index'])->name('mistakes.quiz');
```

New `app/Http/Controllers/MistakesController.php` (or fold into
`ProgressController`, which already owns `/progress` and references
Mistakes in its "Most confused" comment at line 70–72 — update that comment
once this ships). Responsibilities:

- List of quizzes the user has at least one wrong `quiz_attempt_answers` row
  in (powers the pill switcher — only show quizzes with real mistakes, not
  every quiz in the catalog).
- For the selected quiz (default: the one with the most mistakes, or the
  first in the list), run the §1 aggregation and resolve both audio URLs
  per row via §2, reusing `questionAudioUrl()`'s existing
  `$notes ? ... : null` pattern.
- Return via Inertia:

```php
return Inertia::render('Member/Mistakes', [
    'quizzes' => $quizzesWithMistakes->map(fn (Quiz $q) => ['slug' => $q->slug, 'title' => $q->title])->values(),
    'activeQuiz' => $activeQuiz->slug,
    'confusions' => $rows->map(fn ($row) => [
        'picked' => $row->picked,
        'answer' => $row->question->answer,
        'count' => $row->occurrences,
        'pickedAudioUrl' => $pickedUrl,   // null if unresolvable — hide that button
        'correctAudioUrl' => $correctUrl, // null if unresolvable — hide that button
    ])->values(),
]);
```

Resolve audio eagerly server-side (not lazily on click) — the per-quiz row
count is small (the design caps "most confused" lists at a handful of pairs),
and this matches how `QuizResults` already does it rather than introducing a
new on-demand audio endpoint.

## 4. Frontend — `Mistakes.jsx`

- Replace the hardcoded `confusions` const with `props.confusions`, and the
  "Review mistakes" button's `title="Coming soon"` / missing `onClick` with
  real behavior once that flow exists (out of scope here — leave it
  disabled with the same title until a "retry just the missed ones" flow is
  built).
- Add the pill switcher from the design artifact, populated from
  `props.quizzes`, each pill an Inertia `<Link href={route('mistakes.quiz', quiz.slug)}>`
  (full page visit is fine here — this is a low-frequency navigation, not a
  live filter — matching how `AppShell`'s nav links work elsewhere in this
  app rather than introducing client-side-only state).
- Add the two listen buttons per row. Reuse the `AnswerReview` component's
  playback pattern from `QuizResults.jsx` verbatim (`new Audio(url)`,
  play/pause toggle, pulsing ring while playing) rather than writing a new
  audio handler — it already solves "only one clip plays at a time," which
  this page also needs now that each row has two buttons. Promote that
  play/pause logic out of `QuizResults.jsx` into a small shared hook (e.g.
  `resources/js/lib/useAudioToggle.js`) used by both pages, since it'll now
  have two call sites.
- Omit a row's listen button when its `*AudioUrl` is `null` (mirrors the
  existing `canPlayCorrect` check in `AnswerReview`) rather than showing a
  disabled button — matches how missing audio is already handled elsewhere
  in the quiz UI.

## Verification plan

- Seed or play a few real attempts with intentional wrong answers across at
  least two quizzes with different answer shapes (e.g. Melodic Diatonic
  Intervals and 7th Degree Chords · Level 1) to populate real
  `quiz_attempt_answers` rows.
- Load `/mistakes`: confirm the pill switcher lists only quizzes with real
  mistakes, confirm counts match `SELECT picked, COUNT(*) FROM
  quiz_attempt_answers ... GROUP BY picked, quiz_question_id` run directly
  against the DB for that user/quiz.
- Click both listen buttons on a few rows: confirm "Hear the correct answer"
  matches what `QuizResults`' review panel plays for that same question, and
  "Hear what you picked" audibly differs and matches the picked label.
- Confirm a row whose picked value isn't resolvable to audio (if any) hides
  that button instead of erroring.
- Regression: re-run a `QuizResults` attempt afterward to confirm the
  promoted shared audio-toggle hook didn't change its existing behavior.
