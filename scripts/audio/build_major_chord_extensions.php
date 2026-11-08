<?php

use App\Support\ChordExtensionQuizBuilder;
use App\Support\MajorChordExtensionSamples;

// Each group has basic F/all-key levels, then variation F/all-key levels.
// Level 13 is the General review; audio source-group namespaces stay fixed.
foreach (ChordExtensionQuizBuilder::build('Major', MajorChordExtensionSamples::class) as $result) {
    echo 'Major Level '.$result['level'].': '.$result['questions'].' questions, '.$result['attachments'].' playable instrument attachments.'.PHP_EOL;
}
