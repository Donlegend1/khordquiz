<?php

namespace App\Services\KingsleyKhordPiano;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Reads subscribed members from the Kingsleykhordpiano API.
 *
 * Configure in .env (see config/services.php → kingsleykhordpiano):
 *   KKP_API_URL      e.g. https://kingsleykhordpiano.com
 *   KKP_API_TOKEN    sent as "Authorization: Bearer <token>"
 *   KKP_MEMBERS_PATH list endpoint, default /api/members
 *   KKP_MEMBER_PATH  single lookup, default /api/members/lookup?email={email}
 *
 * Accepted response shapes — the list endpoint may return a bare array, or wrap
 * it in "data" / "members", and may paginate with any of next_page_url,
 * links.next, meta.next_page_url or next. The lookup returns the member object
 * (bare, or under "data" / "member"), or 404 when the email isn't a member.
 *
 * Each member object is normalised by normalize(); recognised fields:
 *   id | member_id | user_id
 *   email | user_email
 *   first_name | firstname | given_name,  last_name | lastname | family_name,
 *   name | full_name | display_name
 *   active (bool) | status | subscription_status  ("active", "trialing", "paid"…)
 *   plan | plan_id | plan_name | membership | tier  ("monthly", "Annual", "Lifetime"…)
 *   expires_at | ends_at | expiry_date | expiration_date | renews_at |
 *   current_period_end | next_billing_date  (ISO date or unix timestamp)
 */
class Client
{
    private const ACTIVE_STATUSES = ['active', 'trialing', 'trial', 'paid', 'current', 'subscribed', 'complete', 'completed'];

    public function isConfigured(): bool
    {
        return filled(config('services.kingsleykhordpiano.base_url'));
    }

    /**
     * Every subscribed member, following pagination.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    public function members(): \Generator
    {
        $url = $this->url((string) config('services.kingsleykhordpiano.members_path'));

        for ($page = 0; $url !== null && $page < 1000; $page++) {
            $body = $this->request()->get($url)->throw()->json();

            $items = match (true) {
                is_array($body) && array_is_list($body) => $body,
                is_array($body['data'] ?? null) => $body['data'],
                is_array($body['members'] ?? null) => $body['members'],
                default => [],
            };

            foreach ($items as $item) {
                if (is_array($item) && ($member = $this->normalize($item, listed: true))) {
                    yield $member;
                }
            }

            $next = is_array($body) && ! array_is_list($body)
                ? ($body['next_page_url'] ?? $body['links']['next'] ?? $body['meta']['next_page_url'] ?? $body['next'] ?? null)
                : null;
            $url = is_string($next) && $next !== '' ? $this->url($next) : null;
        }
    }

    /**
     * One member by email, or null if they aren't a Kingsleykhordpiano member.
     *
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $path = str_replace('{email}', rawurlencode(Str::lower($email)), (string) config('services.kingsleykhordpiano.member_path'));
        $response = $this->request()->get($this->url($path));

        if ($response->notFound()) {
            return null;
        }

        $body = $response->throw()->json();
        $item = $body['data'] ?? $body['member'] ?? $body;

        // Some lookup endpoints answer with a one-item list.
        if (is_array($item) && array_is_list($item)) {
            $item = $item[0] ?? null;
        }

        $member = is_array($item) ? $this->normalize($item, listed: false) : null;

        return $member && Str::lower($member['email']) === Str::lower($email) ? $member : null;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{id: ?string, email: string, first_name: ?string, last_name: ?string, name: string, plan_id: string, expires_at: ?Carbon, active: bool}|null
     */
    public function normalize(array $raw, bool $listed): ?array
    {
        $email = $raw['email'] ?? $raw['user_email'] ?? null;

        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $first = $raw['first_name'] ?? $raw['firstname'] ?? $raw['given_name'] ?? null;
        $last = $raw['last_name'] ?? $raw['lastname'] ?? $raw['family_name'] ?? null;
        $name = $raw['name'] ?? $raw['full_name'] ?? $raw['display_name'] ?? trim(($first ?? '').' '.($last ?? ''));
        $plan = $this->planId($raw['plan'] ?? $raw['plan_id'] ?? $raw['plan_name'] ?? $raw['membership'] ?? $raw['tier'] ?? null);
        $expiresAt = $plan === 'lifetime' ? null : $this->date(
            $raw['expires_at'] ?? $raw['ends_at'] ?? $raw['expiry_date'] ?? $raw['expiration_date']
                ?? $raw['renews_at'] ?? $raw['current_period_end'] ?? $raw['next_billing_date'] ?? null
        );

        if (array_key_exists('active', $raw)) {
            $active = filter_var($raw['active'], FILTER_VALIDATE_BOOLEAN);
        } elseif (is_string($status = $raw['status'] ?? $raw['subscription_status'] ?? null)) {
            $active = in_array(Str::lower($status), self::ACTIVE_STATUSES, true);
        } else {
            // No status field: the list endpoint only returns subscribers; a
            // lookup counts as active while the expiry (if any) hasn't passed.
            $active = $listed || $expiresAt === null || $expiresAt->isFuture();
        }

        if ($active && $expiresAt !== null && $expiresAt->isPast()) {
            $active = false;
        }

        $id = $raw['id'] ?? $raw['member_id'] ?? $raw['user_id'] ?? null;

        return [
            'id' => $id !== null ? (string) $id : null,
            'email' => Str::lower(trim($email)),
            'first_name' => is_string($first) && $first !== '' ? $first : null,
            'last_name' => is_string($last) && $last !== '' ? $last : null,
            'name' => is_string($name) && trim($name) !== '' ? trim($name) : Str::before($email, '@'),
            'plan_id' => $plan,
            'expires_at' => $expiresAt,
            'active' => $active,
        ];
    }

    private function planId(mixed $value): string
    {
        $value = Str::lower(is_scalar($value) ? (string) $value : '');

        return match (true) {
            str_contains($value, 'life') => 'lifetime',
            str_contains($value, 'month') => 'monthly',
            str_contains($value, 'year') || str_contains($value, 'annual') => 'yearly',
            default => (string) config('services.kingsleykhordpiano.default_plan', 'yearly'),
        };
    }

    private function date(mixed $value): ?Carbon
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        try {
            return is_numeric($value) ? Carbon::createFromTimestamp((int) $value) : Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function request(): PendingRequest
    {
        $request = Http::acceptJson()
            ->timeout((int) config('services.kingsleykhordpiano.timeout', 8))
            ->retry(2, 250, throw: false);

        $token = config('services.kingsleykhordpiano.token');

        return filled($token) ? $request->withToken((string) $token) : $request;
    }

    private function url(string $pathOrUrl): string
    {
        if (Str::startsWith($pathOrUrl, ['http://', 'https://'])) {
            return $pathOrUrl;
        }

        return rtrim((string) config('services.kingsleykhordpiano.base_url'), '/').'/'.ltrim($pathOrUrl, '/');
    }
}
