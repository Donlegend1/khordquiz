<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizMark;
use App\Models\QuizQuestion;
use App\Support\QuizAnswerPresets;
use App\Support\QuizCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    // Same role as the mobile app's hero image on its quiz-preview screen
    // (app/quiz-preview.tsx): one generic clip shown on every quiz, not a
    // unique video authored per quiz. Used as a fallback wherever a quiz
    // has no video_embed of its own.
    private const DEFAULT_VIDEO_EMBED = <<<'HTML'
        <script src="https://fast.wistia.com/player.js" async></script><script src="https://fast.wistia.com/embed/ce5tw47h05.js" async type="module"></script><style>wistia-player[media-id='ce5tw47h05']:not(:defined) { background: center / contain no-repeat url('https://fast.wistia.com/embed/medias/ce5tw47h05/swatch'); display: block; filter: blur(5px); padding-top:56.25%; }</style> <wistia-player media-id="ce5tw47h05" aspect="1.7777777777777777"></wistia-player>
        HTML;

    // The canonical chromatic note set generated for "Find the Note" (see
    // scripts/audio/) — reused by any other "tap a note name" keyboard (Find the
    // Key #1/#2) as a generic note-audio lookup, independent of that quiz's own
    // question pool.
    private const NOTE_SLUGS = [
        'C' => 'c', 'C#/Db' => 'csharp', 'D' => 'd', 'D#/Eb' => 'dsharp', 'E' => 'e', 'F' => 'f',
        'F#/Gb' => 'fsharp', 'G' => 'g', 'G#/Ab' => 'gsharp', 'A' => 'a', 'A#/Bb' => 'asharp', 'B' => 'b',
    ];

    // Every instrument's storage folder slug (storage/app/public/notes/{slug}/…) —
    // mirrors QuizCategory.jsx's SOUND_CATEGORIES / Settings.jsx's INSTRUMENT_GROUPS
    // membership, kept in sync by hand same as QuizCatalog::allInstruments().
    private const SOUND_SLUGS = [
        'Piano' => 'piano', 'EP' => 'ep', 'Upright Piano' => 'upright-piano', 'Organ' => 'organ',
        'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
        'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
        'Saxophone' => 'saxophone', 'Trombone' => 'trombone', 'Tuba' => 'tuba',
        'Voice' => 'voice',
        'Marimba' => 'marimba', 'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
    ];

    public function saved(Request $request): Response
    {
        $marks = QuizMark::query()
            ->where('user_id', $request->user()->id)
            ->where('bookmarked', true)
            ->with('quiz')
            ->latest('updated_at')
            ->get();

        return Inertia::render('Member/Saved', [
            'saved' => $marks->map(fn (QuizMark $mark) => [
                'slug' => $mark->quiz->slug,
                'title' => $mark->quiz->title,
                'category' => $mark->quiz->category,
                'difficulty' => $mark->quiz->difficulty,
                'savedAt' => $mark->updated_at->diffForHumans(),
            ])->values(),
        ]);
    }

    public function categories(Request $request): Response
    {
        $user = $request->user();
        $quizzes = Quiz::query()->where('is_published', true)->get(['id', 'category', 'title']);
        $byCategory = $quizzes->groupBy('category');
        $completedQuizIds = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->pluck('quiz_id')
            ->unique();

        // Shows every category the app has, not just ones with a real quiz behind
        // them yet — quizCount is the real catalog size (app parity), done/percent
        // are computed only from real, playable quizzes.
        $categories = collect(Quiz::CATEGORIES)
            ->map(function ($name) use ($byCategory, $completedQuizIds) {
                $realQuizIds = $byCategory->get($name, collect())->pluck('id');
                $catalogSlugs = collect(QuizCatalog::subcategories($name))->map(fn ($n) => Str::slug($n));
                $realSlugs = $byCategory->get($name, collect())->pluck('title')->map(fn ($t) => Str::slug($t));
                $quizCount = $catalogSlugs->concat($realSlugs)->unique()->count();
                $done = $realQuizIds->intersect($completedQuizIds)->count();

                return [
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'quizCount' => $quizCount,
                    'done' => $done,
                    'percent' => (int) round(($done / max(1, $quizCount)) * 100),
                ];
            })
            ->values();

        return Inertia::render('Member/Quiz', [
            'categories' => $categories,
        ]);
    }

    public function category(Request $request, string $category, ?string $quiz = null): Response
    {
        $name = $this->resolveCategory($category);
        $user = $request->user();

        // reference_audio_tracks is excluded here and fetched separately below —
        // some quizzes' JSON blob is large enough (thousands of reference tracks)
        // that including it in this ORDER BY query overflows MySQL's filesort
        // buffer ("Out of sort memory", error 1038). A plain whereIn with no
        // ORDER BY needs no filesort, so the same column there is harmless.
        $quizzes = Quiz::query()
            ->where('is_published', true)
            ->where('category', $name)
            ->orderBy('difficulty')
            ->orderBy('title')
            ->get(['id', 'slug', 'title', 'category', 'difficulty', 'description', 'video_embed', 'question_count']);

        $referenceAudioTracksByQuizId = Quiz::query()
            ->whereIn('id', $quizzes->pluck('id'))
            ->pluck('reference_audio_tracks', 'id');

        // One attempt per user per quiz (unique index on user_id + quiz_id), so
        // there's at most one row to key by quiz_id here.
        $attempts = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->get()
            ->keyBy('quiz_id');

        $marks = QuizMark::query()
            ->where('user_id', $user->id)
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->get()
            ->keyBy('quiz_id');

        $real = $quizzes->map(function (Quiz $quiz) use ($attempts, $marks, $referenceAudioTracksByQuizId) {
            $attempt = $attempts->get($quiz->id);
            $mark = $marks->get($quiz->id);

            return [
                'available' => true,
                'slug' => $quiz->slug,
                'title' => $quiz->title,
                'difficulty' => $quiz->difficulty,
                'description' => $quiz->description,
                'video_embed' => $quiz->video_embed ?? self::DEFAULT_VIDEO_EMBED,
                // Same QUIZ_LENGTH (25) the mobile app falls back to for any
                // quiz without question-specific info — real question_count
                // still drives actual quiz-taking below, this is display only.
                'question_count' => $quiz->question_count > 0 ? $quiz->question_count : 25,
                'sounds' => QuizCatalog::soundsFor($quiz->category, $quiz->title),
                // Every sound this quiz actually has generated audio for — a superset
                // of `sounds` above, since Default-Instrument-only variants (EP, Jazz
                // Guitar, Bass Guitar, Organ, …) get real audio generated for a quiz
                // without ever being one of its pickable category tiles. The preview
                // page uses this (not `sounds`) to decide whether a member's specific
                // Default Instrument should play outright, vs. falling back to
                // whatever this quiz's category tile offers.
                'availableSounds' => $quiz->questions()
                    ->with('audioVariants:quiz_question_id,sound')
                    ->get()
                    ->flatMap(fn ($question) => $question->audioVariants->pluck('sound'))
                    ->unique()
                    ->values(),
                'referenceAudioTracks' => collect($referenceAudioTracksByQuizId->get($quiz->id) ?? [])
                    ->map(fn ($track) => [
                        'id' => $track['id'],
                        'label' => $track['label'],
                        'name' => $track['name'] ?? null,
                        // Which instrument this track is — lets the frontend pick the
                        // one matching the member's Default Instrument automatically
                        // instead of always showing the first track. Null for tracks
                        // that predate this (an admin-uploaded one-off, not part of
                        // the per-instrument scale set).
                        'sound' => $track['sound'] ?? null,
                        'url' => Storage::disk('public')->url($track['path']),
                    ])
                    ->values(),
                'status' => $attempt?->status,
                'score' => $attempt && $attempt->status === QuizAttempt::STATUS_COMPLETED
                    ? (int) round(($attempt->correct_answers / max(1, $quiz->question_count)) * 100)
                    : null,
                'stopped_at_question' => $attempt?->stopped_at_question,
                'bookmarked' => $mark?->bookmarked ?? false,
                'manuallyCompleted' => $mark?->completed ?? false,
            ];
        })->keyBy(fn ($entry) => Str::slug($entry['title']));

        // Every subcategory the app has for this category, minus the ones already
        // covered above by a real quiz, rendered with the same preview content as
        // the mobile app shows before any quiz-specific content is authored:
        // mobile's own QuizPreviewScreen falls back to a generic "Beginner" /
        // QUIZ_LENGTH (25) info card for every subcategory it has no override
        // for (see QUIZ_INFO/FALLBACK_INFO in app/quiz-preview.tsx) — so this
        // mirrors real app behavior, not invented numbers. What's genuinely
        // missing here (and why `available` stays false) is real question
        // content: the web's quiz flow needs pre-authored questions per quiz,
        // while the app generates ear-training audio on the fly.
        $placeholders = collect(QuizCatalog::subcategories($name))
            ->reject(fn ($title) => $real->has(Str::slug($title)))
            ->map(fn ($title) => [
                'available' => false,
                'slug' => Str::slug($title),
                'title' => $title,
                'difficulty' => 'Beginner',
                'description' => null,
                'video_embed' => self::DEFAULT_VIDEO_EMBED,
                'question_count' => 25,
                'sounds' => QuizCatalog::soundsFor($name, $title),
                'availableSounds' => [],
                'referenceAudioTracks' => [],
                'status' => null,
                'score' => null,
                'stopped_at_question' => null,
                'bookmarked' => false,
                'manuallyCompleted' => false,
            ]);

        $total = $real->count() + $placeholders->count();
        $done = $real->filter(fn ($entry) => $entry['status'] === QuizAttempt::STATUS_COMPLETED)->count();

        // $real is keyed by slug in whatever order the DB query returned (not
        // catalog order), and simply appending it before $placeholders would
        // put a real quiz that's late in the catalog (e.g. "Find the Key #1")
        // ahead of an earlier placeholder/real one (e.g. "Find the Note") —
        // walk the real catalog order instead so the list always matches it,
        // same as the app's own category screen.
        $byCatalogOrder = collect(QuizCatalog::subcategories($name))->map(fn ($title) => Str::slug($title));
        $entries = $real->merge($placeholders->keyBy(fn ($entry) => $entry['slug']));
        $allQuizzes = $byCatalogOrder->map(fn ($slug) => $entries->get($slug))->filter()->values();
        $selectedSlug = $allQuizzes->contains('slug', $quiz) ? $quiz : null;

        return Inertia::render('Member/QuizCategory', [
            'category' => $name,
            'categorySlug' => $category,
            'selectedSlug' => $selectedSlug,
            'quizzes' => $allQuizzes,
            'progress' => [
                'done' => $done,
                'total' => $total,
                'percent' => (int) round(($done / max(1, $total)) * 100),
            ],
        ]);
    }

    public function preview(Request $request, Quiz $quiz): Response
    {
        $this->ensurePublished($quiz);
        $attempt = $this->latestAttempt($request, $quiz);

        return Inertia::render('Member/QuizPreview', [
            'quiz' => [
                'slug' => $quiz->slug,
                'title' => $quiz->title,
                'category' => $quiz->category,
                'difficulty' => $quiz->difficulty,
                'description' => $quiz->description,
                'question_count' => $quiz->question_count,
            ],
            'attempt' => $attempt ? [
                'status' => $attempt->status,
                'stopped_at_question' => $attempt->stopped_at_question,
                'correct_answers' => $attempt->correct_answers,
                'score' => (int) round(($attempt->correct_answers / max(1, $quiz->question_count)) * 100),
            ] : null,
        ]);
    }

    public function toggleBookmark(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->ensurePublished($quiz);
        $mark = $this->markFor($request, $quiz);
        $mark->update(['bookmarked' => ! $mark->bookmarked]);

        return back();
    }

    public function toggleCompleted(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->ensurePublished($quiz);
        $mark = $this->markFor($request, $quiz);
        $mark->update(['completed' => ! $mark->completed]);

        return back();
    }

    private function markFor(Request $request, Quiz $quiz): QuizMark
    {
        return QuizMark::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'quiz_id' => $quiz->id,
        ]);
    }

    public function start(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->ensurePublished($quiz);
        $user = $request->user();
        $sound = $this->resolveSound($request, $quiz);

        // The schema allows only one attempt per user per quiz (a unique index
        // on user_id + quiz_id), so there's no attempt history to preserve —
        // "retry" means resetting this same row back to question 1, not a new one.
        $attempt = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->first();

        if (! $attempt) {
            QuizAttempt::query()->create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'status' => QuizAttempt::STATUS_IN_PROGRESS,
                'stopped_at_question' => 1,
                'question_sequence' => $this->buildQuestionSequence($quiz, $sound),
                'reference_note_sequence' => $this->buildReferenceNoteSequence($quiz),
                'sound' => $sound,
                'correct_answers' => 0,
                'last_played_at' => now(),
            ]);
        } elseif ($attempt->status === QuizAttempt::STATUS_COMPLETED) {
            $attempt->answers()->delete();
            $attempt->update([
                'status' => QuizAttempt::STATUS_IN_PROGRESS,
                'stopped_at_question' => 1,
                'question_sequence' => $this->buildQuestionSequence($quiz, $sound),
                'reference_note_sequence' => $this->buildReferenceNoteSequence($quiz),
                'sound' => $sound,
                'correct_answers' => 0,
                'last_played_at' => now(),
                'completed_at' => null,
            ]);
        } else {
            // Resuming an in-progress attempt — the preview's sound picker is shown
            // (and re-choosable) every time, so pick up whatever's selected now
            // rather than leaving the attempt on whatever sound (or none) it had
            // before, without touching its actual progress. Also backfills
            // question_sequence for attempts created before that column existed
            // (it was null, so playProps() couldn't resolve a question at all) —
            // only when still null, so an attempt already mid-sequence keeps it.
            $attempt->update([
                'sound' => $sound,
                'question_sequence' => $attempt->question_sequence ?? $this->buildQuestionSequence($quiz, $sound),
                'reference_note_sequence' => $attempt->reference_note_sequence ?? $this->buildReferenceNoteSequence($quiz),
            ]);
        }

        return redirect()->route('quiz.play', $quiz);
    }

    public function play(Request $request, Quiz $quiz): Response|RedirectResponse
    {
        $this->ensurePublished($quiz);
        $attempt = $this->activeAttempt($request, $quiz);

        if (! $attempt) {
            return redirect()->route('quiz.preview', $quiz);
        }

        return Inertia::render('Member/QuizPlay', $this->playProps($request, $quiz, $attempt));
    }

    // Grades the current question in place — the attempt's position only moves
    // forward from next(), so the member keeps seeing this same question (with
    // their choice highlighted and locked) until they choose to continue,
    // matching the mobile app's Submit → Next Question flow instead of jumping
    // straight to the next question's blank state.
    public function answer(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->ensurePublished($quiz);
        $data = $request->validate(['choice' => ['required', 'string']]);

        $attempt = $this->activeAttempt($request, $quiz);
        if (! $attempt) {
            return redirect()->route('quiz.preview', $quiz);
        }

        $position = $attempt->stopped_at_question;
        $sessionKey = $this->feedbackSessionKey($attempt, $position);

        // Already graded (a resubmit, or the page was simply reloaded) — don't grade or count it twice.
        if (! $request->session()->has($sessionKey)) {
            $question = QuizQuestion::find($attempt->questionIdAt($position));
            if (! $question) {
                return redirect()->route('quiz.play', $quiz);
            }

            $correct = $data['choice'] === $question->answer;
            $attempt->correct_answers += $correct ? 1 : 0;
            $attempt->last_played_at = now();
            $attempt->save();

            // Logged per question (not just the running tally) so the results page can show
            // a real per-question review, same as the mobile app's local answer history.
            $attempt->answers()->updateOrCreate(
                ['position' => $position],
                ['quiz_question_id' => $question->id, 'picked' => $data['choice'], 'correct' => $correct],
            );

            $request->session()->put($sessionKey, [
                'correct' => $correct,
                'chosen' => $data['choice'],
                'correctAnswer' => $question->answer,
            ]);
        }

        // Redirect (not render) so the browser's URL/history points at the
        // GET question page, not this POST-only endpoint — rendering
        // directly here left `/answer` as the "current page," and refreshing
        // it issued a GET that 405'd. The GET route reads the same
        // session-stored feedback via playProps(), so the result looks
        // identical; it's just safe to reload.
        return redirect()->route('quiz.play', $quiz);
    }

    // Moves on from a question that's already been graded — refuses to advance
    // an unanswered one, so the position can't skip ahead without a real answer.
    public function next(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->ensurePublished($quiz);
        $attempt = $this->activeAttempt($request, $quiz);
        if (! $attempt) {
            return redirect()->route('quiz.preview', $quiz);
        }

        $position = $attempt->stopped_at_question;
        $sessionKey = $this->feedbackSessionKey($attempt, $position);
        if (! $request->session()->has($sessionKey)) {
            return redirect()->route('quiz.play', $quiz);
        }

        $request->session()->forget($sessionKey);
        $attempt->stopped_at_question += 1;

        if ($attempt->stopped_at_question > $quiz->question_count) {
            $attempt->status = QuizAttempt::STATUS_COMPLETED;
            $attempt->completed_at = now();
            $attempt->save();

            return redirect()->route('quiz.results', $quiz);
        }

        $attempt->save();

        return redirect()->route('quiz.play', $quiz);
    }

    // The mobile app keeps no mid-quiz position at all (practice.tsx's state is purely
    // in-memory), so cancelling always starts over next time, not resumes — mirrored
    // here by resetting the attempt back to question 1 instead of just leaving the page.
    public function cancel(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->ensurePublished($quiz);
        $attempt = $this->activeAttempt($request, $quiz);

        if ($attempt) {
            $request->session()->forget($this->feedbackSessionKey($attempt, $attempt->stopped_at_question));
            $attempt->answers()->delete();
            $attempt->update([
                'stopped_at_question' => 1,
                'question_sequence' => $this->buildQuestionSequence($quiz, $attempt->sound),
                'reference_note_sequence' => $this->buildReferenceNoteSequence($quiz),
                'correct_answers' => 0,
            ]);
        }

        return redirect()->route('quiz.category', ['category' => Str::slug($quiz->category), 'quiz' => $quiz->slug]);
    }

    public function results(Request $request, Quiz $quiz): Response
    {
        $attempt = QuizAttempt::query()
            ->where('user_id', $request->user()->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', QuizAttempt::STATUS_COMPLETED)
            ->latest('completed_at')
            ->firstOrFail();

        return Inertia::render('Member/QuizResults', [
            'quiz' => [
                'slug' => $quiz->slug,
                'title' => $quiz->title,
                'category' => $quiz->category,
                'categorySlug' => Str::slug($quiz->category),
                'question_count' => $quiz->question_count,
            ],
            'result' => [
                'correct' => $attempt->correct_answers,
                'total' => $quiz->question_count,
                'percent' => (int) round(($attempt->correct_answers / max(1, $quiz->question_count)) * 100),
            ],
            'review' => $attempt->answers()
                ->with('question.audioVariants')
                ->orderBy('position')
                ->get()
                ->map(fn (QuizAttemptAnswer $answer) => [
                    'position' => $answer->position,
                    'picked' => $answer->picked,
                    'correct' => $answer->correct,
                    'answer' => $answer->question->answer,
                    // The same sound this attempt was actually played in, not a fixed
                    // instrument — null if the quiz has no generated audio for it.
                    'audio_url' => $this->questionAudioUrl($answer->question, $attempt->sound),
                ])
                ->values(),
        ]);
    }

    private function resolveCategory(string $slug): string
    {
        $match = collect(Quiz::CATEGORIES)->first(fn ($name) => Str::slug($name) === $slug);
        abort_if($match === null, 404);

        return $match;
    }

    private function ensurePublished(Quiz $quiz): void
    {
        abort_unless($quiz->is_published, 404);
    }

    private function latestAttempt(Request $request, Quiz $quiz): ?QuizAttempt
    {
        return QuizAttempt::query()
            ->where('user_id', $request->user()->id)
            ->where('quiz_id', $quiz->id)
            ->latest('last_played_at')
            ->first();
    }

    private function activeAttempt(Request $request, Quiz $quiz): ?QuizAttempt
    {
        return QuizAttempt::query()
            ->where('user_id', $request->user()->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', QuizAttempt::STATUS_IN_PROGRESS)
            ->latest('last_played_at')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return list<string>
     */
    private function choicesFor(Quiz $quiz, QuizQuestion $question): array
    {
        $preset = QuizAnswerPresets::presetFor($quiz->category, $quiz->title);

        if ($preset && in_array($question->answer, $preset, true)) {
            return $preset;
        }

        return $question->choices;
    }

    private function feedbackSessionKey(QuizAttempt $attempt, int $position): string
    {
        return "quiz-feedback:{$attempt->id}:{$position}";
    }

    // A fair shuffle of the quiz's full question pool, cycled as many times as
    // needed to reach question_count: a pool smaller than question_count (e.g.
    // "Find the Note"'s 12 distinct notes) repeats — each full lap reshuffled, so
    // repeats land roughly evenly rather than some notes showing up twice before
    // others show up once — and a pool at or above question_count never repeats
    // at all, since the first lap alone covers every slot.
    // $sound narrows the pool to questions that actually have audio in that sound
    // (e.g. Relative Tone Pitch #2's octaves cap per instrument's real playable
    // range — Violin's pool is smaller than Piano's) — but only when the quiz
    // actually has any sound-restricted rows at all, so quizzes where every
    // question carries every sound (Find the Note, Relative Tone Pitch #1) keep
    // drawing from their full pool exactly as before.
    private function buildQuestionSequence(Quiz $quiz, ?string $sound = null): array
    {
        $query = $quiz->questions();
        if ($sound) {
            $withSound = (clone $query)->whereHas('audioVariants', fn ($q) => $q->where('sound', $sound));
            if ($withSound->exists()) {
                $query = $withSound;
            }
        }

        $poolIds = $query->pluck('id')->all();

        return $this->fairLappedShuffle($poolIds, max(1, $quiz->question_count));
    }

    // Find the Key #2's reference note, shuffled fresh per question the same
    // fair way question_sequence is — previously a fixed "G" for every question,
    // which isn't a real shuffle at all. Null for every other quiz.
    private function buildReferenceNoteSequence(Quiz $quiz): ?array
    {
        if ($quiz->title !== 'Find the Key #2') {
            return null;
        }

        return $this->fairLappedShuffle(array_keys(self::NOTE_SLUGS), max(1, $quiz->question_count));
    }

    // A pool smaller than $target (the common case — a 7-12 item pool against 25
    // draws) repeats as evenly as possible: each full lap through the pool is
    // independently reshuffled, then concatenated, so no item appears twice
    // before every other item has appeared once, and any item at or above the
    // target length never repeats at all since the first lap alone covers it.
    private function fairLappedShuffle(array $pool, int $target): array
    {
        if ($pool === []) {
            return [];
        }

        $sequence = [];
        while (count($sequence) < $target) {
            $lap = $pool;
            shuffle($lap);

            // Each lap is independently shuffled, so the previous lap's last
            // item and this lap's first item can coincide — e.g. a 6-item
            // pool (Basic Triads · Level 3) has roughly 1-in-6 odds of that
            // at every lap boundary, which is exactly what produced two
            // "Minor" questions back to back. Swap the new lap's first item
            // with another of its own positions (never reaching into the
            // previous lap) whenever that would repeat — impossible to avoid
            // only when the whole pool is a single item, where a repeat is
            // unavoidable and expected.
            if ($sequence !== [] && count($lap) > 1 && end($sequence) === $lap[0]) {
                $swapWith = random_int(1, count($lap) - 1);
                [$lap[0], $lap[$swapWith]] = [$lap[$swapWith], $lap[0]];
            }

            $sequence = array_merge($sequence, $lap);
        }

        return array_slice($sequence, 0, $target);
    }

    // The sound chosen on the quiz preview's "Choose a sound" picker, validated
    // against this quiz's real option list — falls back to the first option rather
    // than trusting an unvalidated request value, same defensive stance as the
    // preview's own fallback. Null for quizzes with no sound options at all.
    // Accepts either one of the quiz's official picker options, or any sound that
    // actually has generated audio for this quiz — the second clause matters for
    // Default-Instrument-only variants (EP, Jazz Guitar, Bass Guitar, Organ, …):
    // they're deliberately never a picker option, but a member's saved default
    // still needs to reach here as-is instead of being silently discarded back to
    // the picker's first option, or picking a category whose default resolves to
    // one of these variants (see QuizCategory.jsx's resolveDefaultSound) would
    // play the wrong instrument despite the frontend choosing correctly.
    // Accepts the requested sound only when this quiz actually has real audio
    // for it — being on the static per-category list (QuizCatalog::soundsFor)
    // isn't enough on its own, since that list only reflects what a category
    // CLAIMS to offer, not what was ever recorded. Without this, a stale page
    // load or a request built before a member's Default Instrument resolution
    // was fixed could submit a sound whose Play button would silently do
    // nothing for the whole attempt. Falls back to the first option that
    // genuinely has audio, not just the static list's first entry.
    private function resolveSound(Request $request, Quiz $quiz): ?string
    {
        $options = QuizCatalog::soundsFor($quiz->category, $quiz->title);
        $requested = $request->string('sound')->toString();

        if ($requested && $this->quizHasAudioFor($quiz, $requested)) {
            return $requested;
        }

        foreach ($options as $option) {
            if ($this->quizHasAudioFor($quiz, $option)) {
                return $option;
            }
        }

        return $options[0] ?? null;
    }

    private function quizHasAudioFor(Quiz $quiz, string $sound): bool
    {
        return $quiz->questions()->whereHas('audioVariants', fn ($query) => $query->where('sound', $sound))->exists();
    }

    // The question's audio in whichever sound the attempt is playing in, from the
    // generated-audio pipeline (quiz_question_audio — see its migration). Falls back
    // to the question's own single audio_path for quizzes the pipeline hasn't
    // reached yet, same as before this existed; null if neither has anything.
    private function questionAudioUrl(QuizQuestion $question, ?string $sound): ?string
    {
        $path = $sound
            ? $question->audioVariants()->where('sound', $sound)->value('audio_path')
            : null;

        $path ??= $question->audio_path;

        return $path ? Storage::disk('public')->url($path) : null;
    }

    // One audio URL per note in this sound, keyed by the note's label exactly as
    // it appears in `choices` (e.g. "C#/Db") — built from this quiz's own question
    // pool, so it only produces anything for quizzes where each pool row's answer
    // is itself one of the playable note names (Find the Note today).
    private function keyboardAudioMap(Quiz $quiz, ?string $sound): array
    {
        if (! $sound) {
            return [];
        }

        return $quiz->questions()
            ->with('audioVariants')
            ->get()
            ->mapWithKeys(function (QuizQuestion $question) use ($sound) {
                $path = $question->audioVariants->firstWhere('sound', $sound)?->audio_path;

                return $path ? [$question->answer => Storage::disk('public')->url($path)] : [];
            })
            ->all();
    }

    // Fallback for any "tap a note name" keyboard whose own quiz has no per-
    // instrument note audio in its pool to resolve from (Find the Key #1/#2 —
    // their own `sound` picker is song-recording types like "Monophonic", not an
    // instrument, so keyboardAudioMap() above always comes back empty for them).
    // The keyboard there is a generic "hear this note" widget, so it plays the
    // member's own Default Instrument from the canonical chromatic note set
    // instead, independent of whatever sound the quiz itself is playing in.
    // $onlyNote restricts the map to a single key — Find the Key #2's reference
    // note is the only key that should ever make a sound; every other key is
    // deliberately muted (no entry at all, so NoteKeyboardChoices' press() has
    // nothing to play). Find the Key #1 has no reference note, so it passes
    // null here and gets the full 12-key map, same as before.
    // Every instrument's chromatic set lives at bare notes/{slug}/{note}.wav
    // (the quiz's base C3 octave) except where listed here — Bass Guitar's own
    // base octave was moved down to C2-C3 (a real bass's open low string sits
    // closer to C2 than C3), but its bare files stay C3 since RTP2 still reuses
    // them as its own C3-octave entries; this keyboard reads the "-c2" set
    // instead so it matches Find the Note/RTP1's octave, not RTP2's.
    private const KEYBOARD_NOTE_SUFFIXES = [
        'Bass Guitar' => '-c2',
    ];

    private function defaultInstrumentKeyboardAudio(Request $request, ?string $onlyNote = null): array
    {
        $sound = $request->user()?->default_instrument;
        $slug = self::SOUND_SLUGS[$sound] ?? self::SOUND_SLUGS['Piano'];
        $suffix = self::KEYBOARD_NOTE_SUFFIXES[$sound] ?? '';
        $notes = $onlyNote ? array_intersect_key(self::NOTE_SLUGS, [$onlyNote => true]) : self::NOTE_SLUGS;

        return collect($notes)
            ->mapWithKeys(fn ($noteSlug, $note) => [$note => Storage::disk('public')->url("notes/{$slug}/{$noteSlug}{$suffix}.wav")])
            ->all();
    }

    private function playProps(Request $request, Quiz $quiz, QuizAttempt $attempt): array
    {
        $finished = $attempt->status === QuizAttempt::STATUS_COMPLETED;
        $position = $attempt->stopped_at_question;
        $question = $finished
            ? null
            : QuizQuestion::find($attempt->questionIdAt($position));

        $feedback = $finished ? null : $request->session()->get($this->feedbackSessionKey($attempt, $position));
        $referenceNote = $finished ? null : $attempt->referenceNoteAt($position);

        return [
            'quiz' => [
                'slug' => $quiz->slug,
                'title' => $quiz->title,
                'category' => $quiz->category,
                'categorySlug' => Str::slug($quiz->category),
            ],
            'progress' => [
                'current' => min($position, $quiz->question_count),
                'total' => $quiz->question_count,
                'correct' => $attempt->correct_answers,
            ],
            'question' => $question ? [
                'prompt' => $question->prompt,
                // The real app shows a fixed set of options per quiz (not a
                // grab-bag per question) — switch to that canonical list only
                // when it actually contains this question's real answer, so
                // an older question seeded with different choices never ends
                // up with its correct answer missing from the options shown.
                'choices' => $this->choicesFor($quiz, $question),
                'audio_url' => $this->questionAudioUrl($question, $attempt->sound),
                // For variable-length sequence quizzes (Dual-tonic/Chordal
                // melodies), lets the member-facing keyboard size its answer
                // slots to the real question instead of asking the member to
                // guess the note count first — only the COUNT is exposed
                // here, never the actual notes, so this can't be used to
                // cheat the answer itself. Chord-progression answers (e.g.
                // "Re Minor - Do Major") use " - " as the per-chord
                // separator instead of a plain space, since quality labels
                // like "Minor 7th" contain spaces of their own.
                'answer_length' => str_contains($question->answer, ' - ')
                    ? count(explode(' - ', $question->answer))
                    : (str_contains($question->answer, ' ') ? count(explode(' ', $question->answer)) : 1),
                // Lets the on-screen keyboard play each key — in the attempt's chosen
                // sound where the quiz's own pool has it (Find the Note), or the
                // member's Default Instrument where it doesn't (Find the Key #1/#2,
                // whose own "sound" is a song-recording type, not an instrument).
                // Find the Key #2 restricts this to just the reference note — every
                // other key is deliberately muted. Still empty for every other quiz,
                // so the keyboard stays silent on tap there, same as before.
                'keyboard_audio' => $this->keyboardAudioMap($quiz, $attempt->sound)
                    ?: $this->defaultInstrumentKeyboardAudio($request, $referenceNote),
                // Which key the keyboard highlights as the reference note (Find the
                // Key #2 only) — shuffled fresh per question, not a fixed "G".
                'reference_note' => $referenceNote,
            ] : null,
            'finished' => $finished,
            'feedback' => $feedback,
        ];
    }
}
