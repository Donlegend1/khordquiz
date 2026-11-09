<?php

use Illuminate\Foundation\Inspiring;
use App\Services\KingsleyKhordPiano\MemberSync;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Gives every subscribed Kingsleykhordpiano member KhordQuiz access (creating
// accounts + emailing invites for new ones) and revokes it for lapsed members.
Artisan::command('kkp:sync {--no-invite : Create accounts without emailing invites}', function (MemberSync $sync) {
    if (! $sync->isEnabled()) {
        $this->error('Set KKP_API_URL (and KKP_API_TOKEN) in .env first.');

        return 1;
    }

    $result = $sync->syncAll(invite: ! $this->option('no-invite'));
    $this->info("Synced {$result['synced']} members: {$result['created']} new accounts, {$result['revoked']} revoked.");

    return 0;
})->purpose('Sync subscribed Kingsleykhordpiano members into KhordQuiz');

Schedule::command('kkp:sync')->everyMinute()->withoutOverlapping()->onOneServer();
