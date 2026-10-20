<?php
return [
    'locales' => ['ar', 'fr', 'en'],
    'slickpay' => ['base' => env('SLICKPAY_BASE', 'https://devapi.slick-pay.com/api/v2'), 'key' => env('SLICKPAY_KEY'), 'account' => env('SLICKPAY_ACCOUNT'), 'secret' => env('SLICKPAY_WEBHOOK_SECRET', 'change-me')],
    'waha' => ['url' => env('WAHA_URL'), 'key' => env('WAHA_KEY'), 'session' => env('WAHA_SESSION', 'default')],
    'sms' => ['url' => env('SMS_URL'), 'token' => env('SMS_TOKEN'), 'from' => env('SMS_FROM', 'ILITECH')],
];
