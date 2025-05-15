<?php
require __DIR__ . '/vendor/autoload.php';

session_start(); // ✅ Start the session to store order info

$stripe_secret_key = "sk_test_51ROazW2cMTR3T0wZfisLw9zLi1OhY8VNHQ20Dy2VmUjPgJVfv1kdJHXETslWCBwTVc0LKOECYHzJFJ3lyiZ5vUUC00kbxfoNtE";
\Stripe\Stripe::setApiKey($stripe_secret_key);

// Read raw POST data (JSON)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$cart = $data['cart'] ?? null;
$email = $data['email'] ?? null;

if (!$cart || !is_array($cart)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid cart data']);
    exit;
}

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email']);
    exit;
}

// Calculate total
$total = array_reduce($cart, function ($sum, $item) {
    return $sum + ($item['price'] * $item['quantity']);
}, 0);

$order_id = uniqid("ORDER_");

// ✅ Store order in session
$_SESSION['order'] = [
    'order_id' => $order_id,
    'status' => 'pending',
    'email' => $email,
    'items' => $cart,
    'total' => $total,
    'payment_reference' => null,
    'dispatch_slip_status' => null,
    'email_sent' => false
];

// Build Stripe line items
$line_items = [];
foreach ($cart as $item) {
    $line_items[] = [
        'price_data' => [
            'currency' => 'php', // or 'usd'
            'product_data' => [
                'name' => $item['name'],
                'images' => [$item['image']] // optional
            ],
            'unit_amount' => intval($item['price'] * 100),
        ],
        'quantity' => intval($item['quantity']),
    ];
}

try {
    // Create Stripe checkout session
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'customer_email' => $email,
        'success_url' => 'http://localhost:8000/success.html',
        'cancel_url' => 'http://localhost:8000/shopping-cart.html',
    ]);

    // ✅ Store Stripe session ID
    $_SESSION['order']['payment_reference'] = $checkout_session->id;

    // Optional: Save order log to file
    $logData = $_SESSION['order'];
    $logData['timestamp'] = date('Y-m-d H:i:s');
    file_put_contents('orders.log', json_encode($logData) . PHP_EOL, FILE_APPEND);

    // Return Stripe session URL
    header('Content-Type: application/json');
    echo json_encode(['url' => $checkout_session->url]);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
