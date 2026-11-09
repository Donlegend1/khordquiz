<?php

namespace App\Support;

class PhrygianScaleSamples extends TonalModeScaleSamples
{
    public const TITLE = 'Phrygian Scales #1';

    public const LEGACY_TITLE = 'Phrygian Mode';

    public const MOBILE_TITLE = 'Scales · Level 7';

    public const PREFIX = 'scales/phrygian-scales-c-v1';

    public const TONIC_MIDI = 48;

    public const KEYS = ['c' => 0];

    public const SCALES = [
        'phrygian' => ['Phrygian', [0, 1, 3, 5, 7, 8, 10]],
        'phrygian-dominant' => ['Phrygian Dominant', [0, 1, 4, 5, 7, 8, 10]],
        'super-phrygian' => ['Phrygian (♭4)', [0, 1, 3, 4, 7, 8, 10]],
        'phrygian-natural-six' => ['Phrygian ♮6', [0, 1, 3, 5, 7, 9, 10]],
        'phrygian-natural-seven' => ['Phrygian ♮7', [0, 1, 3, 5, 7, 8, 11]],
    ];
}
