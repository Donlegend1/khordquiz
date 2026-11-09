<?php

use App\Models\Quiz;

$quiz = Quiz::firstOrCreate(
    ['category' => 'Modal Harmony', 'title' => 'Major Triads over Bass'],
    [
        'slug' => Quiz::uniqueSlug('Modal Harmony Major Triads over Bass'),
        'difficulty' => 'Advanced',
        'description' => 'Explore modal colors using a single bass note with a major triad above it. Questions and levels are coming soon.',
        'question_count' => 25,
        'is_published' => false,
    ],
);
echo ($quiz->wasRecentlyCreated ? 'Created' : 'Kept').' Modal Harmony draft: '.$quiz->title.PHP_EOL;
