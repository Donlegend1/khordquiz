<?php

use App\Models\QuizQuestionAudio;
use App\Support\SingleKeyChordCoverage;
use Illuminate\Support\Facades\DB;

config(['cache.default' => 'array']);
$plan = SingleKeyChordCoverage::plan();
echo $plan['lessons'].' single-key lessons, '.$plan['questions'].' questions; remove '.count($plan['remove']).' unplayable attachments, add '.count($plan['add']).' playable attachments.'.PHP_EOL;
if (! in_array('--apply', $_SERVER['argv'] ?? [], true)) {
    echo 'Preview only; pass --apply to refresh audio coverage.'.PHP_EOL;

    return;
}
$backup = QuizQuestionAudio::whereIn('id', $plan['remove'])->get()->map->getAttributes()->all();
$backupPath = storage_path('app/private/single-key-f-coverage-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
if (file_put_contents($backupPath, json_encode(['removed_rows' => $backup, 'new_rows' => $plan['add']], JSON_THROW_ON_ERROR)) === false) {
    throw new RuntimeException('Could not save audio coverage backup.');
}
echo 'Saved attachment backup: '.$backupPath.PHP_EOL;
DB::transaction(function () use ($plan): void {
    SingleKeyChordCoverage::apply($plan);
    $check = SingleKeyChordCoverage::plan();
    if ($check['remove'] !== [] || $check['add'] !== []) {
        throw new RuntimeException('Coverage audit did not converge.');
    }
    echo $check['lessons'].' lessons and '.$check['questions'].' questions verified; zero orphan questions.'.PHP_EOL;
});
