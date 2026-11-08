<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->transpose('f', 'c', 'F', 'C');
    }

    public function down(): void
    {
        $this->transpose('c', 'f', 'C', 'F');
    }

    private function transpose(string $from, string $to, string $fromLabel, string $toLabel): void
    {
        $quizIds = DB::table('quizzes')->where('category', 'Chord Naming')->whereIn('title', [
            'Chord Naming · Level 1', 'Chord Naming · Level 3', 'Chord Naming · Level 5',
        ])->pluck('id');
        $questions = DB::table('quiz_questions')->whereIn('quiz_id', $quizIds);
        foreach (DB::table('quiz_question_audio')->whereIn('quiz_question_id', (clone $questions)->select('id'))->cursor() as $audio) {
            $path = preg_replace('#/'.$from.'-([^/]+)\.wav$#', '/'.$to.'-$1.wav', $audio->audio_path ?? '');
            DB::table('quiz_question_audio')->where('id', $audio->id)->update(['audio_path' => $path]);
        }
        foreach ($questions->cursor() as $question) {
            if ($question->audio_path) {
                DB::table('quiz_questions')->where('id', $question->id)->update([
                    'audio_path' => preg_replace('#/'.$from.'-([^/]+)\.wav$#', '/'.$to.'-$1.wav', $question->audio_path),
                ]);
            }
        }
        foreach (DB::table('quizzes')->whereIn('id', $quizIds)->cursor() as $quiz) {
            DB::table('quizzes')->where('id', $quiz->id)->update([
                'description' => str_replace(' in '.$fromLabel, ' in '.$toLabel, $quiz->description ?? ''),
            ]);
        }
    }
};
