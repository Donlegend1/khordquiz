<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesQuizMedia;
use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Support\AddNineSamples;
use App\Support\BasicChordNamingSamples;
use App\Support\BasicTriadSamples;
use App\Support\CadenceSamples;
use App\Support\ChordToneRecognitionSamples;
use App\Support\DiatonicProgressionSamples;
use App\Support\DiatonicSeventhProgressionSamples;
use App\Support\DominantChordExtensionSamples;
use App\Support\EleventhDegreeChordSamples;
use App\Support\IntervalSamples;
use App\Support\MajorChordExtensionSamples;
use App\Support\MinorChordExtensionSamples;
use App\Support\NinthDegreeChordSamples;
use App\Support\NoteSample;
use App\Support\ProgressionBuildSamples;
use App\Support\QuizCatalog;
use App\Support\SecondaryEleventhChordSamples;
use App\Support\SecondaryNinthChordSamples;
use App\Support\SecondarySeventhChordSamples;
use App\Support\SeventhDegreeChordSamples;
use App\Support\SeventhProgressionBuildSamples;
use App\Support\ThirteenthDegreeChordSamples;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

// Category → lesson overview and editor for the quiz catalog, mirroring the
// "Audio Quiz" admin dashboard from the KingsleyKhordPiano project (category
// grid, then a split-pane lesson list + tabbed editor). Lessons are added one
// question at a time here rather than batch-saved, which is why this has its
// own store/destroy for questions instead of reusing QuizController's.
class AudioQuizController extends Controller
{
    use HandlesQuizMedia;

    // Matches the app's own KEY_FINDING_SOUNDS (src/lib/quiz-catalog.ts), minus
    // "All" — that's a derived member-facing option meaning "any of the below,"
    // not a distinct bucket of audio the admin would ever upload against.
    private const KEY_FINDING_SOUNDS = ['Monophonic', 'Vocal Stems', 'Instruments', 'Ensemble', 'Full Song'];

    // Reference chord qualities for Chord Tone Recognition — the member needs
    // to know which chord is sounding to name the highlighted degree (1/3/5/7)
    // meaningfully, so the admin tags each question with one. Covers both the
    // 5 primary 7th-chord qualities and the 6 secondary ones.
    private const CHORD_TONE_QUALITIES = [
        'Diminished 7th', 'Dominant 7th', 'Minor 7(♭5)', 'Major 7th', 'Minor 7th',
        'Augmented 7th', 'Minor-Major 7th', 'Diminished-Major 7th', 'Dominant 7(♭5)', 'Major 7(♯5)', 'Major 7(♭5)',
    ];

    // #1/#2 are the 5 7th Degree Chord qualities (root position / all
    // inversions); #3/#4 are the 6 Secondary 7th Chord qualities; #5/#6
    // are both combined (11 qualities), same root/inverted split (see
    // scripts/audio/build_chord_tone_recognition{1..6}.php).
    private const CHORD_TONE_QUIZ_TITLES = [
        'Chord Tone Recognition #1', 'Chord Tone Recognition #2',
        'Chord Tone Recognition #3', 'Chord Tone Recognition #4',
        'Chord Tone Recognition #5', 'Chord Tone Recognition #6',
    ];

    private const MAJOR_CHORD_EXTENSION_QUIZ_TITLES = [
        'Major Chord Extentions · Level 1', 'Major Chord Extentions · Level 2', 'Major Chord Extentions · Level 3',
        'Major Chord Extentions · Level 4', 'Major Chord Extentions · Level 5', 'Major Chord Extentions · Level 6',
        'Major Chord Extentions · Level 7',
        'Major Chord Extentions · Level 8',
        'Major Chord Extentions · Level 9',
        'Major Chord Extentions · Level 10',
        'Major Chord Extentions · Level 11',
        'Major Chord Extentions · Level 12',
        'Major Chord Extentions · Level 13',
    ];

