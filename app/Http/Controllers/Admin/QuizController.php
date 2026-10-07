<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuizRequest;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Quizzes/Index', [
            'quizzes' => Quiz::query()
                ->withCount('attempts')
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
        Quiz::query()->create([
            ...$request->validated(),
            'slug' => Quiz::uniqueSlug($request->string('title')->toString()),
            'created_by' => $request->user()->id,
        ]);

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
        $quiz->update([
            ...$request->validated(),
            'slug' => Quiz::uniqueSlug($request->string('title')->toString(), $quiz->id),
        ]);

        return redirect()
            ->route('admin.quizzes.index')
            ->with('success', 'Quiz updated.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
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
            'quiz' => $quiz?->only([
                'id',
                'title',
                'category',
                'difficulty',
                'description',
                'question_count',
                'is_published',
            ]),
            'categories' => Quiz::CATEGORIES,
            'difficulties' => Quiz::DIFFICULTIES,
        ];
    }
}
