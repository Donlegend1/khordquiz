<?php

namespace App\Services\KingsleyKhordPiano;

use App\Models\Subscription;
use App\Models\User;
use App\Notifications\KingsleyKhordPianoInvitation;
use App\Role;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Gives Kingsleykhordpiano subscribers KhordQuiz access.
 *
 * - syncAll()        every minute (kkp:sync): creates/updates every listed member and
 *                    revokes access for members no longer listed.
 * - refresh()        on login: live re-check of one member's status.
 * - provisionByEmail on a failed login / forgot-password for an email that has no
 *                    KhordQuiz account yet but is a Kingsleykhordpiano member.
 *
 * New accounts get a random password and an email invite to set their own.
 * A member who also pays for KhordQuiz directly (Stripe or the app stores)
 * keeps that subscription — membership access never overwrites a paid plan.
 */
class MemberSync
{
    public function __construct(private readonly Client $client) {}

    public function isEnabled(): bool
    {
        return $this->client->isConfigured();
    }

    /**
     * Works through the member list one API page (chunk) at a time. Each member
     * is saved in its own transaction, so one bad record is logged and skipped
     * instead of stopping the run or leaving a half-written account.
     *
     * @return array{synced: int, created: int, failed: int, revoked: int}
     */
    public function syncAll(bool $invite = true): array
    {
        $startedAt = now()->subSecond();
        $synced = $created = $failed = 0;

        foreach ($this->client->pages() as $page => $members) {
            foreach ($members as $member) {
                try {
                    [, $wasCreated] = DB::transaction(fn () => $this->sync($member, $invite));
                    $synced++;
                    $created += (int) $wasCreated;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::error('Kingsleykhordpiano member sync failed.', ['page' => $page + 1, 'email' => $member['email'], 'id' => $member['id'] ?? null, 'error' => $e->getMessage()]);
                }
            }

            Log::info('Kingsleykhordpiano members chunk synced.', ['page' => $page + 1, 'members' => count($members), 'synced' => $synced, 'failed' => $failed]);
        }

        // Revoking relies on every member having just been stamped. A failed
        // page request throws above, and a failed member wasn't stamped, so in
        // either case nobody is revoked this run.
        if ($failed > 0) {
            return ['synced' => $synced, 'created' => $created, 'failed' => $failed, 'revoked' => 0];
        }

        $revoked = 0;
        User::query()
            ->whereNotNull('kkp_member_id')
            ->where(fn ($query) => $query->whereNull('kkp_synced_at')->orWhere('kkp_synced_at', '<', $startedAt))
            ->each(function (User $user) use (&$revoked) {
                $revoked += (int) $this->revoke($user);
            });

        return ['synced' => $synced, 'created' => $created, 'failed' => 0, 'revoked' => $revoked];
    }

    /**
     * Re-checks one member against the API. Never throws — a slow or failing
     * Kingsleykhordpiano API must not stop anyone from signing in.
     */
    public function refresh(User $user): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        try {
            $member = $this->client->findByEmail($user->email);
        } catch (\Throwable $e) {
            Log::warning('Kingsleykhordpiano lookup failed.', ['user' => $user->id, 'error' => $e->getMessage()]);

            return;
        }

