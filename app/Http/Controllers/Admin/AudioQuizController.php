<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesQuizMedia;
use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Support\QuizCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'quiz' => $selectedQuiz ? $this->quizPayload($selectedQuiz) : null,
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
        $isChordTone = in_array($quiz->title, ['Chord Tone Recognition #1', 'Chord Tone Recognition #2'], true);

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
        $multiExtensionLevels = ['Major Chord Extentions', 'Minor Chord Extentions', 'Dominant Chord Extentions'];

        return match (true) {
            $quiz->category === 'Melodic Dictation' => 'Listen to the melody and build the sequence of notes you hear.',
            in_array($quiz->title, $compoundTriadLevels, true) => 'Listen to the triad and identify its quality and inversion.',
            in_array($quiz->title, $compoundChordLevels, true) => 'Listen to the chord and identify its quality and inversion.',
            in_array($quiz->title, $multiExtensionLevels, true) => 'Listen to the chord and select the extension(s) you hear.',
            $quiz->title === 'Chord naming' => 'Listen to the chord and build its full name.',
            $quiz->title === 'Diatonic Progression' => 'Listen to the progression and choose the number of each chord.',
            $quiz->title === 'Progression Build #1' => 'Listen to the progression and choose the number and quality of each chord.',
            $quiz->title === 'Progression Build #2' => 'Listen to the progression and choose the number and 7th chord quality of each chord.',
            $quiz->title === 'Progression recognition' => 'Listen to the progression and select the chord quality for each chord.',
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
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function lessonsFor(string $category): \Illuminate\Support\Collection
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
    private function quizPayload(Quiz $quiz): array
    {
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
            'questions' => $quiz->questions->map(fn (QuizQuestion $question) => [
                'id' => $question->id,
                'prompt' => $question->prompt,
                'choices' => $question->choices,
                'answer' => $question->answer,
                'audio_url' => $this->mediaUrl($question->audio_path),
                'sound' => $question->sound,
            ])->values(),
        ];
    }
}
