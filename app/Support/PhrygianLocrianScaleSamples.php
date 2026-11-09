<?php

namespace App\Support;

class PhrygianLocrianScaleSamples extends TonalModeScaleSamples
{
    public const TITLE = 'Phrygian and Locrian';

    public const MOBILE_TITLE = self::TITLE;

    public const PREFIX = 'scales/phrygian-locrian-all-keys-v1';

    public const TONIC_MIDI = 48;

    public const KEYS = ModalVoicingLessons::KEYS;

    public const SCALES = [
        'phrygian' => ['Phrygian', [0, 1, 3, 5, 7, 8, 10]],
        'locrian' => ['Locrian', [0, 1, 3, 5, 6, 8, 10]],
    ];
}
