<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            // The member's language: `locale` for formatting, `translations` for the
            // useTrans() hook (resources/js/lib/i18n.js). Keys are the English copy.
            'locale' => app()->getLocale(),
            'translations' => fn () => Locales::translations(app()->getLocale()),
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
        ];
    }
}
