<?php

namespace Database\Seeders;

class QuizQuestionBank
{
    /**
     * Questions that belong only to the quiz with this title.
     *
     * @return array<int, array{prompt: string, choices: array<int, string>, answer: string}>
     */
    public static function for(string $title): array
    {
        return self::bank()[$title] ?? [];
    }

    /**
     * @return array<string, array<int, array{prompt: string, choices: array<int, string>, answer: string}>>
     */
    private static function bank(): array
    {
        return [
            'Find the Note' => [
                [
                    'prompt' => 'The reference is C. The next note is a major 3rd higher. Which note is it?',
                    'choices' => ['E', 'Eb', 'F', 'D'],
                    'answer' => 'E',
                ],
                [
                    'prompt' => 'The reference is A. You hear a perfect 5th above it. Which note is it?',
                    'choices' => ['E', 'B', 'F#', 'C'],
                    'answer' => 'E',
                ],
                [
                    'prompt' => 'The reference is G. The note is a minor 2nd above. Which note is it?',
                    'choices' => ['G#', 'A', 'F#', 'Bb'],
                    'answer' => 'G#',
                ],
                [
                    'prompt' => 'The reference is F. You hear a perfect 4th higher. Which note is it?',
                    'choices' => ['Bb', 'A', 'C', 'B'],
                    'answer' => 'Bb',
                ],
            ],
            'Basic Triads · Level 1' => [
                [
                    'prompt' => 'The notes C, E, and G form which triad?',
                    'choices' => ['Major', 'Minor', 'Diminished', 'Augmented'],
                    'answer' => 'Major',
                ],
                [
                    'prompt' => 'The notes D, F, and A form which triad?',
                    'choices' => ['Minor', 'Major', 'Diminished', 'Augmented'],
                    'answer' => 'Minor',
                ],
                [
                    'prompt' => 'The notes B, D, and F form which triad?',
                    'choices' => ['Diminished', 'Minor', 'Major', 'Augmented'],
                    'answer' => 'Diminished',
                ],
                [
                    'prompt' => 'The notes C, E, and G# form which triad?',
                    'choices' => ['Augmented', 'Major', 'Minor', 'Diminished'],
                    'answer' => 'Augmented',
                ],
            ],
            'Cadences' => [
                [
                    'prompt' => 'In C major, the phrase ends G to C. What cadence is that?',
                    'choices' => ['Authentic', 'Plagal', 'Half', 'Deceptive'],
                    'answer' => 'Authentic',
                ],
                [
                    'prompt' => 'In C major, the phrase ends F to C. What cadence is that?',
                    'choices' => ['Plagal', 'Authentic', 'Half', 'Deceptive'],
                    'answer' => 'Plagal',
                ],
                [
                    'prompt' => 'In C major, the phrase stops on G. What cadence is that?',
                    'choices' => ['Half', 'Authentic', 'Plagal', 'Deceptive'],
                    'answer' => 'Half',
                ],
                [
                    'prompt' => 'In C major, G resolves to A minor instead of C. What cadence is that?',
                    'choices' => ['Deceptive', 'Authentic', 'Plagal', 'Half'],
                    'answer' => 'Deceptive',
                ],
            ],
            'Tonal Modes' => [
                [
                    'prompt' => 'A scale from D to D with B natural (a raised 6th) is which mode?',
                    'choices' => ['Dorian', 'Phrygian', 'Aeolian', 'Locrian'],
                    'answer' => 'Dorian',
                ],
                [
                    'prompt' => 'A scale from E to E that starts with a half step (F natural) is which mode?',
                    'choices' => ['Phrygian', 'Dorian', 'Lydian', 'Ionian'],
                    'answer' => 'Phrygian',
                ],
                [
                    'prompt' => 'A scale from F to F with B natural (a raised 4th) is which mode?',
                    'choices' => ['Lydian', 'Mixolydian', 'Ionian', 'Dorian'],
                    'answer' => 'Lydian',
                ],
                [
                    'prompt' => 'A scale from G to G with F natural (a lowered 7th) is which mode?',
                    'choices' => ['Mixolydian', 'Ionian', 'Dorian', 'Aeolian'],
                    'answer' => 'Mixolydian',
                ],
            ],
            'Chord naming' => [
                [
                    'prompt' => 'The chord tones are C, E, G, B, D. What is the chord?',
                    'choices' => ['Cmaj9', 'C7', 'Cm9', 'Cadd9'],
                    'answer' => 'Cmaj9',
                ],
                [
                    'prompt' => 'The chord tones are G, B, D, F. What is the chord?',
                    'choices' => ['G7', 'Gmaj7', 'Gm7', 'Gdim7'],
                    'answer' => 'G7',
                ],
                [
                    'prompt' => 'The chord tones are D, F, A, C, E. What is the chord?',
                    'choices' => ['Dm9', 'D9', 'Dm7', 'Dmaj9'],
                    'answer' => 'Dm9',
                ],
                [
                    'prompt' => 'The chord tones are C, E, G, Bb, D. What is the chord?',
                    'choices' => ['C9', 'Cmaj9', 'Cm9', 'Cmaj7'],
                    'answer' => 'C9',
                ],
            ],
            'Modal Voicings · Level 1' => [
                [
                    'prompt' => 'A voicing with a raised 4th above the bass suggests which mode?',
                    'choices' => ['Lydian', 'Dorian', 'Mixolydian', 'Phrygian'],
                    'answer' => 'Lydian',
                ],
                [
                    'prompt' => 'A voicing with a flat 2nd above the bass suggests which mode?',
                    'choices' => ['Phrygian', 'Dorian', 'Lydian', 'Ionian'],
                    'answer' => 'Phrygian',
                ],
                [
                    'prompt' => 'A voicing with a minor 3rd and a natural 6th suggests which mode?',
                    'choices' => ['Dorian', 'Aeolian', 'Phrygian', 'Locrian'],
                    'answer' => 'Dorian',
                ],
                [
                    'prompt' => 'A voicing with a major 3rd and a flat 7th suggests which mode?',
                    'choices' => ['Mixolydian', 'Ionian', 'Lydian', 'Dorian'],
                    'answer' => 'Mixolydian',
                ],
            ],
        ];
    }
}
