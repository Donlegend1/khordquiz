<?php

namespace App\Support;

class HelpCenter
{
    /**
     * @return list<array{title: string, icon: string, faqs: list<array{question: string, answer: string}>}>
     */
    public static function sections(): array
    {
        return [
            [
                'icon' => 'rocket-outline',
                'title' => 'Getting started',
                'faqs' => [
                    ['question' => 'How do I start a quiz?', 'answer' => 'Open the Quiz tab, choose a category, then pick a level and tap Start Quiz. Listen with the play button and choose your answer.'],
                    ['question' => 'Which quiz should I begin with?', 'answer' => 'If you are new to ear training, start with Relative Pitch and Intervals, then move on to Basic Triads and the chord categories.'],
                    ['question' => 'Can I change the instrument sound?', 'answer' => 'Yes. Choose an instrument on a quiz before you start, or set a default for all quizzes in More → Settings → Default instrument.'],
                ],
            ],
            [
                'icon' => 'musical-notes-outline',
                'title' => 'Quizzes',
                'faqs' => [
                    ['question' => 'How many questions are in a quiz?', 'answer' => 'Each quiz has 25 questions. Finish all of them to mark the quiz as completed.'],
                    ['question' => 'How do I practise my mistakes?', 'answer' => 'On the Progress tab, tap Review mistakes. You’ll replay up to 10 questions you recently got wrong. Answer one correctly and it leaves the list.'],
                    ['question' => 'How do I save a quiz for later?', 'answer' => 'Tap the bookmark icon on a quiz. Your saved quizzes appear in More → Saved quizzes.'],
                ],
            ],
            [
                'icon' => 'stats-chart-outline',
                'title' => 'Progress & goals',
                'faqs' => [
                    ['question' => 'How is my accuracy calculated?', 'answer' => 'Accuracy is the share of all your answers that were correct. The badge next to it compares the last 7 days with the week before.'],
                    ['question' => 'How does the daily streak work?', 'answer' => 'Answer at least one question each day to keep your streak going. Missing a full day resets it.'],
                    ['question' => 'Can I change my daily goal?', 'answer' => 'Yes. Tap Change on the Today’s goal card in Progress, or go to More → Settings → Daily goal.'],
                    ['question' => 'Will I lose my progress if I delete the app?', 'answer' => 'Progress is saved to your KhordQuiz account, so it stays with you when you reinstall or switch phones.'],
                ],
            ],
            [
                'icon' => 'card-outline',
                'title' => 'Account & billing',
                'faqs' => [
                    ['question' => 'How do I manage my subscription?', 'answer' => 'Go to More → Account and tap Manage Subscription to see or change your plan.'],
                    ['question' => 'How do I change my password?', 'answer' => 'Go to More → Account → Change password and enter your new password twice.'],
                    ['question' => 'How do I turn notifications on or off?', 'answer' => 'Go to More → Settings and use the switches under Notifications.'],
                ],
            ],
        ];
    }
}
