<?php

namespace App\Support;

class MajorScaleSamples extends TonalModeScaleSamples
{
    public const TITLE = 'Major Scales #1';

    public const PREVIOUS_TITLE = 'Major Scales';

    public const LEGACY_TITLE = 'Major mode';

    public const MOBILE_TITLE = 'Scales · Level 3';

    public const PREFIX = 'scales/major-scales-c-v1';

    public const TONIC_MIDI = 48;

    public const KEYS = ['c' => 0];

    public const SCALES = [
        'ionian' => ['Ionian', [0, 2, 4, 5, 7, 9, 11]],
        'dorian' => ['Dorian', [0, 2, 3, 5, 7, 9, 10]],
        'phrygian' => ['Phrygian', [0, 1, 3, 5, 7, 8, 10]],
        'lydian' => ['Lydian', [0, 2, 4, 6, 7, 9, 11]],
        'mixolydian' => ['Mixolydian', [0, 2, 4, 5, 7, 9, 10]],
        'aeolian' => ['Aeolian', [0, 2, 3, 5, 7, 8, 10]],
        'locrian' => ['Locrian', [0, 1, 3, 5, 6, 8, 10]],
    ];
}
