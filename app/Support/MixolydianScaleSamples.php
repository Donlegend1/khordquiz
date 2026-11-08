<?php

namespace App\Support;

class MixolydianScaleSamples extends TonalModeScaleSamples
{
    public const TITLE = 'Mixolydian Scales #1';

    public const LEGACY_TITLE = 'Mixolydian Mode';

    public const MOBILE_TITLE = 'Scales · Level 8';

    public const PREFIX = 'scales/mixolydian-scales-c-v1';

    public const TONIC_MIDI = 48;

    public const KEYS = ['c' => 0];

    public const SCALES = [
        'mixolydian' => ['Mixolydian', [0, 2, 4, 5, 7, 9, 10]],
        'mixolydian-flat-two' => ['Mixolydian ♭2', [0, 1, 4, 5, 7, 9, 10]],
        'mixolydian-flat-six' => ['Mixolydian ♭6', [0, 2, 4, 5, 7, 8, 10]],
        'mixolydian-flat-two-flat-six' => ['Mixolydian ♭2 ♭6', [0, 1, 4, 5, 7, 8, 10]],
        'mixolydian-sharp-four' => ['Mixolydian ♯4', [0, 2, 4, 6, 7, 9, 10]],
    ];
}
