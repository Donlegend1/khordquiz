<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Support\Plans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function plans(): JsonResponse
    {
        return response()->json([
            'trial_days' => Plans::TRIAL_DAYS,
            'plans' => array_values(Plans::all()),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'subscription' => Subscription::forUser($request->user())->toApi(),
        ]);
    }

    public function trial(Request $request): JsonResponse
    {
        $planId = $this->planId($request, ['yearly', 'monthly']);
        $subscription = Subscription::forUser($request->user());
        $subscription->fill([
            'access' => 'trial',
            'plan_id' => $planId,
            'status' => 'active',
            'provider' => $this->provider($request),
            'trial_cancelled' => false,
            'trial_ends_at' => now()->addDays(Plans::TRIAL_DAYS),
            'next_plan' => null,
        ])->save();

        return response()->json([
            'message' => 'Free trial started.',
            'subscription' => $subscription->fresh()->toApi(),
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $planId = $this->planId($request, ['monthly', 'yearly', 'lifetime']);
        /** @var User $user */
        $user = $request->user();
        $subscription = Subscription::forUser($user);
        $plan = Plans::all()[$planId];
        $now = now();

        $subscription->fill([
            'access' => 'paid',
            'plan_id' => $planId,
            'status' => 'active',
            'provider' => $this->provider($request),
            'next_plan' => null,
            'trial_cancelled' => false,
            'since' => $now->format('M Y'),
            'renews' => $planId === 'lifetime' ? null : Subscription::formatDate($now->copy()->addDays($planId === 'monthly' ? 30 : 365)),
        ])->save();

        SubscriptionPayment::query()->create([
            'user_id' => $user->id,
            'amount' => strtok($plan['price'], '/'),
            'plan' => $plan['tier'].' · '.$plan['label'],
            'status' => 'Paid',
            'paid_on' => Subscription::formatDate($now),
        ]);

        return response()->json([
            'message' => 'Plan updated. The store still has to confirm the charge.',
            'subscription' => $subscription->fresh()->toApi(),
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $subscription = Subscription::forUser($request->user());
        abort_if($subscription->access === 'none', 422, 'There is no plan to cancel.');

        if ($subscription->access === 'trial') {
            $subscription->update(['trial_cancelled' => true]);
        } else {
            $subscription->update(['status' => 'cancelling', 'next_plan' => null]);
        }

        return response()->json([
            'message' => 'Cancellation scheduled.',
            'subscription' => $subscription->fresh()->toApi(),
        ]);
    }

    public function resume(Request $request): JsonResponse
    {
        $subscription = Subscription::forUser($request->user());

        if ($subscription->access === 'trial') {
            $subscription->update(['trial_cancelled' => false]);
        } else {
            $subscription->update(['status' => 'active']);
        }

        return response()->json([
            'message' => 'Plan resumed.',
            'subscription' => $subscription->fresh()->toApi(),
        ]);
    }

    /**
     * @param  list<string>  $allowed
     */
    private function planId(Request $request, array $allowed): string
    {
        return $request->validate([
            'plan_id' => ['required', 'string', Rule::in($allowed)],
        ])['plan_id'];
    }

    private function provider(Request $request): string
    {
        $provider = $request->validate([
            'provider' => ['sometimes', 'string', Rule::in(['App Store', 'Google Play'])],
        ])['provider'] ?? null;

        return $provider ?? 'App Store';
    }
}
