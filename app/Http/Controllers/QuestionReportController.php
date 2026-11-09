<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionReportRequest;
use App\Models\QuestionReport;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class QuestionReportController extends Controller
{
    public function store(StoreQuestionReportRequest $request, Quiz $quiz): RedirectResponse
    {
        $data = $request->validated();
        $prompt = null;
        $instrument = null;
        if (! empty($data['session_id'])) {
            $session = QuizSession::where('user_id', $request->user()->id)->where('slug', $quiz->slug)->findOrFail($data['session_id']);
            $record = collect($session->review_answers ?? [])->firstWhere('question_id', (int) $data['question_id']);
            abort_unless($record, 404);
            $prompt = $record['prompt'];
            $instrument = $session->sound;
        } else {
            $attempt = QuizAttempt::where('user_id', $request->user()->id)->where('quiz_id', $quiz->id)->firstOrFail();
            abort_unless(in_array((int) $data['question_id'], $attempt->question_sequence ?? [], true), 404);
            $question = $quiz->questions()->findOrFail($data['question_id']);
            $prompt = $question->prompt;
            $instrument = $attempt->sound;
        }
        QuestionReport::create([
            'user_id' => $request->user()->id, 'quiz_id' => $quiz->id,
            'question_id' => $data['question_id'], 'quiz_title' => $quiz->title,
            'question_prompt' => $prompt, 'instrument' => $instrument,
            'reason' => $data['reason'], 'message' => $data['message'] ?? null,
        ]);

        return back()->with('success', 'Thank you. Your report has been saved.');
    }

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['open', 'resolved'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? '';
        $reports = QuestionReport::query()->with('user:id,name,email')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('quiz_title', 'like', '%'.$search.'%')
                        ->orWhere('reason', 'like', '%'.$search.'%')
                        ->orWhere('question_id', $search)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(25)->withQueryString();
        $counts = QuestionReport::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return Inertia::render('Admin/QuestionReports', [
            'reports' => $reports,
            'filters' => ['status' => $status, 'search' => $search],
            'counts' => ['all' => (int) $counts->sum(), 'open' => (int) ($counts['open'] ?? 0), 'resolved' => (int) ($counts['resolved'] ?? 0)],
        ]);
    }

    public function update(Request $request, QuestionReport $questionReport): RedirectResponse
    {
        $questionReport->update($request->validate(['status' => ['required', Rule::in(['open', 'resolved'])]]));

        return back();
    }
}
