<?php

namespace Database\Seeders;

use App\Models\Award;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = $this->account(
            name: 'Khord Admin',
            email: 'admin@khordquiz.com',
            role: Role::Admin,
        );

        $member = $this->account(
            name: 'Amara Okonkwo',
            email: 'member@khordquiz.com',
            role: Role::Member,
            currentStreak: 7,
            longestStreak: 12,
            lastPracticedOn: now()->toDateString(),
        );

        $this->account(
            name: 'Demo User',
            email: 'demo@khordquiz.com',
            role: Role::Member,
            currentStreak: 2,
            longestStreak: 2,
            lastPracticedOn: now()->subDays(2)->toDateString(),
        );

        $jordan = $this->account(
            name: 'Jordan Adeyemi',
            email: 'jordan@khordquiz.com',
            role: Role::Member,
            currentStreak: 14,
            longestStreak: 21,
            lastPracticedOn: now()->toDateString(),
        );

        $chioma = $this->account(
            name: 'Chioma Bello',
            email: 'chioma@khordquiz.com',
            role: Role::Member,
            currentStreak: 3,
            longestStreak: 9,
            lastPracticedOn: now()->subDay()->toDateString(),
        );

        $this->account(
            name: 'Tunde Lawal',
            email: 'tunde@khordquiz.com',
            role: Role::Member,
            currentStreak: 1,
            longestStreak: 4,
            lastPracticedOn: now()->subDays(4)->toDateString(),
        );

        $quizzes = collect($this->catalog())->mapWithKeys(function (array $quiz) use ($admin) {
            $record = Quiz::query()->updateOrCreate(
                ['slug' => Str::slug($quiz['title'])],
                [
                    ...$quiz,
                    'slug' => Str::slug($quiz['title']),
                    'created_by' => $admin->id,
                ],
            );

            return [$record->slug => $record];
        });

        $this->progress($member, $quizzes['find-the-note'], QuizAttempt::STATUS_COMPLETED, 25, 23, now()->subDays(2));
        $this->progress($member, $quizzes['harmonic-5th'], QuizAttempt::STATUS_IN_PROGRESS, 12, 9, now()->subHours(3));
        $this->progress($member, $quizzes['cadences'], QuizAttempt::STATUS_COMPLETED, 25, 20, now()->subDay());
        $this->progress($member, $quizzes['diatonic-intervals'], QuizAttempt::STATUS_IN_PROGRESS, 4, 3, now()->subDays(5));

        $this->progress($jordan, $quizzes['tonal-modes'], QuizAttempt::STATUS_IN_PROGRESS, 18, 14, now()->subHour());
        $this->progress($chioma, $quizzes['cadences'], QuizAttempt::STATUS_COMPLETED, 25, 17, now()->subDay());

        $this->award($member, '7-day streak', 'Practiced seven days in a row.', 'streak', now()->subDay());
        $this->award(
            $member,
            'Sharp ear',
            'Scored 23 out of 25 on Find the Note.',
            'score',
            now()->subDays(2),
            $quizzes['find-the-note']->id,
        );
        $this->award($member, 'First quiz', 'Finished a quiz from start to end.', 'milestone', now()->subDays(6));
    }

    private function account(
        string $name,
        string $email,
        Role $role,
        int $currentStreak = 0,
        int $longestStreak = 0,
        ?string $lastPracticedOn = null,
    ): User {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email_verified_at' => now(),
                'password' => 'password',
                'role' => $role,
                'current_streak' => $currentStreak,
                'longest_streak' => $longestStreak,
                'last_practiced_on' => $lastPracticedOn,
            ],
        );
    }

    private function progress(
        User $user,
        Quiz $quiz,
        string $status,
        int $stoppedAt,
        int $correct,
        Carbon $playedAt,
    ): void {
        QuizAttempt::query()->updateOrCreate(
            ['user_id' => $user->id, 'quiz_id' => $quiz->id],
            [
                'status' => $status,
                'stopped_at_question' => $stoppedAt,
                'correct_answers' => $correct,
                'last_played_at' => $playedAt,
                'completed_at' => $status === QuizAttempt::STATUS_COMPLETED ? $playedAt : null,
            ],
        );
    }

    private function award(
        User $user,
        string $title,
        string $description,
        string $kind,
        Carbon $earnedAt,
        ?int $quizId = null,
    ): void {
        Award::query()->updateOrCreate(
            ['user_id' => $user->id, 'title' => $title],
            [
                'quiz_id' => $quizId,
                'description' => $description,
                'kind' => $kind,
                'earned_at' => $earnedAt,
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalog(): array
    {
        return [
            [
                'title' => 'Find the Note',
                'category' => 'Relative Pitch',
                'difficulty' => 'Beginner',
                'description' => 'Compare a note to the reference tone, then name it.',
                'question_count' => 25,
                'is_published' => true,
            ],
            [
                'title' => 'Harmonic 5th',
                'category' => 'Relative Pitch',
                'difficulty' => 'Beginner',
                'description' => 'Hear two notes at once and tell when they form a fifth.',
                'question_count' => 25,
                'is_published' => true,
            ],
            [
                'title' => 'Diatonic Intervals',
                'category' => 'Intervals',
                'difficulty' => 'Beginner',
                'description' => 'Name the interval between two notes inside a key.',
                'question_count' => 25,
                'is_published' => true,
            ],
            [
                'title' => 'Basic Triads — Level 1',
                'category' => 'Basic Triads',
                'difficulty' => 'Beginner',
                'description' => 'Recognise major, minor, diminished and augmented triads.',
                'question_count' => 25,
                'is_published' => true,
            ],
            [
                'title' => '3-Note Melody',
                'category' => 'Melodic Dictation',
                'difficulty' => 'Beginner',
                'description' => 'Listen to a short melody and identify the notes played.',
                'question_count' => 25,
                'is_published' => true,
            ],
            [
                'title' => 'Cadences',
                'category' => 'Chord Progressions',
                'difficulty' => 'Intermediate',
                'description' => 'Hear authentic, plagal, half and deceptive cadences.',
                'question_count' => 25,
                'is_published' => true,
            ],
            [
                'title' => 'Tonal Modes',
                'category' => 'Scales',
                'difficulty' => 'Intermediate',
                'description' => 'Tell the modes apart by their sound.',
                'question_count' => 25,
                'is_published' => true,
            ],
            [
                'title' => 'Chord Naming',
                'category' => 'Extensions',
                'difficulty' => 'Advanced',
                'description' => 'Name the chord after you hear its extensions.',
                'question_count' => 25,
                'is_published' => true,
            ],
            [
                'title' => 'Modal Voicings — Level 1',
                'category' => 'Modal Voicings',
                'difficulty' => 'Advanced',
                'description' => 'Recognise chord voicings built from different modes.',
                'question_count' => 25,
                'is_published' => false,
            ],
        ];
    }
}
