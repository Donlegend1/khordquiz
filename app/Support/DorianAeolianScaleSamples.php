<?php

namespace App\Support;

class DorianAeolianScaleSamples extends TonalModeScaleSamples
{
    public const TITLE = 'Dorian and Aeolian';

    public const MOBILE_TITLE = self::TITLE;

    public const PREFIX = 'scales/dorian-aeolian-all-keys-v1';

    public const TONIC_MIDI = 48;

    public const KEYS = ModalVoicingLessons::KEYS;

    public const SCALES = [
        'dorian' => ['Dorian', [0, 2, 3, 5, 7, 9, 10]],
        'aeolian' => ['Aeolian', [0, 2, 3, 5, 7, 8, 10]],
    ];
}
