<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\Billing\PaddleSubscriptionSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaddleBillingTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://sandbox-api.paddle.com';

    /** @var array<string, array<string, mixed>> */
    private array $subscriptions = [];

    /** @var array<string, array<string, mixed>> */
    private array $transactions = [];

    /** @var array<string, mixed> */
    private array $catalogPrice = ['trial_period' => null];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paddle.environment' => 'sandbox',
            'services.paddle.api_key' => 'pdl_test',
            'services.paddle.client_token' => 'test_client',
            'services.paddle.webhook_secret' => 'pdl_ntfset_secret',
            'services.paddle.prices' => ['monthly' => 'pri_monthly', 'yearly' => 'pri_yearly', 'lifetime' => 'pri_lifetime'],
            'services.stripe.secret' => null,
        ]);

        Http::preventStrayRequests();
        Http::fake(fn (Request $request) => $this->paddleApi($request));
    }

    public function test_checkout_creates_a_paddle_transaction_with_the_free_trial_for_first_time_members(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('billing.checkout', 'yearly'))
            ->assertRedirect(route('billing.pay', ['transaction' => 'txn_new']));

        $this->assertSame('ctm_test', Subscription::forUser($user)->paddle_customer_id);

        // The catalog price has no trial, so a copy with the 7-day trial is sent.
        $transaction = $this->sentTransaction();
        $this->assertSame(['interval' => 'day', 'frequency' => 7], $transaction['items'][0]['price']['trial_period']);
        $this->assertSame('pro_khordquiz', $transaction['items'][0]['price']['product_id']);
        $this->assertSame(['user_id' => (string) $user->id, 'plan_id' => 'yearly'], $transaction['custom_data']);
        $this->assertSame('ctm_test', $transaction['customer_id']);
    }

    public function test_no_second_trial_and_lifetime_uses_the_catalog_price(): void
    {
        $this->catalogPrice = ['trial_period' => ['interval' => 'day', 'frequency' => 7]];
        $user = User::factory()->create();
        Subscription::forUser($user)->update(['trial_used' => true, 'paddle_customer_id' => 'ctm_test']);

        $this->actingAs($user)->post(route('billing.checkout', 'monthly'));
        $monthly = $this->sentTransaction();
        $this->assertArrayNotHasKey('trial_period', $monthly['items'][0]['price']);
        $this->assertSame(['plan_id' => 'monthly'], $monthly['items'][0]['price']['custom_data']);

        $this->actingAs($user)->post(route('billing.checkout', 'lifetime'));
        $this->assertSame(['price_id' => 'pri_lifetime', 'quantity' => 1], $this->sentTransaction()['items'][0]);

        // The existing customer is reused rather than created again.
        Http::assertNotSent(fn (Request $request) => $request->method() === 'POST' && $request->url() === self::API.'/customers');
    }

    public function test_the_pay_page_opens_paddle_checkout_for_the_transaction(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('billing.pay', ['transaction' => 'txn_abc123']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Billing/PaddleCheckout')
                ->where('transactionId', 'txn_abc123')
                ->where('clientToken', 'test_client')
                ->where('sandbox', true));

        // Paddle's own payment links use ?_ptxn=.
        $this->actingAs(User::factory()->create())
            ->get(route('billing.pay', ['_ptxn' => 'txn_def456']))
            ->assertInertia(fn (Assert $page) => $page->where('transactionId', 'txn_def456'));

        $this->actingAs(User::factory()->create())->get(route('billing.pay', ['transaction' => '<script>']))->assertNotFound();
    }

    public function test_returning_from_checkout_activates_the_plan_immediately(): void
    {
        $user = User::factory()->create(['avatar' => 'note']);
        $this->subscriptions['sub_1'] = $this->paddleSubscription('sub_1', 'pri_yearly', 'trialing', now()->addDays(7));
        $this->transactions['txn_1'] = $this->paddleTransaction('txn_1', $user, 'pri_yearly', 0, ['subscription_id' => 'sub_1']);

        $this->actingAs($user)
            ->get(route('billing.success', ['transaction_id' => 'txn_1']))
            ->assertRedirect(route('subscription'));

        $subscription = Subscription::forUser($user);
        $this->assertSame('trial', $subscription->access);
        $this->assertSame('yearly', $subscription->plan_id);
        $this->assertSame(Subscription::PROVIDER_PADDLE, $subscription->provider);
        $this->assertSame('sub_1', $subscription->paddle_subscription_id);
        $this->assertTrue($subscription->trial_used);
        $this->assertTrue($user->fresh()->hasQuizAccess());
        $this->assertSame(0, SubscriptionPayment::query()->count());
    }

    public function test_someone_elses_transaction_is_rejected(): void
    {
        $this->transactions['txn_1'] = $this->paddleTransaction('txn_1', User::factory()->create(), 'pri_lifetime', 9999);

        $this->actingAs(User::factory()->create())
            ->get(route('billing.success', ['transaction_id' => 'txn_1']))
            ->assertForbidden();
    }

    public function test_webhook_rejects_bad_signatures_and_syncs_signed_events(): void
    {
        $user = User::factory()->create();
        Subscription::forUser($user)->update(['paddle_customer_id' => 'ctm_test']);

        $payload = json_encode([
            'event_id' => 'evt_1',
            'event_type' => 'subscription.updated',
            'data' => $this->paddleSubscription('sub_1', 'pri_monthly', 'active', now()->addMonth(), [
                'scheduled_change' => ['action' => 'cancel', 'effective_at' => now()->addMonth()->toIso8601String()],
            ]),
        ]);

        $this->postWebhook($payload, 'ts='.time().';h1=nope')->assertStatus(400);
        $this->postWebhook($payload, 'ts='.(time() - 3600).';h1='.hash_hmac('sha256', (time() - 3600).':'.$payload, 'pdl_ntfset_secret'))->assertStatus(400);

        $this->postWebhook($payload, $this->sign($payload), '/Paddle/webhook')->assertOk();

        $subscription = Subscription::forUser($user);
        $this->assertSame('paid', $subscription->access);
        $this->assertSame('monthly', $subscription->plan_id);
        $this->assertSame('cancelling', $subscription->status);
        $this->assertSame('sub_1', $subscription->paddle_subscription_id);
    }

    public function test_completed_transactions_are_added_to_billing_history_once(): void
    {
        $user = User::factory()->create();
        $this->subscriptions['sub_1'] = $this->paddleSubscription('sub_1', 'pri_yearly', 'active', now()->addYear());
        $payload = json_encode([
            'event_type' => 'transaction.completed',
            'data' => $this->paddleTransaction('txn_renew', $user, 'pri_yearly', 4999, ['subscription_id' => 'sub_1']),
        ]);

        $this->postWebhook($payload, $this->sign($payload))->assertOk();
        $this->postWebhook($payload, $this->sign($payload))->assertOk();

        $payments = SubscriptionPayment::query()->where('user_id', $user->id)->get();
        $this->assertCount(1, $payments);
        $this->assertSame('$49.99', $payments[0]->amount);
        $this->assertSame('Premium · Yearly', $payments[0]->plan);
        $this->assertSame('paid', Subscription::forUser($user)->access);
    }

    public function test_subscription_ending_removes_access(): void
    {
        $user = User::factory()->create();
        Subscription::forUser($user)->update(['access' => 'paid', 'plan_id' => 'monthly', 'status' => 'active', 'provider' => 'Paddle', 'paddle_customer_id' => 'ctm_test', 'paddle_subscription_id' => 'sub_1']);

        app(PaddleSubscriptionSync::class)->syncSubscription($this->paddleSubscription('sub_1', 'pri_monthly', 'canceled', null, ['canceled_at' => now()->toIso8601String()]));

        $this->assertSame('expired', Subscription::forUser($user)->status);
        $this->assertFalse($user->fresh()->hasQuizAccess());
    }

    public function test_cancel_resume_switch_and_portal(): void
    {
        $user = User::factory()->create();
        $periodEnd = now()->addMonth();
        Subscription::forUser($user)->update([
            'access' => 'paid', 'plan_id' => 'monthly', 'status' => 'active', 'provider' => 'Paddle',
            'paddle_customer_id' => 'ctm_test', 'paddle_subscription_id' => 'sub_1', 'current_period_end' => $periodEnd,
        ]);
        $this->subscriptions['sub_1'] = $this->paddleSubscription('sub_1', 'pri_monthly', 'active', $periodEnd);

        $this->actingAs($user)->post(route('billing.cancel'), ['reason' => 'Too expensive']);
        $this->assertSame('cancelling', Subscription::forUser($user)->status);
        Http::assertSent(fn (Request $request) => $request->url() === self::API.'/subscriptions/sub_1/cancel' && $request['effective_from'] === 'next_billing_period');

        $this->actingAs($user)->post(route('billing.resume'));
        $this->assertSame('active', Subscription::forUser($user)->status);

        // Switching is scheduled: still Monthly until the period rolls over.
        $this->actingAs($user)->post(route('billing.switch'), ['plan' => 'yearly']);
        $subscription = Subscription::forUser($user);
        $this->assertSame('monthly', $subscription->plan_id);
        $this->assertSame('yearly', $subscription->next_plan);
        Http::assertSent(fn (Request $request) => $request->method() === 'PATCH'
            && ($request['items'][0]['price_id'] ?? null) === 'pri_yearly'
            && $request['proration_billing_mode'] === 'do_not_bill');

        // Renewal: Paddle reports the next period on the new price.
        app(PaddleSubscriptionSync::class)->syncSubscription($this->paddleSubscription('sub_1', 'pri_yearly', 'active', now()->addYear()));
        $subscription->refresh();
        $this->assertSame('yearly', $subscription->plan_id);
        $this->assertNull($subscription->next_plan);

        $this->actingAs($user)
            ->post(route('billing.portal'), [], ['X-Inertia' => 'true'])
            ->assertHeader('X-Inertia-Location', 'https://customer-portal.paddle.test/overview');
    }

    public function test_lifetime_purchase_replaces_the_recurring_plan(): void
    {
        $user = User::factory()->create();
        Subscription::forUser($user)->update([
            'access' => 'paid', 'plan_id' => 'yearly', 'status' => 'active', 'provider' => 'Paddle',
            'paddle_customer_id' => 'ctm_test', 'paddle_subscription_id' => 'sub_1', 'current_period_end' => now()->addMonth(),
        ]);

        app(PaddleSubscriptionSync::class)->completeTransaction($this->paddleTransaction('txn_life', $user, 'pri_lifetime', 9999));

        $subscription = Subscription::forUser($user);
        $this->assertSame('lifetime', $subscription->plan_id);
        $this->assertNull($subscription->current_period_end);
        $this->assertNull($subscription->paddle_subscription_id);
        $this->assertSame('$99.99', SubscriptionPayment::query()->sole()->amount);
        Http::assertSent(fn (Request $request) => $request->url() === self::API.'/subscriptions/sub_1/cancel' && $request['effective_from'] === 'immediately');

        // The old subscription's cancellation event doesn't undo Lifetime.
        app(PaddleSubscriptionSync::class)->syncSubscription($this->paddleSubscription('sub_1', 'pri_yearly', 'canceled', null));
        $this->assertTrue($user->fresh()->hasQuizAccess());
    }

    public function test_deleting_an_account_cancels_its_paddle_billing(): void
    {
        $user = User::factory()->create();
        Subscription::forUser($user)->update(['provider' => 'Paddle', 'paddle_subscription_id' => 'sub_1', 'access' => 'paid']);

        $user->delete();

        Http::assertSent(fn (Request $request) => $request->url() === self::API.'/subscriptions/sub_1/cancel' && $request['effective_from'] === 'immediately');
    }

    public function test_signup_goes_straight_to_paddle_checkout(): void
    {
        $this->post('/signup', [
            'first_name' => 'Ada',
            'last_name' => 'N',
            'email' => 'ada@example.com',
            'password' => 'password',
            'terms' => true,
            'plan' => 'monthly',
            'payment_method' => 'paddle',
        ])->assertRedirect(route('billing.pay', ['transaction' => 'txn_new']));

        $this->assertSame('monthly', $this->sentTransaction()['custom_data']['plan_id']);
    }

    public function test_locally_every_plan_checks_out_with_the_test_price_but_keeps_its_plan(): void
    {
        config(['services.paddle.local_test_price' => 'pri_test_dollar']);
        $user = User::factory()->create();
        Subscription::forUser($user)->update(['paddle_customer_id' => 'ctm_test']);

        $sync = app(PaddleSubscriptionSync::class);

        // Not local (tests run as "testing"): the real prices are used.
        $this->assertSame(['price_id' => 'pri_lifetime', 'quantity' => 1], $sync->transactionItem('lifetime', false));

        $this->app['env'] = 'local';
        $this->assertSame(['price_id' => 'pri_test_dollar', 'quantity' => 1], $sync->transactionItem('lifetime', false));
        $this->assertSame('pri_test_dollar', $sync->priceFor('monthly'));

        $sync->completeTransaction(array_merge(
            $this->paddleTransaction('txn_test', $user, 'pri_test_dollar', 100),
            ['custom_data' => ['user_id' => (string) $user->id, 'plan_id' => 'lifetime']],
        ));

        $subscription = Subscription::forUser($user);
        $this->assertSame('lifetime', $subscription->plan_id);
        $this->assertSame('$1.00', SubscriptionPayment::query()->sole()->amount);
    }

    /**
     * A tiny stand-in for the Paddle API: just the endpoints billing uses.
     */
    private function paddleApi(Request $request): mixed
    {
        $path = substr($request->url(), strlen(self::API));
        $path = strtok($path, '?');

        return match (true) {
            $request->method() === 'POST' && $path === '/customers' => Http::response(['data' => ['id' => 'ctm_test']], 201),
            str_ends_with($path, '/portal-sessions') => Http::response(['data' => ['urls' => ['general' => ['overview' => 'https://customer-portal.paddle.test/overview']]]], 201),
            str_starts_with($path, '/prices/') => Http::response(['data' => array_merge([
                'id' => substr($path, 8),
                'product_id' => 'pro_khordquiz',
                'description' => 'KhordQuiz',
                'unit_price' => ['amount' => '4999', 'currency_code' => 'USD'],
                'billing_cycle' => ['interval' => 'year', 'frequency' => 1],
                'tax_mode' => 'account_setting',
            ], $this->catalogPrice)]),
            $request->method() === 'POST' && $path === '/transactions' => Http::response(['data' => ['id' => 'txn_new', 'status' => 'draft']], 201),
            str_starts_with($path, '/transactions/') => Http::response(['data' => $this->transactions[substr($path, 14)] ?? []]),
            str_ends_with($path, '/cancel') => Http::response(['data' => $this->updateSubscription(explode('/', $path)[2], ['scheduled_change' => ['action' => 'cancel']])]),
            $request->method() === 'PATCH' => Http::response(['data' => $this->updateSubscription(explode('/', $path)[2], $request->data())]),
            str_starts_with($path, '/subscriptions/') => Http::response(['data' => $this->subscriptions[substr($path, 15)] ?? []]),
            default => Http::response(['error' => ['code' => 'not_found']], 404),
        };
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function updateSubscription(string $id, array $changes): array
    {
        $subscription = $this->subscriptions[$id] ?? $this->paddleSubscription($id, 'pri_monthly', 'active', now()->addMonth());

        if (array_key_exists('scheduled_change', $changes)) {
            $subscription['scheduled_change'] = $changes['scheduled_change'];
        }

        if (isset($changes['items'])) {
            $subscription['items'][0]['price']['id'] = $changes['items'][0]['price_id'];
        }

        return $this->subscriptions[$id] = $subscription;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function paddleSubscription(string $id, string $priceId, string $status, ?Carbon $periodEnd, array $overrides = []): array
    {
        return array_merge([
            'id' => $id,
            'status' => $status,
            'customer_id' => 'ctm_test',
            'custom_data' => null,
            'scheduled_change' => null,
            'current_billing_period' => $periodEnd ? ['starts_at' => now()->toIso8601String(), 'ends_at' => $periodEnd->toIso8601String()] : null,
            'items' => [[
                'price' => ['id' => $priceId],
                'trial_dates' => $status === 'trialing' && $periodEnd ? ['starts_at' => now()->toIso8601String(), 'ends_at' => $periodEnd->toIso8601String()] : null,
            ]],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function paddleTransaction(string $id, User $user, string $priceId, int $totalCents, array $overrides = []): array
    {
        return array_merge([
            'id' => $id,
            'status' => 'completed',
            'customer_id' => 'ctm_test',
            'subscription_id' => null,
            'currency_code' => 'USD',
            'custom_data' => ['user_id' => (string) $user->id],
            'billed_at' => now()->toIso8601String(),
            'items' => [['price' => ['id' => $priceId]]],
            'details' => ['totals' => ['grand_total' => (string) $totalCents]],
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function sentTransaction(): array
    {
        $sent = Http::recorded(fn (Request $request) => $request->method() === 'POST' && $request->url() === self::API.'/transactions');

        return $sent->last()[0]->data();
    }

    private function sign(string $payload): string
    {
        $timestamp = time();

        return "ts={$timestamp};h1=".hash_hmac('sha256', "{$timestamp}:{$payload}", 'pdl_ntfset_secret');
    }

    private function postWebhook(string $payload, string $signature, string $path = '/paddle/webhook'): TestResponse
    {
        return $this->call('POST', $path, [], [], [], ['HTTP_PADDLE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload);
    }
}
