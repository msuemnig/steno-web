<?php

return [

    'plans' => [
        'individual' => [
            'name' => 'Individual',
            'price_monthly' => null,
            'price_yearly' => env('STRIPE_INDIVIDUAL_YEARLY_PRICE_ID', ''),
            'amount' => 5000, // $50/yr in cents
            'max_members' => 1,
            'max_scripts' => null, // unlimited
            'can_export' => true,
        ],
        'business' => [
            'name' => 'Business',
            'price_monthly' => null,
            'price_yearly' => env('STRIPE_BUSINESS_YEARLY_PRICE_ID', ''),
            'amount' => 25000, // $250/yr in cents
            'max_members' => 10,
            'max_scripts' => null, // unlimited
            'can_export' => true,
        ],
    ],

    'free_tier' => [
        'max_scripts' => 5,
        'can_export' => false,
    ],

];
