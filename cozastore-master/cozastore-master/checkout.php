<?php

require __DIR__ . '/vendor/autoload.php';

$stripe_secret_key = "sk_test_51ROazW2cMTR3T0wZfisLw9zLi1OhY8VNHQ20Dy2VmUjPgJVfv1kdJHXETslWCBwTVc0LKOECYHzJFJ3lyiZ5vUUC00kbxfoNtE";
\Stripe\Stripe::setApiKey($stripe_secret_key);

$checkout_session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'line_items' => [[
        'price_data' => [
            'currency' => 'php',
            'product_data' => [
                'name' => 'T-shirt',
                'images' => ['https://example.com/t-shirt.png'],
            ],
            'unit_amount' => 2000,
        ],
        'quantity' => 1,
    ]],
    'mode' => 'payment',
    'success_url' => 'https://example.com/success.html',
    'cancel_url' => 'https://example.com/cancel.html',
]);