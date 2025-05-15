<?php
session_start();

// Update a single order entry in orders.log
function updateOrderLog($updatedOrder) {
    $logFile = 'orders.log';
    $orderId = $updatedOrder['order_id'];

    if (!file_exists($logFile)) return;

    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $newLines = [];
    $found = false;

    foreach ($lines as $line) {
        $order = json_decode($line, true);
        if (isset($order['order_id']) && $order['order_id'] === $orderId) {
            $newLines[] = json_encode($updatedOrder); // Replace matched order
            $found = true;
        } else {
            $newLines[] = $line;
        }
    }

    if (!$found) {
        // Optional: Add it if not found
        $newLines[] = json_encode($updatedOrder);
    }

    file_put_contents($logFile, implode(PHP_EOL, $newLines) . PHP_EOL);
}

// Update order in session and log
if (isset($_SESSION['order'])) {
    // Step 1: Mark order as accepted after successful Stripe payment
    $_SESSION['order']['status'] = 'accepted';

    // Step 2: Prepare for dispatch
    $_SESSION['order']['dispatch_slip_status'] = 'pending';

    // Step 3: Email has not been sent yet
    $_SESSION['order']['email_sent'] = false;

    // Add timestamp
    $_SESSION['order']['timestamp'] = date('Y-m-d H:i:s');

    // Save to log
    updateOrderLog($_SESSION['order']);
}
