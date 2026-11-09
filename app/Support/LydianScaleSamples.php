<?php

namespace App\Support;

class LydianScaleSamples extends TonalModeScaleSamples
{
    public const TITLE = 'Lydian Scales #1';

    public const LEGACY_TITLE = 'Lydian Mode';

    public const MOBILE_TITLE = 'Scales · Level 5';

    public const PREFIX = 'scales/lydian-scales-c-v1';

    public const TONIC_MIDI = 48;

    public const KEYS = ['c' => 0];

    public const SCALES = [
        'lydian' => ['Lydian', [0, 2, 4, 6, 7, 9, 11]],
        'lydian-sharp-two' => ['Lydian ♯2', [0, 3, 4, 6, 7, 9, 11]],
        'lydian-augmented' => ['Lydian Augmented', [0, 2, 4, 6, 8, 9, 11]],
        'lydian-dominant' => ['Lydian Dominant', [0, 2, 4, 6, 7, 9, 10]],
        'lydian-flat-three' => ['Lydian Diminished', [0, 2, 3, 6, 7, 9, 11]],
    ];
}