    private const MINOR_CHORD_EXTENSION_QUIZ_TITLES = [
        'Minor Chord Extentions · Level 1', 'Minor Chord Extentions · Level 2', 'Minor Chord Extentions · Level 3',
        'Minor Chord Extentions · Level 4', 'Minor Chord Extentions · Level 5', 'Minor Chord Extentions · Level 6',
        'Minor Chord Extentions · Level 7',
        'Minor Chord Extentions · Level 8',
        'Minor Chord Extentions · Level 9',
        'Minor Chord Extentions · Level 10',
        'Minor Chord Extentions · Level 11',
        'Minor Chord Extentions · Level 12',
        'Minor Chord Extentions · Level 13',
    ];

    private const DOMINANT_CHORD_EXTENSION_QUIZ_TITLES = [
        'Dominant Chord Extentions · Level 1', 'Dominant Chord Extentions · Level 2',
        'Dominant Chord Extentions · Level 3', 'Dominant Chord Extentions · Level 4',
        'Dominant Chord Extentions · Level 5', 'Dominant Chord Extentions · Level 6', 'Dominant Chord Extentions · Level 7', 'Dominant Chord Extentions · Level 8',
    ];

    public function index(): Response
    {
        $quizzes = Quiz::query()->get(['id', 'category', 'title']);
        $byCategory = $quizzes->groupBy('category');

        // Shows every category the app has, not just ones with a real quiz
        // behind them yet — total is the real catalog size (app parity),
        // configured counts only real, saved quizzes.
        $categories = collect(Quiz::CATEGORIES)
            ->map(function ($name) use ($byCategory) {
                $real = $byCategory->get($name, collect());
                $catalogSlugs = collect(QuizCatalog::subcategories($name))->map(fn ($n) => Str::slug($n));
                $realSlugs = $real->pluck('title')->map(fn ($t) => Str::slug($t));
                $total = $catalogSlugs->concat($realSlugs)->unique()->count();

                return [
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'total' => $total,
                    'configured' => $real->count(),
                ];
            })
            ->values();

        return Inertia::render('Admin/AudioQuiz/Index', [
            'categories' => $categories,
        ]);
    }

    public function show(Request $request, string $category, ?string $lesson = null): Response
    {
        $name = $this->resolveCategory($category);
        $lessons = $this->lessonsFor($name);

        $selectedQuiz = null;

        if ($lesson !== null) {
            $match = $lessons->firstWhere('slug', $lesson);
            abort_if($match === null, 404);

            if ($match['configured']) {
                $selectedQuiz = Quiz::query()->where('category', $name)->get()
                    ->first(fn (Quiz $quiz) => Str::slug($quiz->title) === $lesson);
            } else {
                // Selecting a catalog placeholder creates its lesson shell on the
                // spot, so the admin lands straight on the question editor instead
                // of a separate "create this lesson" step.
                $selectedQuiz = Quiz::query()->create([
                    'category' => $name,
                    'title' => $match['title'],
                    'difficulty' => Quiz::DIFFICULTIES[0],
                    'slug' => Quiz::uniqueSlug($match['title']),
                    'created_by' => $request->user()->id,
                    'question_count' => 0,
                    'is_published' => false,
                ]);

                $lessons = $this->lessonsFor($name);
            }
        }

        return Inertia::render('Admin/AudioQuiz/Editor', [
            'category' => $name,
            'categorySlug' => Str::slug($name),
            'categories' => collect(Quiz::CATEGORIES)->map(fn ($c) => ['name' => $c, 'slug' => Str::slug($c)])->values(),
            'difficulties' => Quiz::DIFFICULTIES,
            'lessons' => $lessons->values(),
            'selectedSlug' => $lesson,
            'quiz' => $selectedQuiz ? $this->quizPayload($selectedQuiz, max(1, $request->integer('page', 1))) : null,
        ]);
    }

