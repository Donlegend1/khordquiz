<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizMark;
use App\Models\QuizQuestion;
use App\Models\QuizSession;
use App\Support\AddNineSamples;
use App\Support\AeolianScaleAllKeysSamples;
use App\Support\AeolianScaleSamples;
use App\Support\AeolianVoicingSamples;
use App\Support\BasicChordNamingSamples;
use App\Support\BasicTriadSamples;
use App\Support\CadenceSamples;
use App\Support\ChordNamingAnswer;
use App\Support\ChordToneRecognitionSamples;
use App\Support\DiatonicProgressionSamples;
use App\Support\DiatonicSeventhProgressionSamples;
use App\Support\DominantChordExtensionSamples;
use App\Support\DorianAeolianScaleSamples;
use App\Support\DorianScaleAllKeysSamples;
use App\Support\DorianScaleSamples;
use App\Support\DorianVoicingSamples;
use App\Support\EleventhDegreeChordSamples;
use App\Support\IntervalSamples;
use App\Support\IonianLydianScaleSamples;
use App\Support\IonianScaleAllKeysSamples;
use App\Support\IonianScaleSamples;
use App\Support\IonianVoicingSamples;
use App\Support\LocrianScaleAllKeysSamples;
use App\Support\LocrianScaleSamples;
use App\Support\LocrianVoicingSamples;
use App\Support\LydianScaleAllKeysSamples;
use App\Support\LydianScaleSamples;
use App\Support\MajorChordExtensionSamples;
use App\Support\MajorModeVoicingSamples;
use App\Support\MajorScaleAllKeysSamples;
use App\Support\MajorScaleSamples;
use App\Support\MinorChordExtensionSamples;
use App\Support\MinorModeVoicingSamples;
use App\Support\MixolydianScaleAllKeysSamples;
use App\Support\MixolydianScaleSamples;
use App\Support\MixolydianVoicingSamples;
use App\Support\ModalHarmonySamples;
use App\Support\ModalVoicingLessons;
use App\Support\ModalVoicingSamples;
use App\Support\NinthDegreeChordSamples;
use App\Support\NoteSample;
use App\Support\PhrygianLocrianScaleSamples;
use App\Support\PhrygianScaleAllKeysSamples;
use App\Support\PhrygianScaleSamples;
use App\Support\PhrygianVoicingSamples;
use App\Support\ProgressionBuildSamples;
use App\Support\QuizAnswerPresets;
use App\Support\QuizCatalog;
use App\Support\ReferenceAudioTracks;
use App\Support\SecondaryEleventhChordSamples;
use App\Support\SecondaryNinthChordSamples;
use App\Support\SecondarySeventhChordSamples;
use App\Support\SeventhDegreeChordSamples;
use App\Support\SeventhProgressionBuildSamples;
use App\Support\ThirteenthDegreeChordSamples;
use App\Support\TonalModeAllKeysSamples;
use App\Support\TonalModeScaleSamples;
use App\Support\TwoFiveOneExtensionSamples;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    // The canonical chromatic note set generated for "Find the Note" (see
    // scripts/audio/) — reused by any other "tap a note name" keyboard (Find the
    // Key #1/#2) as a generic note-audio lookup, independent of that quiz's own
    // question pool.
    private const NOTE_SLUGS = [
        'C' => 'c', 'C#/Db' => 'csharp', 'D' => 'd', 'D#/Eb' => 'dsharp', 'E' => 'e', 'F' => 'f',
        'F#/Gb' => 'fsharp', 'G' => 'g', 'G#/Ab' => 'gsharp', 'A' => 'a', 'A#/Bb' => 'asharp', 'B' => 'b',
    ];

    // Instrument name → storage folder slug — see QuizCatalog::SOUND_SLUGS
    // (moved there so AudioQuizController can reuse it too).

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

        $lastSessions = QuizSession::query()
            ->where('user_id', $user->id)
            ->whereIn('slug', $quizzes->pluck('slug'))
            ->where('total', '>', 0)
            ->orderByDesc('played_at')
            ->orderByDesc('id')
            ->get()
            ->unique('slug')
            ->keyBy('slug');

        $referenceAudioPresenter = new ReferenceAudioTracks;

        // Distinct (quiz_id, sound) pairs for every quiz in this category, in
        // one query — not per-quiz model hydration. A quiz like "Chordal
        // Progressions" can have 1,000+ question rows with ~20 audio variants
        // each; loading those as Eloquent models just to read `sound` off
        // each one exhausted PHP's memory limit once that quiz's bank grew
        // past a few hundred rows (real 500 on GET /quiz/chord-progressions).
        $attachedSoundsByQuizId = DB::table('quiz_question_audio')
            ->join('quiz_questions', 'quiz_questions.id', '=', 'quiz_question_audio.quiz_question_id')
            ->whereIn('quiz_questions.quiz_id', $quizzes->pluck('id'))
            ->select('quiz_questions.quiz_id', 'quiz_question_audio.sound')
            ->distinct()
            ->get()
            ->groupBy('quiz_id');

        $real = $quizzes->toBase()->map(function (Quiz $quiz) use ($attempts, $marks, $referenceAudioTracksByQuizId, $lastSessions, $referenceAudioPresenter, $attachedSoundsByQuizId) {
            $attempt = $attempts->get($quiz->id);
            $mark = $marks->get($quiz->id);
            $lastSession = $lastSessions->get($quiz->slug);

            // Every sound this quiz actually has pre-generated audio attached
            // for, regardless of static category/title tables — ground truth
            // from the DB rather than a hand-maintained list, so a newly-built
            // instrument's tile appears automatically without another code
            // change here. (Melodic Dictation combo files are built/attached
            // per instrument per quiz over time — a static list would always
            // lag behind what's actually been recorded.)
            $attachedSounds = collect($attachedSoundsByQuizId->get($quiz->id) ?? [])
                ->pluck('sound')
                ->unique()
                // Only the current 27-instrument roster — some quizzes still
                // carry attached rows under pre-consolidation names (Harp,
                // Jazz Guitar) that predate today's Harpejji/guitar-family
                // lineup and were never re-added to SOUND_SLUGS; those stay
                // out of the picker rather than resurfacing as a tile.
                ->filter(fn ($sound) => isset(QuizCatalog::SOUND_SLUGS[$sound]))
                ->values();

            return [
                'available' => true,
                'slug' => $quiz->slug,
                'title' => $quiz->title,
                'category' => $quiz->category,
                'difficulty' => $quiz->difficulty,
                'description' => $quiz->description,
                'video_embed' => $quiz->video_embed,
                // Same QUIZ_LENGTH (25) the mobile app falls back to for any
                // quiz without question-specific info — real question_count
                // still drives actual quiz-taking below, this is display only.
                'question_count' => $quiz->question_count > 0 ? $quiz->question_count : 25,
                // Merges in every instrument the live-mixing engine alone can
                // already cover (see liveMixAvailableSounds) — naturally a
                // no-op for every category it doesn't support yet (Melodic
                // Dictation, chord quizzes, …), since NoteSample::fromAnswer()
                // only ever resolves a Relative Pitch-shaped single-note or
                // interval answer, so this can't add a tile with nothing
                // behind it to an unsupported quiz type — plus every instrument
                // with real attached audio above, so the picker always reflects
                // what's actually been recorded instead of a hand-curated list
                // going stale as more instruments get built.
                'sounds' => collect(QuizCatalog::soundsFor($quiz->category, $quiz->title))
                    ->merge($liveMixSounds = $this->liveMixAvailableSounds($quiz))
                    ->merge($attachedSounds)
                    ->unique()
                    ->values(),
                // Every sound this quiz actually has generated audio for — a superset
                // of `sounds` above only in that it also covers whatever
                // liveMixAvailableSounds adds; kept for the preview page's
                // Default-Instrument fallback logic.
                'availableSounds' => $attachedSounds
                    ->merge($liveMixSounds)
                    ->unique()
                    ->values(),
                // Just the distinct-label list, no audio resolved — see
                // ReferenceAudioTracks::pool() and referenceAudioClip() above.
                'referenceAudioPool' => $referenceAudioPresenter->pool($referenceAudioTracksByQuizId->get($quiz->id) ?? []),
                'status' => $attempt?->status,
                'score' => $attempt && $attempt->status === QuizAttempt::STATUS_COMPLETED
                    ? (int) round(($attempt->correct_answers / max(1, $quiz->question_count)) * 100)
                    : ($lastSession ? (int) round(($lastSession->correct / $lastSession->total) * 100) : null),
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
                'category' => $name,
                'difficulty' => 'Beginner',
                'description' => null,
                'video_embed' => null,
                'question_count' => 25,
                'sounds' => QuizCatalog::soundsFor($name, $title),
                'availableSounds' => [],
                'referenceAudioPool' => [],
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

    public function preview(Quiz $quiz): RedirectResponse
    {
        $this->ensurePublished($quiz);

        return redirect()->route('quiz.category', [
            'category' => Str::slug($quiz->category),
            'quiz' => $quiz->slug,
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

    // Lazy counterpart to the 'referenceAudioPool' list sent on the category
    // page: that list is just distinct labels (free to compute, even for a
    // quiz with a huge question pool), with no audio resolved at all. This
    // resolves exactly one clip, for one instrument, on demand — called once
    // per Listen Mode "Next" press, never for the whole pool up front. Keeps
    // a quiz with hundreds or thousands of distinct answers from resolving
    // (28 instruments x every answer) on every single category page load.
    public function referenceAudioClip(Request $request, Quiz $quiz, string $group): JsonResponse
    {
        $this->ensurePublished($quiz);

        $sound = $request->string('sound')->toString();
        $clip = $sound ? (new ReferenceAudioTracks)->resolveOne($quiz->reference_audio_tracks ?? [], $group, $sound) : null;

        return response()->json(['clip' => $clip]);
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
        // on user_id + quiz_id). Completed runs are archived with individual
        // answer snapshots before this active row is reset for a retry.
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
            $this->archiveCompletedAttempt($attempt);
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
            if ($quiz->title === 'Find the Pitch') {
                $gradedCount = $attempt->stopped_at_question - 1;
                if ($request->session()->has($this->feedbackSessionKey($attempt, $attempt->stopped_at_question))) {
                    $gradedCount++;
                }
                $slug = $sound ? QuizCatalog::SOUND_SLUGS[$sound] ?? null : null;
                $remainingIds = array_slice($attempt->question_sequence ?? [], $gradedCount);
                $hasUnavailablePitch = $quiz->questions()->whereIn('id', $remainingIds)->get()
                    ->contains(fn (QuizQuestion $question) => $slug === null || NoteSample::fromScientificPitchAnswer($question->answer, $slug) === null);
                if ($attempt->sound !== $sound || $hasUnavailablePitch) {
                    $attempt->question_sequence = array_merge(
                        array_slice($attempt->question_sequence ?? [], 0, $gradedCount),
                        array_slice($this->buildQuestionSequence($quiz, $sound), 0, max(0, $quiz->question_count - $gradedCount)),
                    );
                }
            }
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
            return redirect()->route('quiz.category', ['category' => Str::slug($quiz->category), 'quiz' => $quiz->slug]);
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
            return redirect()->route('quiz.category', ['category' => Str::slug($quiz->category), 'quiz' => $quiz->slug]);
        }

        $position = $attempt->stopped_at_question;
        $sessionKey = $this->feedbackSessionKey($attempt, $position);

        // Already graded (a resubmit, or the page was simply reloaded) — don't grade or count it twice.
        if (! $request->session()->has($sessionKey)) {
            $question = QuizQuestion::find($attempt->questionIdAt($position));
            if (! $question) {
                return redirect()->route('quiz.play', $quiz);
            }

            $correct = in_array($quiz->title, ['Chord naming', BasicChordNamingSamples::TITLE, BasicChordNamingSamples::LEVEL_TWO_TITLE, BasicChordNamingSamples::LEVEL_THREE_TITLE, BasicChordNamingSamples::LEVEL_FOUR_TITLE, BasicChordNamingSamples::LEVEL_FIVE_TITLE, BasicChordNamingSamples::LEVEL_SIX_TITLE], true)
                ? ChordNamingAnswer::matches($data['choice'], $question->answer)
                : ($quiz->title === 'Progression Build #1'
                    ? ProgressionBuildSamples::answersMatch($data['choice'], $question->answer)
                    : ($quiz->title === TwoFiveOneExtensionSamples::TITLE ? TwoFiveOneExtensionSamples::answersMatch($data['choice'], $question->answer) : $this->answersMatch($data['choice'], $question->answer)));
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

    // Multi-select quizzes (e.g. "Major/Minor/Dominant Chord Extentions" —
    // ExtensionCheckboxGrid on the frontend) submit the picked tokens
    // comma-joined in TAP order ("selected.join(', ')" in AnswerBuilder.jsx),
    // while the stored correct answer is comma-joined in a fixed canonical
    // order (see scripts/audio/build_major_chord_extensions.php's
    // mceAnswerTokens()) — an exact string match would reject a correct
    // pick made in a different order. Every other answer type's value never
    // contains ", " (comma+space), so falling back to an order-independent
    // token-set comparison only when that substring is present is safe and
    // doesn't change grading for any single-value answer.
    private function answersMatch(string $submitted, string $correct): bool
    {
        if ($submitted === $correct) {
            return true;
        }
        if (! str_contains($correct, ', ')) {
            return false;
        }
        $normalize = fn (string $s) => collect(explode(', ', $s))->map(fn ($t) => trim($t))->sort()->values()->all();

        return $normalize($submitted) === $normalize($correct);
    }

    // Moves on from a question that's already been graded — refuses to advance
    // an unanswered one, so the position can't skip ahead without a real answer.
    public function next(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->ensurePublished($quiz);
        $attempt = $this->activeAttempt($request, $quiz);
        if (! $attempt) {
            return redirect()->route('quiz.category', ['category' => Str::slug($quiz->category), 'quiz' => $quiz->slug]);
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
            $this->archiveCompletedAttempt($attempt);

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
                'sound' => $attempt->sound,
                'correct' => $attempt->correct_answers,
                'total' => $quiz->question_count,
                'percent' => (int) round(($attempt->correct_answers / max(1, $quiz->question_count)) * 100),
            ],
            'review_keyboard_audio' => $quiz->title === 'Find the Pitch' ? $this->pitchKeyboardAudioMap($attempt->sound) : [],
            'review' => $this->reviewRecords($attempt),
            'initial_review' => $request->boolean('review'),
        ]);
    }

    private function reviewRecords(QuizAttempt $attempt): array
    {
        $quiz = $attempt->quiz;

        return $attempt->answers()
            ->with('question.audioVariants')
            ->orderBy('position')
            ->get()
            ->map(fn (QuizAttemptAnswer $answer) => [
                'position' => $answer->position,
                'answer_id' => $answer->id,
                'question_id' => $answer->quiz_question_id,
                'answered_at' => $answer->created_at?->toIso8601String(),
                'prompt' => $answer->question->prompt,
                'choices' => $this->choicesFor($quiz, $answer->question),
                'answer_length' => str_contains($answer->question->answer, ' - ')
                    ? count(explode(' - ', $answer->question->answer))
                    : count(explode(' ', $answer->question->answer)),
                'reference_note' => $attempt->referenceNoteAt($answer->position),
                'picked' => $answer->picked,
                'correct' => $answer->correct,
                'answer' => $answer->question->answer,
                // The same sound this attempt was actually played in, not a fixed
                // instrument — null if the quiz has no generated audio for it.
                'audio_url' => $this->questionAudioUrl($answer->question, $attempt->sound),
                'notes' => $this->questionLiveNotes($quiz, $answer->question, $attempt->sound),
            ])
            ->values()->all();
    }

    private function archiveCompletedAttempt(QuizAttempt $attempt): QuizSession
    {
        $identity = [
            'user_id' => $attempt->user_id, 'slug' => $attempt->quiz->slug,
            'played_at' => $attempt->completed_at ?? $attempt->last_played_at ?? now(),
        ];
        $records = $this->reviewRecords($attempt);
        $answerIds = array_column($records, 'answer_id');
        $sameTime = QuizSession::where($identity)->orderByDesc('id')->get();
        $session = $sameTime->first(fn (QuizSession $saved) => $saved->review_answers !== null
            && array_column($saved->review_answers, 'answer_id') === $answerIds);
        $session ??= $sameTime->first(fn (QuizSession $saved) => $saved->review_answers === null
            && $saved->correct === $attempt->correct_answers && $saved->total === $attempt->quiz->question_count);
        $session ??= new QuizSession($identity);
        if (! $session->exists) {
            $session->correct = $attempt->correct_answers;
            $session->total = $attempt->quiz->question_count;
        }
        if ($session->review_answers === null && $records !== []) {
            $session->review_answers = $records;
        }
        $session->sound ??= $attempt->sound;
        $session->save();

        return $session;
    }

    public function historyReview(Request $request, QuizSession $quizSession): Response
    {
        abort_unless($quizSession->user_id === $request->user()->id, 404);
        $quiz = Quiz::where('slug', $quizSession->slug)->firstOrFail();
        $records = $quizSession->review_answers;
        if ($records === null) {
            $attempt = QuizAttempt::where('user_id', $request->user()->id)->where('quiz_id', $quiz->id)
                ->where('status', QuizAttempt::STATUS_COMPLETED)->where('completed_at', $quizSession->played_at)->first();
            if ($attempt) {
                $quizSession = $this->archiveCompletedAttempt($attempt);
                $records = $quizSession->review_answers;
            }
        }

        return Inertia::render('Member/QuizResults', [
            'quiz' => ['slug' => $quiz->slug, 'title' => $quiz->title, 'category' => $quiz->category,
                'categorySlug' => Str::slug($quiz->category), 'question_count' => $quizSession->total],
            'result' => ['sound' => $quizSession->sound, 'correct' => $quizSession->correct, 'total' => $quizSession->total,
                'percent' => (int) round($quizSession->correct / max(1, $quizSession->total) * 100)],
            'review' => $records ?? [], 'initial_review' => true,
            'history_session_id' => $quizSession->id,
            'review_keyboard_audio' => $quiz->title === 'Find the Pitch' ? $this->pitchKeyboardAudioMap($quizSession->sound) : [],
            'history_notice' => empty($records) ? __('This older result was saved without individual answers. You can practise the quiz again to get a new answer review.') : null,
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

        if (in_array($quiz->title, ['Chord naming', BasicChordNamingSamples::TITLE, BasicChordNamingSamples::LEVEL_TWO_TITLE, BasicChordNamingSamples::LEVEL_THREE_TITLE, BasicChordNamingSamples::LEVEL_FOUR_TITLE, BasicChordNamingSamples::LEVEL_FIVE_TITLE, BasicChordNamingSamples::LEVEL_SIX_TITLE], true)) {
            $poolIds = $query->get()->filter(fn (QuizQuestion $question): bool => ChordNamingAnswer::isValid($question->answer))->pluck('id')->all();
        } elseif ($quiz->title === 'Find the Pitch') {
            $slug = $sound ? QuizCatalog::SOUND_SLUGS[$sound] ?? null : null;
            $poolIds = $slug ? $query->get()
                ->filter(fn (QuizQuestion $question) => NoteSample::fromScientificPitchAnswer($question->answer, $slug) !== null)
                ->pluck('id')->all() : [];
        } else {
            $poolIds = $query->pluck('id')->all();
        }

        $sequence = $this->fairLappedShuffle($poolIds, max(1, $quiz->question_count));
        if ($quiz->category === ModalVoicingSamples::CATEGORY && in_array($quiz->title, [ModalVoicingSamples::TITLE, MixolydianVoicingSamples::TITLE, PhrygianVoicingSamples::TITLE, LocrianVoicingSamples::TITLE, DorianVoicingSamples::TITLE, AeolianVoicingSamples::TITLE, IonianVoicingSamples::TITLE, MajorModeVoicingSamples::TITLE, MinorModeVoicingSamples::TITLE], true) || ($quiz->category === ModalVoicingSamples::CATEGORY && in_array($quiz->title, ModalVoicingLessons::titles(), true)) || ($quiz->category === ModalHarmonySamples::CATEGORY && in_array($quiz->title, ModalHarmonySamples::titles(), true)) || ($quiz->category === TonalModeScaleSamples::CATEGORY && in_array($quiz->title, [TonalModeScaleSamples::TITLE, TonalModeAllKeysSamples::TITLE, DorianScaleSamples::TITLE, DorianScaleAllKeysSamples::TITLE, LydianScaleSamples::TITLE, LydianScaleAllKeysSamples::TITLE, PhrygianScaleSamples::TITLE, PhrygianScaleAllKeysSamples::TITLE, MixolydianScaleSamples::TITLE, MixolydianScaleAllKeysSamples::TITLE, AeolianScaleSamples::TITLE, AeolianScaleAllKeysSamples::TITLE, LocrianScaleSamples::TITLE, LocrianScaleAllKeysSamples::TITLE, IonianScaleSamples::TITLE, IonianScaleAllKeysSamples::TITLE, IonianLydianScaleSamples::TITLE, DorianAeolianScaleSamples::TITLE, PhrygianLocrianScaleSamples::TITLE, MajorScaleSamples::TITLE, MajorScaleAllKeysSamples::TITLE], true))) {
            shuffle($sequence);
        }

        return $sequence;
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
    // Default-Instrument-only variants (EP, Harpejji, Bass Guitar, Organ, …):
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

        $default = $request->user()?->default_instrument;
        if ($default && $this->quizHasAudioFor($quiz, $default)) {
            return $default;
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
        if ($quiz->title === 'Find the Pitch') {
            return $quiz->questions()->get()->contains(
                fn (QuizQuestion $question) => $this->liveNotesFromAnswer($quiz, $question, $sound) !== null
            );
        }

        if ($quiz->questions()->whereHas('audioVariants', fn ($query) => $query->where('sound', $sound))->exists()) {
            return true;
        }

        // Live-mixing fallback — see questionLiveNotes()/liveNotesFromAnswer().
        // No quiz_question_audio row exists for this sound on any question
        // here, but the instrument might still have real note samples
        // recorded; checked against one real question's answer (not every
        // question) as a representative sample of the quiz's note range.
        $sample = $quiz->questions()->first();

        return $sample && $this->liveNotesFromAnswer($quiz, $sample, $sound) !== null;
    }

    // Every sound the live-mixing engine alone can already cover for this
    // quiz, with no quiz_question_audio rows pre-attached at all — merged
    // into 'availableSounds' in category() so the member-facing sound
    // picker shows (and can actually play) an instrument like this, not
    // just ones the old per-quiz generation pipeline already touched.
    //
    // @return list<string>
    private function liveMixAvailableSounds(Quiz $quiz): array
    {
        if ($quiz->title === 'Find the Pitch') {
            $questions = $quiz->questions()->get();

            return collect(QuizCatalog::SOUND_SLUGS)->keys()
                ->filter(fn (string $sound) => $questions->contains(
                    fn (QuizQuestion $question) => $this->liveNotesFromAnswer($quiz, $question, $sound) !== null
                ))->values()->all();
        }

        $sample = $quiz->questions()->first();
        if (! $sample) {
            return [];
        }

        return collect(QuizCatalog::SOUND_SLUGS)
            ->keys()
            ->filter(fn (string $sound) => $this->liveNotesFromAnswer($quiz, $sample, $sound) !== null)
            ->values()
            ->all();
    }

    // The question's audio storage path in whichever sound the attempt is playing
    // in, from the generated-audio pipeline (quiz_question_audio — see its
    // migration). Falls back to the question's own single audio_path for quizzes
    // the pipeline hasn't reached yet, same as before this existed; null if
    // neither has anything.
    private function questionAudioPath(QuizQuestion $question, ?string $sound): ?string
    {
        $path = $sound
            ? $question->audioVariants()->where('sound', $sound)->value('audio_path')
            : null;

        return $path ?? $question->audio_path;
    }

    private function questionAudioUrl(QuizQuestion $question, ?string $sound): ?string
    {
        $path = $this->questionAudioPath($question, $sound);

        return $path ? NoteSample::url($path) : null;
    }

    // Live-mixing engine data: the individual note sample(s) to combine at
    // playback time, derived from the same storage path questionAudioUrl()
    // resolves (see NoteSample) — no separate "which notes" column needed.
    // Null wherever NoteSample doesn't recognize the path shape (anything
    // outside the Relative Pitch category today), so the frontend falls back
    // to playing audio_url directly as one pre-rendered file, same as before
    // this existed.
    //
    // @return list<array{url: string, offset: float}>|null
    private function questionLiveNotes(Quiz $quiz, QuizQuestion $question, ?string $sound): ?array
    {
        // Scientific answers remain authoritative when a completed GM bank
        // replaces legacy sharp filenames with explicit MIDI filenames.
        if ($quiz->title === 'Find the Pitch') {
            return $this->liveNotesFromAnswer($quiz, $question, $sound);
        }

        $path = $this->questionAudioPath($question, $sound);
        $notes = $path ? NoteSample::resolve($path) : null;

        if ($notes) {
            return array_map(fn (array $note) => [
                'url' => NoteSample::url(NoteSample::path($note['slug'], $note['degree'], $note['octave'])),
                'offset' => $note['offset'],
            ], $notes);
        }

        // No quiz_question_audio row for this instrument at all — but it
        // might still have real note samples recorded, just never attached
        // to this quiz specifically. Build straight from the answer instead
        // of requiring a pre-generated combination file, same fallback the
        // admin editor's "Live mix" preview uses for unattached instruments
        // (AudioQuizController::missingInstrumentPreviews) — this is what
        // actually makes a newly-recorded instrument playable by a real
        // student without a one-off attach script per quiz.
        return $this->liveNotesFromAnswer($quiz, $question, $sound);
    }

    // @return list<array{url: string, offset: float}>|null
    private function liveNotesFromAnswer(Quiz $quiz, QuizQuestion $question, ?string $sound): ?array
    {
        // A sound only ever plays when it's actually on this quiz's own
        // curated instrument list (QuizCatalog::soundsFor()). The live-mixing
        // branches below can technically produce correct audio for almost
        // any instrument whose recorded range happens to cover a given
        // answer — e.g. Bass Guitar easily fits inside a basic triad's
        // narrow range — but several instruments (Bass Guitar, Upright Bass,
        // Tuba) are deliberately left off the chord/progression categories'
        // lists regardless of range. This one gate is what the sound picker,
        // resolveSound(), and liveMixAvailableSounds() all rely on to keep
        // those instruments from leaking back in — it replaces the narrower
        // title/category list this used to hardcode, which missed several
        // categories (Basic Triads, 7th-13th Degree Chords, Chord Tone
        // Recognition, Chord Progressions' other titles, …) that exclude the
        // same three instruments via QuizCatalog but never got added here.
        if ($sound !== null && ! in_array($sound, QuizCatalog::soundsFor($quiz->category, $quiz->title), true)) {
            return null;
        }

        $slug = $sound ? QuizCatalog::SOUND_SLUGS[$sound] ?? null : null;
        if (! $slug) {
            return null;
        }

        // See AudioQuizController::missingInstrumentPreviews() for why RTP2
        // never uses this fallback: a bare degree answer there doesn't imply
        // octave 3 the way it does for RTP1/Find the Note, so guessing would
        // be wrong as often as right. Find the Key #1/#2 answer with the
        // same chromatic letter names for an unrelated reason (naming a
        // song's key, not a note to play) and have no real content yet.
        if (in_array($quiz->title, ['Relative Tone Pitch #2', 'Find the Key #1', 'Find the Key #2'], true)) {
            return null;
        }

        $timing = str_contains($quiz->title, 'Harmonic') ? 'harmonic' : 'melodic';
        $notes = match (true) {
            $quiz->title === 'Cadences' => CadenceSamples::forQuestion($question, $slug),
            $quiz->title === 'Diatonic Progression #1' => DiatonicProgressionSamples::forQuestion($question, $slug),
            $quiz->title === 'Diatonic Progression #2' => DiatonicSeventhProgressionSamples::forQuestion($question, $slug),
            $quiz->title === 'Progression Build #1' => ProgressionBuildSamples::forQuestion($question, $slug),
            $quiz->title === 'Progression Build #2' => SeventhProgressionBuildSamples::forQuestion($question, $slug),
            in_array($quiz->title, [BasicChordNamingSamples::TITLE, BasicChordNamingSamples::LEVEL_TWO_TITLE, BasicChordNamingSamples::LEVEL_THREE_TITLE, BasicChordNamingSamples::LEVEL_FOUR_TITLE, BasicChordNamingSamples::LEVEL_FIVE_TITLE, BasicChordNamingSamples::LEVEL_SIX_TITLE], true) => BasicChordNamingSamples::forQuestion($question, $slug),
            $quiz->category === 'Add 9' => AddNineSamples::forQuestion($question, $slug),
            $quiz->category === 'Basic Triads' => BasicTriadSamples::forQuestion($question, $slug),
            $quiz->category === '7th Degree Chords' => SeventhDegreeChordSamples::forQuestion($question, $slug),
            $quiz->category === 'Secondary 7th Chords' => SecondarySeventhChordSamples::forQuestion($question, $slug),
            $quiz->category === '9th Degree Chords' => NinthDegreeChordSamples::forQuestion($question, $slug),
            $quiz->category === 'Secondary 9th Chords' => SecondaryNinthChordSamples::forQuestion($question, $slug),
            $quiz->category === '11th Degree Chords' => EleventhDegreeChordSamples::forQuestion($question, $slug),
            $quiz->category === 'Secondary 11th Chords' => SecondaryEleventhChordSamples::forQuestion($question, $slug),
            $quiz->category === '13th Degree Chords' => ThirteenthDegreeChordSamples::forQuestion($question, $slug),
            in_array($quiz->title, [
                'Chord Tone Recognition #1', 'Chord Tone Recognition #2',
                'Chord Tone Recognition #3', 'Chord Tone Recognition #4',
                'Chord Tone Recognition #5', 'Chord Tone Recognition #6',
            ], true) => ChordToneRecognitionSamples::forQuestion($question, $slug),
            in_array($quiz->title, [
                'Major Chord Extentions · Level 1', 'Major Chord Extentions · Level 2', 'Major Chord Extentions · Level 3',
                'Major Chord Extentions · Level 4', 'Major Chord Extentions · Level 5', 'Major Chord Extentions · Level 6',
                'Major Chord Extentions · Level 7',
                'Major Chord Extentions · Level 8',
                'Major Chord Extentions · Level 9',
                'Major Chord Extentions · Level 10',
                'Major Chord Extentions · Level 11',
                'Major Chord Extentions · Level 12',
                'Major Chord Extentions · Level 13',
            ], true) => MajorChordExtensionSamples::forQuestion($question, $slug),
            in_array($quiz->title, [
                'Minor Chord Extentions · Level 1', 'Minor Chord Extentions · Level 2', 'Minor Chord Extentions · Level 3',
                'Minor Chord Extentions · Level 4', 'Minor Chord Extentions · Level 5', 'Minor Chord Extentions · Level 6',
                'Minor Chord Extentions · Level 7',
                'Minor Chord Extentions · Level 8',
                'Minor Chord Extentions · Level 9',
                'Minor Chord Extentions · Level 10',
                'Minor Chord Extentions · Level 11',
                'Minor Chord Extentions · Level 12',
                'Minor Chord Extentions · Level 13',
            ], true) => MinorChordExtensionSamples::forQuestion($question, $slug),
            in_array($quiz->title, [
                'Dominant Chord Extentions · Level 1', 'Dominant Chord Extentions · Level 2',
                'Dominant Chord Extentions · Level 3', 'Dominant Chord Extentions · Level 4',
                'Dominant Chord Extentions · Level 5', 'Dominant Chord Extentions · Level 6', 'Dominant Chord Extentions · Level 7', 'Dominant Chord Extentions · Level 8',
            ], true) => DominantChordExtensionSamples::forQuestion($question, $slug),
            $quiz->category === 'Intervals' => IntervalSamples::fromAnswer($question->answer, $timing, $slug),
            $quiz->title === 'Find the Pitch' => NoteSample::fromScientificPitchAnswer($question->answer, $slug),
            default => NoteSample::fromAnswer($question->answer, $timing, $slug),
        };
        if (! $notes) {
            return null;
        }

        $paths = array_map(fn (array $note) => NoteSample::path($slug, $note['degree'], $note['octave']), $notes);
        if (collect($paths)->contains(fn ($path) => ! NoteSample::sampleExists($path))) {
            return null;
        }

        return array_map(fn (array $note, string $path) => [
            'url' => NoteSample::url($path),
            'offset' => $note['offset'],
        ], $notes, $paths);
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
            ->mapWithKeys(function (QuizQuestion $question) use ($quiz, $sound) {
                if ($quiz->title === 'Find the Note') {
                    $notes = $this->questionLiveNotes($quiz, $question, $sound);
                    if (count($notes ?? []) === 1) {
                        return [$question->answer => $notes[0]['url']];
                    }
                }

                $path = $question->audioVariants->firstWhere('sound', $sound)?->audio_path;

                return $path ? [$question->answer => NoteSample::url($path)] : [];
            })
            ->all();
    }

    /** @return array<string, string> Exact pitch labels mapped to available samples. */
    private function pitchKeyboardAudioMap(?string $sound): array
    {
        $slug = $sound ? QuizCatalog::SOUND_SLUGS[$sound] ?? null : null;
        if ($slug === null) {
            return [];
        }

        $map = [];
        foreach (NoteSample::SCIENTIFIC_PITCH_OCTAVES as $octave) {
            foreach (array_keys(NoteSample::CHROMATIC_SLUGS) as $label) {
                $answer = "{$label}{$octave}";
                $notes = NoteSample::fromScientificPitchAnswer($answer, $slug);
                if ($notes !== null) {
                    $note = $notes[0];
                    $map[$answer] = NoteSample::url(NoteSample::path($slug, $note['degree'], $note['octave']));
                }
            }
        }

        return $map;
    }

    // Key-finding recordings use recording types rather than instruments.
    // Their keyboards follow the member's instrument and Find the Note register.
    private function defaultInstrumentKeyboardAudio(Request $request, ?string $onlyNote = null): array
    {
        $sound = $request->user()?->default_instrument;
        $sound = isset(QuizCatalog::SOUND_SLUGS[$sound]) ? $sound : 'Piano';
        $slug = QuizCatalog::SOUND_SLUGS[$sound];
        $noteQuiz = Quiz::where('title', 'Find the Note')->first();
        $map = $noteQuiz ? $this->keyboardAudioMap($noteQuiz, $sound) : [];
        $labels = $onlyNote ? array_intersect_key(self::NOTE_SLUGS, [$onlyNote => true]) : self::NOTE_SLUGS;

        return collect($labels)->keys()->mapWithKeys(function ($label) use ($map, $slug) {
            if (isset($map[$label])) {
                return [$label => $map[$label]];
            }

            $note = NoteSample::fromAnswer($label, 'melodic', $slug)[0] ?? null;
            if ($note === null) {
                return [];
            }

            $path = NoteSample::path($slug, $note['degree'], $note['octave']);

            return NoteSample::sampleExists($path) ? [$label => NoteSample::url($path)] : [];
        })->all();
    }

    // Melodic Dictation only, per explicit request: lets a member re-anchor
    // to the tonic ("Do") before answering any question, same as a teacher
    // sounding the tonic before a dictation exercise — the quiz never writes
    // out a key, so there's nothing else establishing it. Same note on
    // every question in the attempt (same sound, always "Do"), so this is
    // cheap to compute per request — one single-note resolve, already
    // memoized by NoteSample.
    //
    // @return list<array{url: string, offset: float}>|null
    private function tonicNotes(?string $sound): ?array
    {
        $slug = $sound ? QuizCatalog::SOUND_SLUGS[$sound] ?? null : null;
        $note = $slug ? NoteSample::fromAnswer('Do', 'melodic', $slug)[0] ?? null : null;
        if ($note === null) {
            return null;
        }
        $path = NoteSample::path($slug, $note['degree'], $note['octave']);

        return NoteSample::sampleExists($path) ? [['url' => NoteSample::url($path), 'offset' => 0.0]] : null;
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
                'id' => $question->id,
                'prompt' => $question->prompt,
                // The real app shows a fixed set of options per quiz (not a
                // grab-bag per question) — switch to that canonical list only
                // when it actually contains this question's real answer, so
                // an older question seeded with different choices never ends
                // up with its correct answer missing from the options shown.
                'choices' => $this->choicesFor($quiz, $question),
                'audio_url' => $this->questionAudioUrl($question, $attempt->sound),
                // Live-mixing engine data — see questionLiveNotes(). Present
                // only for Relative Pitch today; every other category gets
                // null here and keeps playing audio_url as one file.
                'notes' => $this->questionLiveNotes($quiz, $question, $attempt->sound),
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
                'keyboard_audio' => $quiz->title === 'Find the Pitch'
                    ? $this->pitchKeyboardAudioMap($attempt->sound)
                    : ($this->keyboardAudioMap($quiz, $attempt->sound)
                        ?: $this->defaultInstrumentKeyboardAudio($request, $referenceNote)),
                // Which key the keyboard highlights as the reference note (Find the
                // Key #2 only) — shuffled fresh per question, not a fixed "G".
                'reference_note' => $referenceNote,
                'tonic_notes' => $quiz->category === 'Melodic Dictation' || in_array($quiz->title, ['Relative Tone Pitch #1', 'Relative Tone Pitch #2'], true)
                    ? $this->tonicNotes($attempt->sound) : null,
            ] : null,
            'finished' => $finished,
            'feedback' => $feedback,
        ];
    }
}
