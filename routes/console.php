<?php

use App\Models\Subscription;
use App\Notifications\TrialEndingSoon;
use App\Services\KingsleyKhordPiano\MemberSync;
use App\Services\PracticeReminders;
use App\Support\Plans;
use Illuminate\Foundation\Inspiring;
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

    if ($result['failed'] > 0) {
        $this->warn("{$result['failed']} members failed (see the log) — revoking was skipped this run.");

        return 1;
    }

    return 0;
})->purpose('Sync subscribed Kingsleykhordpiano members into KhordQuiz');

Schedule::command('kkp:sync')->everyMinute()->withoutOverlapping()->onOneServer();

// Warns members whose free trial ends within two days, once per trial
// (the trial's end date is stored on the notification to tell trials apart).
Artisan::command('notifications:trial-ending', function () {
    $sent = 0;

    Subscription::query()
        ->with('user')
        ->where('access', 'trial')
        ->where('trial_cancelled', false)
        ->whereBetween('trial_ends_at', [now(), now()->addDays(2)])
        ->each(function (Subscription $subscription) use (&$sent) {
            $plan = Plans::all()[$subscription->plan_id] ?? null;
            $endsOn = $subscription->trial_ends_at->toDateString();
            $user = $subscription->user;

            if ($plan === null || $user === null || $user->notifications()->where('type', TrialEndingSoon::class)->where('data->ends_on', $endsOn)->exists()) {
                return;
            }

            $daysLeft = max(1, (int) ceil(now()->diffInSeconds($subscription->trial_ends_at) / 86400));
            $user->notify(new TrialEndingSoon($daysLeft, $plan['tier'].' '.$plan['label'], $plan['price'], $endsOn));
            $sent++;
        });

    $this->info("Sent {$sent} trial-ending notifications.");
})->purpose('Notify members whose free trial ends soon');

Schedule::command('notifications:trial-ending')->hourly()->withoutOverlapping()->onOneServer();

// Settings → Practice reminders and Streak alerts (see PracticeReminders).
Artisan::command('notifications:reminders', function (PracticeReminders $reminders) {
    $sent = $reminders->send();
    $this->info("Sent {$sent['reminders']} practice reminders and {$sent['streak_alerts']} streak alerts.");
})->purpose('Send practice reminders and streak alerts that are due');

Schedule::command('notifications:reminders')->everyFiveMinutes()->withoutOverlapping()->onOneServer();
