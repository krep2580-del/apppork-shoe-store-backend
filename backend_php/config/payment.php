<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for order payment hold duration, retry duration upon slip
    | rejection, PromptPay credentials, and bank account information.
    | Always access these values via config('payment.xxx') throughout the app.
    |
    */

    'hold_minutes' => (int) env('PAYMENT_HOLD_MINUTES', 1440),
    'retry_minutes' => (int) env('PAYMENT_RETRY_MINUTES', 720),

    'promptpay_id' => env('PROMPTPAY_ID', ''),
    'payee_name' => env('PAYEE_NAME', ''),
    'bank_name' => env('BANK_NAME', ''),
    'bank_account_no' => env('BANK_ACCOUNT_NO', ''),
];