        if ($member !== null) {
            $this->sync($member, invite: false);
        } elseif ($user->kkp_member_id !== null) {
            $this->revoke($user);
        }
    }

    /**
     * Creates a KhordQuiz account for a Kingsleykhordpiano member who doesn't
     * have one yet. Returns the new user, or null if the email isn't an active
     * member (or already has an account).
     */
    public function provisionByEmail(string $email, bool $invite = true): ?User
    {
        if (! $this->isEnabled() || User::query()->where('email', Str::lower($email))->exists()) {
            return null;
        }

        try {
            $member = $this->client->findByEmail($email);
        } catch (\Throwable $e) {
            Log::warning('Kingsleykhordpiano lookup failed.', ['email' => $email, 'error' => $e->getMessage()]);

            return null;
        }

        if ($member === null || ! $member['active']) {
            return null;
        }

        return $this->sync($member, $invite)[0];
    }

    /**
     * @param  array{id: ?string, email: string, first_name: ?string, last_name: ?string, name: string, plan_id: string, expires_at: ?Carbon, active: bool}  $member
     * @return array{0: User, 1: bool} the user, and whether it was just created
     */
    public function sync(array $member, bool $invite = true): array
    {
        $user = User::query()->firstOrCreate(
            ['email' => $member['email']],
            [
                'name' => $member['name'],
                'first_name' => $member['first_name'],
                'last_name' => $member['last_name'],
                'password' => Hash::make(Str::random(40)),
                'role' => Role::Member,
            ],
        );
        $created = $user->wasRecentlyCreated;

        $user->forceFill([
            'first_name' => $user->first_name ?? $member['first_name'],
            'last_name' => $user->last_name ?? $member['last_name'],
            'kkp_member_id' => $member['id'] ?? $user->kkp_member_id ?? $member['email'],
            'kkp_synced_at' => now(),
            // Their address is already verified by Kingsleykhordpiano, and the
            // invite link proves it again.
            'email_verified_at' => $user->email_verified_at ?? ($created ? now() : null),
        ])->save();

        $this->applyMembership($user, $member);

        // Only active members are invited — a lapsed member's account exists
        // (with its expired subscription) but has nothing to unlock yet.
        if ($invite && $created && $member['active']) {
            $this->invite($user);
        }

        return [$user, $created];
    }

    public function invite(User $user): void
    {
        $user->notify(new KingsleyKhordPianoInvitation);
        $user->forceFill(['kkp_invited_at' => now()])->save();
    }

    /**
     * @param  array{plan_id: string, expires_at: ?Carbon, active: bool}  $member
     */
    private function applyMembership(User $user, array $member): void
    {
        $subscription = Subscription::forUser($user);

        if ($this->hasOwnPaidPlan($subscription)) {
            return;
        }

        // Never subscribed on Kingsleykhordpiano: nothing to record, but end
        // any KKP access they had before.
        if (! $member['active'] && ! ($member['has_subscription'] ?? true)) {
            $this->expire($subscription);

            return;
        }

        // A lapsed membership is recorded as an expired KKP plan, unless the
        // user has something else going on here (e.g. a KhordQuiz free trial).
        if (! $member['active'] && $subscription->provider !== Subscription::PROVIDER_KKP && $subscription->access !== 'none') {
            return;
        }

        $subscription->fill([
            'access' => 'paid',
            'plan_id' => $member['plan_id'],
            'status' => $member['active'] ? 'active' : 'expired',
            'provider' => Subscription::PROVIDER_KKP,
            'current_period_end' => $member['expires_at'],
            'renews' => $member['expires_at'] ? Subscription::formatDate($member['expires_at']) : null,
            'since' => $subscription->provider === Subscription::PROVIDER_KKP && $subscription->since ? $subscription->since : now()->format('M Y'),
            'next_plan' => null,
            'trial_cancelled' => false,
        ])->save();
    }

    private function revoke(User $user): bool
    {
        $subscription = Subscription::forUser($user);

        return $this->expire($subscription);
    }

    private function expire(Subscription $subscription): bool
    {
        if ($subscription->provider !== Subscription::PROVIDER_KKP || $subscription->status === 'expired') {
            return false;
        }

        $subscription->update(['status' => 'expired']);

        return true;
    }

    // A live plan the member pays KhordQuiz for directly.
    private function hasOwnPaidPlan(Subscription $subscription): bool
    {
        return in_array($subscription->provider, [Subscription::PROVIDER_STRIPE, 'App Store', 'Google Play'], true)
            && $subscription->access !== 'none'
            && $subscription->unlocksQuizzes();
    }
}