    public function updateSettings(Request $request, Quiz $quiz): RedirectResponse
    {
        $data = $request->validate([
            'difficulty' => ['required', 'string', 'in:'.implode(',', Quiz::DIFFICULTIES)],
            'description' => ['nullable', 'string', 'max:500'],
            'video_embed' => ['nullable', 'string', 'max:5000'],
            'guide_text' => ['nullable', 'string', 'max:3000'],
            'reference_audio' => ['nullable', 'file', 'mimes:mp3,wav,m4a,aac,ogg,webm', 'max:15360'],
            'keep_reference_audio' => ['boolean'],
            'is_published' => ['boolean'],
        ]);

        $unused = [];

        $quiz->update([
            'difficulty' => $data['difficulty'],
            'description' => $data['description'] ?? null,
            'video_embed' => $data['video_embed'] ?? null,
            'guide_text' => $data['guide_text'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'reference_audio_path' => $this->resolveMedia(
                $request->file('reference_audio'),
                $quiz->reference_audio_path,
                $request->boolean('keep_reference_audio'),
                'quizzes/reference-audio',
                $unused,
            ),
        ]);

        $this->deleteMedia($unused);

        return back()->with('success', 'Lesson updated.');
    }

    public function storeReferenceAudioTrack(Request $request, Quiz $quiz): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:150'],
            'audio' => ['required', 'file', 'mimes:mp3,wav,m4a,aac,ogg,webm', 'max:15360'],
        ]);

        $tracks = $quiz->reference_audio_tracks ?? [];
        $tracks[] = [
            'id' => (string) Str::uuid(),
            'label' => $data['label'],
            'name' => $data['name'],
            'path' => $request->file('audio')->store('quizzes/reference-audio', 'public'),
        ];

        $quiz->update(['reference_audio_tracks' => $tracks]);

        return back()->with('success', 'Reference audio track added.');
    }

    public function destroyReferenceAudioTrack(Quiz $quiz, string $track): RedirectResponse
    {
        $tracks = collect($quiz->reference_audio_tracks ?? []);
        $removed = $tracks->firstWhere('id', $track);

        $quiz->update(['reference_audio_tracks' => $tracks->reject(fn ($t) => $t['id'] === $track)->values()->all()]);

        if ($removed) {
            $this->deleteMedia([$removed['path']]);
        }

        return back()->with('success', 'Reference audio track removed.');
    }

    public function storeQuestion(Request $request, Quiz $quiz): RedirectResponse
    {
        $isChordTone = in_array($quiz->title, self::CHORD_TONE_QUIZ_TITLES, true);

        // Melodic Dictation questions are a single tapped-out sequence rather than
        // a flat list of options, so they submit a 1-element "choices" array.
        $data = $request->validate([
            'choices' => ['required', 'array', 'min:1', 'max:8'],
            'choices.*' => ['required', 'string', 'max:120'],
            'correct' => ['required', 'integer', 'min:0'],
            'quality' => [$isChordTone ? 'required' : 'nullable', 'string', Rule::in(self::CHORD_TONE_QUALITIES)],
            'audio' => ['nullable', 'file', 'mimes:mp3,wav,m4a,aac,ogg,webm', 'max:15360'],
        ]);

        abort_unless(array_key_exists($data['correct'], $data['choices']), 422, 'Choose which answer is correct.');

        $prompt = $isChordTone
            ? "Listen to the {$data['quality']} chord and identify which chord tone the highlighted note is."
            : $this->promptFor($quiz);

        $unused = [];
        $audioPath = $this->resolveMedia($request->file('audio'), null, false, 'questions/audio', $unused);

        DB::transaction(function () use ($quiz, $data, $audioPath, $prompt) {
            $quiz->questions()->create([
                'position' => (int) $quiz->questions()->max('position') + 1,
                'prompt' => $prompt,
                'choices' => array_values($data['choices']),
                'answer' => $data['choices'][$data['correct']],
                'audio_path' => $audioPath,
            ]);

            $quiz->update(['question_count' => $quiz->questions()->count()]);
        });

        return back()->with('success', 'Question added.');
    }

    // Lets the admin drop a whole batch of audio files for one key at once —
    // each file becomes its own question with the same answer — instead of
    // repeating the single-question form for every song in that key.
    public function storeBulkQuestions(Request $request, Quiz $quiz): RedirectResponse
    {
        $hasFixedSounds = in_array($quiz->title, ['Find the Key #1', 'Find the Key #2'], true);

        $data = $request->validate([
            'choices' => ['required', 'array', 'min:2', 'max:12'],
            'choices.*' => ['required', 'string', 'max:120'],
            'answer' => ['required', 'string', Rule::in($request->input('choices', []))],
            'sound' => [$hasFixedSounds ? 'required' : 'nullable', 'string', Rule::in(self::KEY_FINDING_SOUNDS)],
            'audio' => ['required', 'array', 'min:1', 'max:60'],
            'audio.*' => ['required', 'file', 'mimes:mp3,wav,m4a,aac,ogg,webm', 'max:15360'],
        ]);

        $prompt = $this->promptFor($quiz);
        $choices = array_values($data['choices']);
        $sound = $data['sound'] ?? null;

        $unused = [];
        $audioPaths = array_map(
            fn ($file) => $this->resolveMedia($file, null, false, 'questions/audio', $unused),
            $data['audio'],
        );

        DB::transaction(function () use ($quiz, $choices, $data, $audioPaths, $prompt, $sound) {
            $position = (int) $quiz->questions()->max('position');

            foreach ($audioPaths as $audioPath) {
                $quiz->questions()->create([
                    'position' => ++$position,
                    'prompt' => $prompt,
                    'choices' => $choices,
                    'answer' => $data['answer'],
                    'audio_path' => $audioPath,
                    'sound' => $sound,
                ]);
            }

            $quiz->update(['question_count' => $quiz->questions()->count()]);
        });

        $count = count($audioPaths);

        return back()->with('success', $count.' question'.($count === 1 ? '' : 's').' added.');
    }

    public function destroyQuestion(QuizQuestion $question): RedirectResponse
    {
        $quiz = $question->quiz;

        $this->deleteMedia([$question->audio_path, $question->video_path]);

        DB::transaction(function () use ($quiz, $question) {
            $question->delete();

            // Positions must stay contiguous (1..N) — the member quiz-play
            // flow looks up the next question by exact position number.
            $quiz->questions()->orderBy('position')->get()->values()
                ->each(fn (QuizQuestion $remaining, int $index) => $remaining->update(['position' => $index + 1]));

            $quiz->update(['question_count' => $quiz->questions()->count()]);
        });

        return back()->with('success', 'Question removed.');
    }

    public function destroyLesson(Quiz $quiz): RedirectResponse
    {
        $category = Str::slug($quiz->category);

        $this->deleteMedia([
            $quiz->reference_audio_path,
            ...$quiz->questions->flatMap(fn (QuizQuestion $q) => [$q->audio_path, $q->video_path])->all(),
        ]);

        $quiz->delete();

        return redirect()->route('admin.audio-quiz.show', $category)->with('success', 'Lesson deleted.');
    }

    // Matches the app's own wording per quiz type (app/practice.tsx) wherever a
    // fixed mapping exists; falls back to the single-note wording otherwise.
    private function promptFor(Quiz $quiz): string
    {
        $compoundTriadLevels = ['Basic Triads · Level 5', 'Basic Triads · Level 6', 'Basic Triads · Level 7', 'Basic Triads · Level 8'];
        $compoundChordLevels = [
            '7th Degree Chords · Level 5', '7th Degree Chords · Level 6', '7th Degree Chords · Level 7', '7th Degree Chords · Level 8',
            'Secondary 7th Chords · Level 5', 'Secondary 7th Chords · Level 6', 'Secondary 7th Chords · Level 7', 'Secondary 7th Chords · Level 8',
        ];
        $multiExtensionLevels = [
            'Major Chord Extentions', 'Major Chord Extentions · Level 1', 'Major Chord Extentions · Level 2', 'Major Chord Extentions · Level 3',
            'Major Chord Extentions · Level 4', 'Major Chord Extentions · Level 5', 'Major Chord Extentions · Level 6',
            'Major Chord Extentions · Level 7',
            'Major Chord Extentions · Level 8',
            'Major Chord Extentions · Level 9',
            'Major Chord Extentions · Level 10',
            'Major Chord Extentions · Level 11',
            'Major Chord Extentions · Level 12',
            'Major Chord Extentions · Level 13',
            'Minor Chord Extentions', 'Minor Chord Extentions · Level 1', 'Minor Chord Extentions · Level 2', 'Minor Chord Extentions · Level 3',
            'Minor Chord Extentions · Level 4', 'Minor Chord Extentions · Level 5', 'Minor Chord Extentions · Level 6',
            'Minor Chord Extentions · Level 7',
            'Minor Chord Extentions · Level 8',
            'Minor Chord Extentions · Level 9',
            'Minor Chord Extentions · Level 10',
            'Minor Chord Extentions · Level 11',
            'Minor Chord Extentions · Level 12',
            'Minor Chord Extentions · Level 13',
            'Dominant Chord Extentions', 'Dominant Chord Extentions · Level 1', 'Dominant Chord Extentions · Level 2', 'Dominant Chord Extentions · Level 3',
            'Dominant Chord Extentions · Level 4',
            'Dominant Chord Extentions · Level 5', 'Dominant Chord Extentions · Level 6', 'Dominant Chord Extentions · Level 7', 'Dominant Chord Extentions · Level 8',
        ];

        return match (true) {
            $quiz->title === 'Find the Pitch' => 'Listen to the note and tap it on the keyboard.',
            $quiz->category === 'Melodic Dictation' => 'Listen to the melody and build the sequence of notes you hear.',
            in_array($quiz->title, $compoundTriadLevels, true) => 'Listen to the triad and identify its quality and inversion.',
            in_array($quiz->title, $compoundChordLevels, true) => 'Listen to the chord and identify its quality and inversion.',
            in_array($quiz->title, $multiExtensionLevels, true) => 'Listen to the chord and select the extension(s) you hear.',
            in_array($quiz->title, ['Chord naming', BasicChordNamingSamples::TITLE, BasicChordNamingSamples::LEVEL_TWO_TITLE, BasicChordNamingSamples::LEVEL_THREE_TITLE, BasicChordNamingSamples::LEVEL_FOUR_TITLE, BasicChordNamingSamples::LEVEL_FIVE_TITLE, BasicChordNamingSamples::LEVEL_SIX_TITLE], true) => 'Listen to the chord and build its full name.',
            in_array($quiz->title, ['Diatonic Progression #1', 'Diatonic Progression #2', 'Progression Build #1', 'Progression Build #2'], true) => 'First hear the key’s major triad and tonic note. Then identify the four progression chords.',
            $quiz->title === 'Progression Build #1' => 'Listen to the progression and choose the number and quality of each chord.',
            $quiz->title === 'Progression Build #2' => 'Listen to the progression and choose the number and 7th chord quality of each chord.',
            in_array($quiz->title, ['Progression Recognition #1', 'Progression Recognition #2', 'Progression Recognition #3', 'Progression Recognition #4'], true) => 'Listen to the progression and select the chord quality for each chord.',
            $quiz->title === '2-5-1 chord degree' => 'Listen to the progression and select the chord quality and every degree for each chord.',
            $quiz->title === 'Find the Key #1' => 'Listen to the song and identify which key it is in.',
            $quiz->title === 'Find the Key #2' => 'Listen to the song and use the reference note to identify which key it is in.',
            default => 'Listen to the note and identify which solfège syllable it is.',
        };
    }

    private function resolveCategory(string $slug): string
    {
        $match = collect(Quiz::CATEGORIES)->first(fn ($name) => Str::slug($name) === $slug);
        abort_if($match === null, 404);

        return $match;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function lessonsFor(string $category): Collection
    {
        $real = Quiz::query()
            ->where('category', $category)
            ->withCount('attempts')
            ->get()
            ->keyBy(fn (Quiz $quiz) => Str::slug($quiz->title));

        $entryFor = function (string $slug, string $title) use ($real) {
            $quiz = $real->get($slug);

            return $quiz ? [
                'configured' => true,
                'slug' => Str::slug($quiz->title),
                'title' => $quiz->title,
                'difficulty' => $quiz->difficulty,
                'question_count' => $quiz->question_count,
                'attempts_count' => $quiz->attempts_count,
                'is_published' => $quiz->is_published,
            ] : [
                'configured' => false,
                'slug' => $slug,
                'title' => $title,
                'difficulty' => null,
                'question_count' => null,
                'attempts_count' => null,
                'is_published' => null,
            ];
        };

        // Catalog order first (real quiz data merged in place of each matching
        // title), so a lesson's position never jumps once it's created. Real
        // quizzes whose title doesn't match any catalog entry are appended after.
        $catalogEntries = collect(QuizCatalog::subcategories($category))
            ->map(fn ($title) => $entryFor(Str::slug($title), $title));

        $catalogSlugs = $catalogEntries->pluck('slug');

        $extraReal = $real->reject(fn ($quiz, $slug) => $catalogSlugs->contains($slug))
            ->map(fn (Quiz $quiz) => $entryFor(Str::slug($quiz->title), $quiz->title));

        return $catalogEntries->concat($extraReal->values())->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function quizPayload(Quiz $quiz, int $page = 1): array
    {
        $questions = $quiz->questions()->with('audioVariants')->orderBy('id')->paginate(25, ['*'], 'page', $page)->withQueryString();
        if ($questions->currentPage() > $questions->lastPage()) {
            $questions = $quiz->questions()->with('audioVariants')->orderBy('id')->paginate(25, ['*'], 'page', $questions->lastPage())->withQueryString();
        }
        $sounds = QuizQuestionAudio::whereIn('quiz_question_id', $quiz->questions()->select('id'))->distinct()->pluck('sound')->values();

        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'category' => $quiz->category,
            'difficulty' => $quiz->difficulty,
            'description' => $quiz->description,
            'video_embed' => $quiz->video_embed,
            'guide_text' => $quiz->guide_text,
            'reference_audio_url' => $this->mediaUrl($quiz->reference_audio_path),
            'keep_reference_audio' => $quiz->reference_audio_path !== null,
            'reference_audio_tracks' => collect($quiz->reference_audio_tracks ?? [])
                ->map(fn ($track) => ['id' => $track['id'], 'label' => $track['label'], 'url' => $this->mediaUrl($track['path'])])
                ->values(),
            'is_published' => $quiz->is_published,
            'questions_total' => $questions->total(),
            'audio_sounds' => $sounds,
            'pagination' => [
                'current_page' => $questions->currentPage(), 'last_page' => $questions->lastPage(),
                'from' => $questions->firstItem(), 'to' => $questions->lastItem(), 'total' => $questions->total(),
                'previous_url' => $questions->previousPageUrl(), 'next_url' => $questions->nextPageUrl(),
            ],
            'questions' => $questions->getCollection()->map(fn (QuizQuestion $question) => [
                'id' => $question->id,
                'prompt' => $question->prompt,
                'choices' => $question->choices,
                'answer' => $question->answer,
                'audio_url' => $this->mediaUrl($question->audio_path),
                'notes' => $this->liveNotes($question->audio_path),
                'sound' => $question->sound,
                'audio_variants' => $question->audioVariants->map(fn (QuizQuestionAudio $variant) => [
                    'sound' => $variant->sound,
                    'url' => $this->mediaUrl($variant->audio_path),
                    // "Play live-mixed" next to the existing player — lets an
                    // admin A/B the old pre-rendered file against exactly what
                    // the member-facing live-mixing engine now produces for
                    // this question/instrument (QuizController::
                    // questionLiveNotes is the member-facing twin of this).
                    'notes' => $this->liveNotes($variant->audio_path),
                ])->values(),
                // Instruments with no quiz_question_audio row at all (no
                // generated audio for this question) but that DO have a base
                // note sample recorded — single-note questions only (Find the
                // Note, Relative Tone Pitch #1/#2), since those are the only
                // ones where the answer itself names one note directly. Lets
                // an admin preview an instrument by ear before any dedicated
                // audio pipeline run has touched this quiz.
                'missing_instrument_previews' => $this->missingInstrumentPreviews($quiz, $question),
            ])->values(),
        ];
    }

    // @return list<array{url: string, offset: float}>|null
    private function liveNotes(?string $path): ?array
    {
        $notes = $path ? NoteSample::resolve($path) : null;

        if (! $notes) {
            return null;
        }

        return array_map(fn (array $note) => [
            'url' => $this->mediaUrl(NoteSample::path($note['slug'], $note['degree'], $note['octave'])),
            'offset' => $note['offset'],
        ], $notes);
    }

    // @return list<array{sound: string, notes: list<array{url: string, offset: float}>}>
    private function missingInstrumentPreviews(Quiz $quiz, QuizQuestion $question): array
    {
        $have = $question->audioVariants->pluck('sound')->all();
        $timing = str_contains($quiz->title, 'Harmonic') ? 'harmonic' : 'melodic';

        // Relative Tone Pitch #2's pool reuses the same bare-degree answer
        // text as RTP1/Find the Note ("Do", "Re", ...), but unlike those
        // quizzes a bare degree here does NOT mean "play it at octave 3" —
        // each position has its own real intended octave (2-6), only
        // recoverable from its attached quiz_question_audio row. With no row
        // (the instrument's real range doesn't reach this position - e.g.
        // Acoustic Guitar has no octave-2 Do/Re/Mi/Fa), NoteSample::fromAnswer()
        // would silently guess octave 3, which is wrong exactly as often as
        // it's right and was confirmed audible as notes "jumping around"
        // between octaves while stepping through a quiz. No row here means
        // genuinely no sample at the right pitch, so no preview, full stop.
        // Find the Key #1/#2 answer with chromatic letter names ("C",
        // "C#/Db", ...) for an entirely different reason — identifying a
        // song excerpt's key, not naming/playing a single note — but that
        // answer format happens to be exactly what NoteSample::fromAnswer()
        // recognizes as a playable single degree, so it was offering a
        // "Live mix" preview that plays one decontextualized piano note for
        // a question that's actually about a whole song. These two quizzes
        // have no real content yet at all (sounds deliberately emptied —
        // see QuizCatalog::soundsFor()), so there's nothing meaningful to
        // preview regardless.
        if (in_array($quiz->title, ['Relative Tone Pitch #2', 'Find the Key #1', 'Find the Key #2'], true)) {
            return [];
        }

        // Bass Guitar, Upright Bass, and Tuba are excluded from Basic Triads,
        // Add 9, 7th Degree Chords, Secondary 7th Chords, 9th Degree
        // Chords, Secondary 9th Chords, 11th Degree Chords, Secondary
        // 11th Chords, 13th Degree Chords, and the two Chord Tone
        // Recognition quizzes entirely by explicit request — not just
        // unattached, but never offered as a "missing instrument" Live
        // Mix suggestion either.
        $liveMixCategories = [
            'Basic Triads', 'Add 9', '7th Degree Chords', 'Secondary 7th Chords',
            '9th Degree Chords', 'Secondary 9th Chords', '11th Degree Chords', 'Secondary 11th Chords',
            '13th Degree Chords', 'Chord Naming',
        ];
        $liveMixTitles = ['Cadences', 'Diatonic Progression #1', 'Diatonic Progression #2', 'Progression Build #1', 'Progression Build #2', ...self::CHORD_TONE_QUIZ_TITLES, ...self::MAJOR_CHORD_EXTENSION_QUIZ_TITLES, ...self::MINOR_CHORD_EXTENSION_QUIZ_TITLES, ...self::DOMINANT_CHORD_EXTENSION_QUIZ_TITLES];
        $isLiveMixQuiz = in_array($quiz->category, $liveMixCategories, true) || in_array($quiz->title, $liveMixTitles, true);
        $excludedFromCategory = $isLiveMixQuiz ? ['Bass Guitar', 'Upright Bass', 'Tuba'] : [];

        $previews = [];
        foreach (QuizCatalog::SOUND_SLUGS as $sound => $slug) {
            if (in_array($sound, $have, true) || in_array($sound, $excludedFromCategory, true)) {
                continue;
            }

            if ($isLiveMixQuiz) {
                $resolved = match (true) {
                    $quiz->title === 'Cadences' => CadenceSamples::forQuestion($question, $slug),
                    $quiz->title === 'Diatonic Progression #1' => DiatonicProgressionSamples::forQuestion($question, $slug),
                    $quiz->title === 'Diatonic Progression #2' => DiatonicSeventhProgressionSamples::forQuestion($question, $slug),
                    $quiz->title === 'Progression Build #1' => ProgressionBuildSamples::forQuestion($question, $slug),
                    $quiz->title === 'Progression Build #2' => SeventhProgressionBuildSamples::forQuestion($question, $slug),
                    in_array($quiz->title, self::CHORD_TONE_QUIZ_TITLES, true) => ChordToneRecognitionSamples::forQuestion($question, $slug),
                    in_array($quiz->title, self::MAJOR_CHORD_EXTENSION_QUIZ_TITLES, true) => MajorChordExtensionSamples::forQuestion($question, $slug),
                    in_array($quiz->title, self::MINOR_CHORD_EXTENSION_QUIZ_TITLES, true) => MinorChordExtensionSamples::forQuestion($question, $slug),
                    in_array($quiz->title, self::DOMINANT_CHORD_EXTENSION_QUIZ_TITLES, true) => DominantChordExtensionSamples::forQuestion($question, $slug),
                    default => match ($quiz->category) {
                        'Chord Naming' => BasicChordNamingSamples::forQuestion($question, $slug),
                        'Add 9' => AddNineSamples::forQuestion($question, $slug),
                        'Basic Triads' => BasicTriadSamples::forQuestion($question, $slug),
                        '7th Degree Chords' => SeventhDegreeChordSamples::forQuestion($question, $slug),
                        'Secondary 7th Chords' => SecondarySeventhChordSamples::forQuestion($question, $slug),
                        '9th Degree Chords' => NinthDegreeChordSamples::forQuestion($question, $slug),
                        'Secondary 9th Chords' => SecondaryNinthChordSamples::forQuestion($question, $slug),
                        '11th Degree Chords' => EleventhDegreeChordSamples::forQuestion($question, $slug),
                        'Secondary 11th Chords' => SecondaryEleventhChordSamples::forQuestion($question, $slug),
                        '13th Degree Chords' => ThirteenthDegreeChordSamples::forQuestion($question, $slug),
                    },
                };
                $notes = $resolved === null ? null : array_map(fn (array $note) => [
                    'url' => $this->mediaUrl(NoteSample::path($slug, $note['degree'], $note['octave'])),
                    'offset' => $note['offset'],
                ], $resolved);
            } else {
                $notes = $this->previewNotesFor($slug, $question->answer, $timing, $quiz->title, $quiz->category);
            }
            if ($notes === null) {
                continue;
            }

            $previews[] = ['sound' => $sound, 'notes' => $notes];
        }

        return $previews;
    }

    // Builds the preview straight from the answer (NoteSample::fromAnswer —
    // same fallback the member-facing quiz player uses, see QuizController::
    // liveNotesFromAnswer) and checks every resolved note's file actually
    // exists for $slug before offering it. Handles both single-note answers
    // (Find the Note, RTP1/2) and interval pairs (Melodic/Harmonic 2nd-7th) —
    // the admin's old version only ever handled the single-note case.
    //
    // @return list<array{url: string, offset: float}>|null
    private function previewNotesFor(string $slug, string $answer, string $timing, string $quizTitle, string $category): ?array
    {
        $notes = match (true) {
            $category === 'Intervals' => IntervalSamples::fromAnswer($answer, $timing, $slug),
            $quizTitle === 'Find the Pitch' => NoteSample::fromScientificPitchAnswer($answer, $slug),
            default => NoteSample::fromAnswer($answer, $timing, $slug),
        };
        if ($notes === null) {
            return null;
        }

        $resolved = [];
        foreach ($notes as $note) {
            $path = NoteSample::path($slug, $note['degree'], $note['octave']);
            if (! NoteSample::sampleExists($path)) {
                // Single-note answers might only have this pitch recorded
                // under the other naming scheme (e.g. re.wav instead of
                // d.wav) — same sound, just not under this exact filename.
                $equivalent = NoteSample::equivalentDegree($note['degree']);
                $path = $equivalent ? NoteSample::path($slug, $equivalent, $note['octave']) : null;
                if (! $path || ! NoteSample::sampleExists($path)) {
                    return null;
                }
            }

            $resolved[] = ['url' => $this->mediaUrl($path), 'offset' => $note['offset']];
        }

        return $resolved;
    }
}
