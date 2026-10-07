<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuizRequest;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Quizzes/Index', [
            'quizzes' => Quiz::query()
                ->withCount(['attempts', 'questions'])
                ->latest()
                ->paginate(10)
                ->through(fn (Quiz $quiz) => $quiz->summary()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Quizzes/Form', $this->formProps());
    }

    public function store(QuizRequest $request): RedirectResponse
    {
        $unusedMedia = DB::transaction(function () use ($request): array {
            $quiz = Quiz::query()->create([
                ...$this->quizAttributes($request),
                'slug' => Quiz::uniqueSlug($request->string('title')->toString()),
                'created_by' => $request->user()->id,
                'question_count' => count($request->validated('questions')),
            ]);

            [$questions, $unusedMedia] = $this->questionsWithMedia($request, $quiz);
            $quiz->syncQuestions($questions);

            return $unusedMedia;
        });

        $this->deleteMedia($unusedMedia);

        return redirect()
            ->route('admin.quizzes.index')
            ->with('success', 'Quiz added.');
    }

    public function edit(Quiz $quiz): Response
    {
        return Inertia::render('Admin/Quizzes/Form', $this->formProps($quiz));
    }

    public function update(QuizRequest $request, Quiz $quiz): RedirectResponse
    {
        $unusedMedia = DB::transaction(function () use ($request, $quiz): array {
            $quiz->update([
                ...$this->quizAttributes($request),
                'slug' => Quiz::uniqueSlug($request->string('title')->toString(), $quiz->id),
            ]);

            [$questions, $unusedMedia] = $this->questionsWithMedia($request, $quiz);
            $quiz->syncQuestions($questions);

            return $unusedMedia;
        });

        $this->deleteMedia($unusedMedia);

        return redirect()
            ->route('admin.quizzes.index')
            ->with('success', 'Quiz updated.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->deleteMedia($quiz->questions->flatMap(fn (QuizQuestion $question) => [
            $question->audio_path,
            $question->video_path,
        ])->all());

        $quiz->delete();

        return redirect()
            ->back()
            ->with('success', 'Quiz deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(?Quiz $quiz = null): array
    {
        return [
            'quiz' => $quiz ? [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'category' => $quiz->category,
                'difficulty' => $quiz->difficulty,
                'description' => $quiz->description,
                'is_published' => $quiz->is_published,
                'questions' => $quiz->questions->map(function ($question) {
                    $correct = array_search($question->answer, $question->choices, true);

                    return [
                        'id' => $question->id,
                        'prompt' => $question->prompt,
                        'choices' => $question->choices,
                        'correct' => $correct === false ? 0 : $correct,
                        'audio' => null,
                        'video' => null,
                        'audio_url' => $this->mediaUrl($question->audio_path),
                        'video_url' => $this->mediaUrl($question->video_path),
                        'keep_audio' => $question->audio_path !== null,
                        'keep_video' => $question->video_path !== null,
                    ];
                })->values(),
            ] : null,
            'categories' => Quiz::CATEGORIES,
            'difficulties' => Quiz::DIFFICULTIES,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function quizAttributes(QuizRequest $request): array
    {
        return $request->safe()->except('questions');
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>}
     */
    private function questionsWithMedia(QuizRequest $request, Quiz $quiz): array
    {
        $existing = $quiz->questions()->get()->keyBy('id');
        $unused = [];
        $keptIds = [];
        $questions = [];

        foreach ($request->validated('questions') as $index => $question) {
            $previous = null;
            $id = isset($question['id']) ? (int) $question['id'] : null;

            if ($id && $existing->has($id)) {
                $previous = $existing->get($id);
                $keptIds[] = $id;
            }

            $questions[] = [
                'prompt' => $question['prompt'],
                'choices' => $question['choices'],
                'correct' => $question['correct'],
                'audio_path' => $this->resolveMedia(
                    $request->file("questions.{$index}.audio"),
                    $previous?->audio_path,
                    (bool) ($question['keep_audio'] ?? false),
                    'questions/audio',
                    $unused,
                ),
                'video_path' => $this->resolveMedia(
                    $request->file("questions.{$index}.video"),
                    $previous?->video_path,
                    (bool) ($question['keep_video'] ?? false),
                    'questions/video',
                    $unused,
                ),
            ];
        }

        foreach ($existing as $id => $previous) {
            if (in_array((int) $id, $keptIds, true)) {
                continue;
            }

            if ($previous->audio_path) {
                $unused[] = $previous->audio_path;
            }

            if ($previous->video_path) {
                $unused[] = $previous->video_path;
            }
        }

        return [$questions, $unused];
    }

    /**
     * @param  array<int, string>  $unused
     */
    private function resolveMedia(?UploadedFile $file, ?string $current, bool $keep, string $directory, array &$unused): ?string
    {
        if ($file instanceof UploadedFile) {
            if ($current) {
                $unused[] = $current;
            }

            return $file->store($directory, 'public');
        }

        if ($keep) {
            return $current;
        }

        if ($current) {
            $unused[] = $current;
        }

        return null;
    }

    /**
     * @param  array<int, string|null>  $paths
     */
    private function deleteMedia(array $paths): void
    {
        $paths = array_values(array_filter($paths));

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }
    }

    private function mediaUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
