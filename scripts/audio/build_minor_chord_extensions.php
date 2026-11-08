<?php

use App\Support\ChordExtensionQuizBuilder;
use App\Support\MinorChordExtensionSamples;

foreach (ChordExtensionQuizBuilder::build('Minor', MinorChordExtensionSamples::class) as $result) {
    echo 'Minor Level '.$result['level'].': '.$result['questions'].' questions, '.$result['attachments'].' playable instrument attachments.'.PHP_EOL;
}
