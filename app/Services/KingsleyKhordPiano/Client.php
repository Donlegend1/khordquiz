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
 *   KKP_MEMBERS_PATH list endpoint, default /api/khord-quiz/members
 *   KKP_MEMBER_PATH  single lookup, default /api/khord-quiz/members/{email}
 *
 * Accepted response shapes — the list endpoint may return a bare array, or wrap
 * it in "data" / "members", and may paginate with any of next_page_url,
 * links.next, meta.next_page_url, next, or meta.current_page/last_page (?page=N).
 * Items may be flat, or Kingsleykhordpiano's {member, subscribed, subscription}
 * (see flatten()). The lookup returns the member object
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
     * Every member, one page (chunk) at a time — only one page is ever held in
     * memory. Page size comes from KKP_PER_PAGE.
     *
     * @return \Generator<int, list<array<string, mixed>>>
     */
    public function pages(): \Generator
    {
        $url = $this->url((string) config('services.kingsleykhordpiano.members_path'));
        $perPage = (int) config('services.kingsleykhordpiano.per_page', 100);

        if ($perPage > 0) {
            $url = $this->withQuery($url, ['per_page' => $perPage]);
        }

        for ($page = 0; $url !== null && $page < 1000; $page++) {
            $body = $this->request()->get($url)->throw()->json();

            $items = match (true) {
                is_array($body) && array_is_list($body) => $body,
                is_array($body['data'] ?? null) => $body['data'],
                is_array($body['members'] ?? null) => $body['members'],
                default => [],
            };

            logger()->info('Fetched Kingsleykhordpiano members page.', ['page' => $page + 1, 'url' => $url, 'count' => count($items)]);

            $members = [];
            foreach ($items as $item) {
                if (is_array($item) && ($member = $this->normalize($item, listed: true))) {
                    $members[] = $member;
                }
            }

            yield $members;

            $url = $this->nextPageUrl($body, $url);
        }
    }

    /**
     * Every member, following pagination.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    public function members(): \Generator
    {
        foreach ($this->pages() as $members) {
            yield from $members;
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
     * @return array{id: ?string, email: string, first_name: ?string, last_name: ?string, name: string, plan_id: string, expires_at: ?Carbon, active: bool, has_subscription: bool}|null
     */
    public function normalize(array $raw, bool $listed): ?array
    {
        if (is_array($raw['member'] ?? null)) {
            $raw = $this->flatten($raw);
        }

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
            'has_subscription' => (bool) ($raw['has_subscription'] ?? true),
        ];
    }

    /**
     * Kingsleykhordpiano's own shape:
     *   {member: {id, first_name, last_name, display_name, email, …},
     *    subscribed: bool,
     *    subscription: null | {status, type, provider, is_lifetime, started_at, expires_at,
     *                          plan: null | {id, tier, type, slug, is_premium}}}
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function flatten(array $raw): array
    {
        $subscription = is_array($raw['subscription'] ?? null) ? $raw['subscription'] : [];
        $plan = is_array($subscription['plan'] ?? null) ? $subscription['plan'] : [];
        $status = $subscription['status'] ?? null;
        $subscribed = $subscription !== [] && filter_var($raw['subscribed'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return array_merge($raw['member'], [
            'has_subscription' => $subscription !== [],
            // No status (e.g. a manual grant) counts as active while it hasn't
            // expired — normalize() then turns off anything past its expiry.
            'active' => $subscribed && (! is_string($status) || in_array(Str::lower($status), self::ACTIVE_STATUSES, true)),
            'plan' => filter_var($subscription['is_lifetime'] ?? false, FILTER_VALIDATE_BOOLEAN)
                ? 'lifetime'
                : ($subscription['type'] ?? $plan['type'] ?? null),
            'expires_at' => $subscription['expires_at'] ?? null,
        ]);
    }

    /**
     * Follows a next-page link if the response has one, otherwise
     * meta.current_page / last_page (or has_more) with a ?page= query.
     */
    private function nextPageUrl(mixed $body, string $url): ?string
    {
        if (! is_array($body) || array_is_list($body)) {
            return null;
        }

        $next = $body['next_page_url'] ?? $body['links']['next'] ?? $body['meta']['next_page_url'] ?? $body['next'] ?? null;

        if (is_string($next) && $next !== '') {
            return $this->url($next);
        }

        $meta = is_array($body['meta'] ?? null) ? $body['meta'] : [];
        $current = (int) ($meta['current_page'] ?? 0);
        $hasMore = isset($meta['last_page']) ? $current < (int) $meta['last_page'] : (bool) ($meta['has_more'] ?? false);

        if ($current < 1 || ! $hasMore) {
            return null;
        }

        return $this->withQuery($url, ['page' => $current + 1]);
    }

    /**
     * @param  array<string, scalar>  $params
     */
    private function withQuery(string $url, array $params): string
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return Str::before($url, '?').'?'.http_build_query(array_merge($query, $params));
    }

    private function planId(mixed $value): string
    {
        $value = Str::lower(is_scalar($value) ? (string) $value : '');

        return match (true) {
            str_contains($value, 'life') => 'lifetime',
            // KhordQuiz has no quarterly plan; access still ends at the real expiry date.
            str_contains($value, 'month') || str_contains($value, 'quarter') => 'monthly',
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
            $date = is_numeric($value) ? Carbon::createFromTimestamp((int) $value) : Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }

        // Outside what a DATETIME column can hold (e.g. 0000-00-00 or a
        // year-9999 "never") — treat as no usable date.
        return $date->year >= 1970 && $date->year < 9999 ? $date : null;
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
