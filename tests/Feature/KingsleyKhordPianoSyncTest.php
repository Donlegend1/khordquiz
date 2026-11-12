<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Notifications\KingsleyKhordPianoInvitation;
use App\Services\KingsleyKhordPiano\MemberSync;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class KingsleyKhordPianoSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.kingsleykhordpiano.base_url' => 'https://kkp.test',
            'services.kingsleykhordpiano.token' => 'secret',
            'services.kingsleykhordpiano.members_path' => '/api/members',
            'services.kingsleykhordpiano.member_path' => '/api/members/lookup?email={email}',
            'services.kingsleykhordpiano.per_page' => 0,
        ]);

        Notification::fake();
    }

    public function test_nightly_sync_creates_members_invites_them_and_grants_access(): void
    {
        Http::fake([
            'kkp.test/api/members' => Http::response([
                'data' => [
                    ['id' => 11, 'email' => 'Grace@Example.com', 'first_name' => 'Grace', 'last_name' => 'Ade', 'plan' => 'Annual Membership', 'status' => 'active', 'expires_at' => now()->addMonths(6)->toIso8601String()],
                ],
                'next_page_url' => 'https://kkp.test/api/members?page=2',
            ]),
            'kkp.test/api/members?page=2' => Http::response([
                'data' => [['id' => 12, 'email' => 'tolu@example.com', 'name' => 'Tolu B', 'plan' => 'Lifetime', 'status' => 'active']],
                'next_page_url' => null,
            ]),
        ]);

        $this->artisan('kkp:sync')->assertSuccessful();

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer secret'));

        $grace = User::query()->where('email', 'grace@example.com')->firstOrFail();
        $this->assertSame('11', $grace->kkp_member_id);
        $this->assertNotNull($grace->email_verified_at);
        $this->assertTrue($grace->hasQuizAccess());
        $this->assertSame('yearly', Subscription::forUser($grace)->plan_id);
        $this->assertSame(Subscription::PROVIDER_KKP, Subscription::forUser($grace)->provider);

        $tolu = User::query()->where('email', 'tolu@example.com')->firstOrFail();
        $this->assertSame('lifetime', Subscription::forUser($tolu)->plan_id);

        Notification::assertSentTo([$grace, $tolu], KingsleyKhordPianoInvitation::class);
    }

    public function test_sync_reads_the_real_kingsleykhordpiano_payload_across_meta_paginated_pages(): void
    {
        config(['services.kingsleykhordpiano.per_page' => 3]);
        $item = fn (int $id, string $email, bool $subscribed, ?array $subscription) => [
            'member' => ['id' => $id, 'first_name' => 'First'.$id, 'last_name' => 'Last', 'display_name' => "First{$id} Last", 'email' => $email, 'email_verified' => true],
            'subscribed' => $subscribed,
            'subscription' => $subscription,
        ];

        Http::fake([
            'kkp.test/api/members?per_page=3&page=2' => Http::response([
                'data' => [$item(3, 'life@example.com', true, ['status' => 'active', 'type' => null, 'provider' => 'manual', 'is_lifetime' => true, 'expires_at' => null, 'plan' => null])],
                'meta' => ['current_page' => 2, 'last_page' => 2, 'has_more' => false],
            ]),
            'kkp.test/api/members?per_page=3' => Http::response([
                'synced_at' => now()->toIso8601String(),
                'data' => [
                    $item(1, 'paid@example.com', true, ['status' => 'active', 'type' => 'monthly', 'provider' => 'paypal', 'is_lifetime' => false, 'expires_at' => now()->addMonth()->toIso8601String(), 'plan' => ['id' => 1, 'tier' => 'standard', 'type' => 'monthly']]),
                    $item(2, 'never@example.com', false, null),
                    $item(4, 'expired@example.com', true, ['status' => 'expired', 'type' => null, 'provider' => 'stripe', 'is_lifetime' => false, 'expires_at' => now()->addWeek()->toIso8601String(), 'plan' => null]),
                ],
                'meta' => ['current_page' => 1, 'last_page' => 2, 'has_more' => true],
            ]),
        ]);

        $this->artisan('kkp:sync')->assertSuccessful();

        $paid = User::query()->where('email', 'paid@example.com')->firstOrFail();
        $this->assertSame('1', $paid->kkp_member_id);
        $this->assertSame('First1', $paid->first_name);
        $this->assertTrue($paid->hasQuizAccess());
        $this->assertSame('monthly', Subscription::forUser($paid)->plan_id);

        $life = User::query()->where('email', 'life@example.com')->firstOrFail();
        $this->assertTrue($life->hasQuizAccess());
        $this->assertSame('lifetime', Subscription::forUser($life)->plan_id);

        // Account exists, but no made-up KKP subscription.
        $never = User::query()->where('email', 'never@example.com')->firstOrFail();
        $this->assertFalse($never->hasQuizAccess());
        $this->assertNull(Subscription::forUser($never)->provider);

        // "subscribed": true but status "expired" → no access.
        $expired = User::query()->where('email', 'expired@example.com')->firstOrFail();
        $this->assertFalse($expired->hasQuizAccess());
        $this->assertSame('expired', Subscription::forUser($expired)->status);

        Notification::assertSentTo([$paid, $life], KingsleyKhordPianoInvitation::class);
        Notification::assertNotSentTo([$never, $expired], KingsleyKhordPianoInvitation::class);
    }

    public function test_one_failing_member_is_skipped_without_stopping_the_run_or_revoking_anyone(): void
    {
        $other = User::factory()->create(['kkp_member_id' => '99']);
        Subscription::forUser($other)->update(['access' => 'paid', 'plan_id' => 'yearly', 'status' => 'active', 'provider' => Subscription::PROVIDER_KKP]);

        User::creating(function (User $user) {
            if ($user->email === 'broken@example.com') {
                throw new \RuntimeException('boom');
            }
        });

        Http::fake(['kkp.test/*' => Http::response(['data' => [
            ['id' => 1, 'email' => 'broken@example.com', 'status' => 'active'],
            // A far-future grant must save fine (MySQL TIMESTAMP stops at 2038).
            ['id' => 2, 'email' => 'fine@example.com', 'status' => 'active', 'plan' => 'yearly', 'expires_at' => '2050-11-03T12:49:00+00:00'],
        ]])]);

        $this->artisan('kkp:sync')->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'broken@example.com']);
        $fine = User::query()->where('email', 'fine@example.com')->firstOrFail();
        $this->assertTrue($fine->hasQuizAccess());
        $this->assertSame(2050, Subscription::forUser($fine)->current_period_end->year);
        // A failed member means the run is incomplete, so nobody is revoked.
        $this->assertTrue($other->fresh()->hasQuizAccess());
    }

    public function test_sync_creates_every_returned_member_with_their_subscription_even_when_lapsed(): void
    {
        $existing = User::factory()->create(['email' => 'old@example.com']);

        Http::fake(['kkp.test/*' => Http::response(['data' => [
            ['id' => 21, 'email' => 'lapsed@example.com', 'name' => 'Lapsed One', 'plan' => 'monthly', 'status' => 'cancelled', 'expires_at' => now()->subWeek()->toIso8601String()],
            ['id' => 22, 'email' => 'OLD@example.com', 'name' => 'Old Member', 'plan' => 'yearly', 'status' => 'active'],
        ]])]);

        $this->artisan('kkp:sync')->assertSuccessful();

        $lapsed = User::query()->where('email', 'lapsed@example.com')->firstOrFail();
        $subscription = Subscription::forUser($lapsed);
        $this->assertSame('21', $lapsed->kkp_member_id);
        $this->assertSame(Subscription::PROVIDER_KKP, $subscription->provider);
        $this->assertSame('monthly', $subscription->plan_id);
        $this->assertSame('expired', $subscription->status);
        $this->assertFalse($lapsed->hasQuizAccess());
        Notification::assertNotSentTo($lapsed, KingsleyKhordPianoInvitation::class);

        // Existing account is matched, not duplicated, and gets the membership.
        $this->assertSame(1, User::query()->where('email', 'old@example.com')->count());
        $this->assertSame('22', $existing->fresh()->kkp_member_id);
        $this->assertTrue($existing->fresh()->hasQuizAccess());
    }

    public function test_members_missing_from_the_list_lose_access_but_paying_members_keep_theirs(): void
    {
        $lapsed = User::factory()->create(['kkp_member_id' => '5']);
        Subscription::forUser($lapsed)->update(['access' => 'paid', 'plan_id' => 'yearly', 'status' => 'active', 'provider' => Subscription::PROVIDER_KKP]);

        $payer = User::factory()->subscribed('monthly')->create(['email' => 'payer@example.com']);

        Http::fake(['kkp.test/*' => Http::response([['id' => 7, 'email' => 'payer@example.com', 'plan' => 'yearly', 'status' => 'active']])]);

        $this->artisan('kkp:sync')->assertSuccessful();

        $this->assertFalse($lapsed->fresh()->hasQuizAccess());
        $this->assertSame('Stripe', Subscription::forUser($payer)->provider);
        $this->assertSame('monthly', Subscription::forUser($payer)->plan_id);
        // Existing accounts aren't re-invited.
        Notification::assertNotSentTo($payer, KingsleyKhordPianoInvitation::class);
    }

    public function test_a_failed_api_call_never_revokes_anyone(): void
    {
        $member = User::factory()->create(['kkp_member_id' => '5']);
        Subscription::forUser($member)->update(['access' => 'paid', 'plan_id' => 'yearly', 'status' => 'active', 'provider' => Subscription::PROVIDER_KKP]);
        Http::fake(['kkp.test/*' => Http::response('down', 500)]);

        try {
            app(MemberSync::class)->syncAll();
        } catch (\Throwable) {
            // expected
        }

        $this->assertTrue($member->fresh()->hasQuizAccess());
    }

    public function test_first_login_attempt_by_a_member_creates_their_account_and_emails_an_invite(): void
    {
        Http::fake(['kkp.test/api/members/lookup*' => Http::response(['member' => ['id' => 9, 'email' => 'new@example.com', 'name' => 'New Member', 'status' => 'active']])]);

        $this->post('/login', ['email' => 'new@example.com', 'password' => 'whatever'])
            ->assertSessionHasErrors(['email' => __('Your Kingsleykhordpiano membership includes KhordQuiz! We\'ve emailed you a link to set your password.')]);

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue($user->hasQuizAccess());
        Notification::assertSentTo($user, KingsleyKhordPianoInvitation::class);
        $this->assertGuest();
    }

    public function test_non_members_get_the_normal_login_error(): void
    {
        Http::fake(['kkp.test/*' => Http::response([], 404)]);

        $this->post('/login', ['email' => 'stranger@example.com', 'password' => 'whatever'])
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);

        $this->assertDatabaseMissing('users', ['email' => 'stranger@example.com']);
    }

    public function test_login_rechecks_membership_and_survives_the_api_being_down(): void
    {
        $user = User::factory()->create(['email' => 'member@example.com', 'kkp_member_id' => '3']);
        Subscription::forUser($user)->update(['access' => 'paid', 'plan_id' => 'yearly', 'status' => 'active', 'provider' => Subscription::PROVIDER_KKP]);

        // Lapsed on Kingsleykhordpiano → access is revoked at login.
        Http::fake(['kkp.test/*' => Http::response(['id' => 3, 'email' => 'member@example.com', 'status' => 'cancelled'])]);
        $this->post('/login', ['email' => 'member@example.com', 'password' => 'password']);
        $this->assertAuthenticated();
        $this->assertFalse($user->fresh()->hasQuizAccess());
    }

    public function test_api_outage_does_not_block_login(): void
    {
        User::factory()->create(['email' => 'member@example.com']);
        Http::fake(['kkp.test/*' => Http::response('down', 503)]);

        $this->post('/login', ['email' => 'member@example.com', 'password' => 'password']);

        $this->assertAuthenticated();
    }

    public function test_forgot_password_creates_the_account_for_a_member_so_the_reset_link_works(): void
    {
        Http::fake(['kkp.test/*' => Http::response(['id' => 4, 'email' => 'fresh@example.com', 'status' => 'active'])]);

        $this->post('/forgot-password', ['email' => 'fresh@example.com'])->assertSessionHas('status');

        $user = User::query()->where('email', 'fresh@example.com')->firstOrFail();
        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertNotSentTo($user, KingsleyKhordPianoInvitation::class);
    }

    public function test_sync_is_skipped_when_not_configured(): void
    {
        config(['services.kingsleykhordpiano.base_url' => null]);
        Http::fake();

        $this->artisan('kkp:sync')->assertFailed();
        $this->post('/login', ['email' => 'x@example.com', 'password' => 'y']);

        Http::assertNothingSent();
    }
}
