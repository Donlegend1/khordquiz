<?php

namespace App\Support;

class Plans
{
    public const TRIAL_DAYS = 7;

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'monthly' => [
                'id' => 'monthly',
                'tier' => 'Standard',
                'label' => 'Monthly',
                'price' => '$5.99/month',
                'subtitle' => 'Billed every month',
                'icon' => 'star',
                'icon_color' => '#CBD5E1',
            ],
            'yearly' => [
                'id' => 'yearly',
                'tier' => 'Premium',
                'label' => 'Yearly',
                'price' => '$49.99/year',
                'original_price' => '$70.99/year',
                'discount' => '30% OFF',
                'subtitle' => 'Billed every year',
                'icon' => 'diamond',
                'icon_color' => '#FFD54A',
            ],
            'lifetime' => [
                'id' => 'lifetime',
                'tier' => 'Premium',
                'label' => 'Lifetime',
                'price' => '$99.99',
                'subtitle' => 'Pay once, use forever',
                'icon' => 'diamond',
                'icon_color' => '#FFD54A',
            ],
        ];
    }
}
