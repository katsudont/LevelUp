<?php
session_start();

// Function to update a specific order in the log
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
            $newLines[] = json_encode($updatedOrder); // Replace with updated order
            $found = true;
        } else {
            $newLines[] = $line;
        }
    }

    if (!$found) {
        $newLines[] = json_encode($updatedOrder); // Fallback: add if not found
    }

    file_put_contents($logFile, implode(PHP_EOL, $newLines) . PHP_EOL);
}

if (isset($_SESSION['order'])) {
    // 1. Update order status and email flag
    $_SESSION['order']['status'] = 'shipped';
    $_SESSION['order']['email_sent'] = true;

    // 2. Build plain text item list for email use
    $itemsList = "";
    foreach ($_SESSION['order']['items'] as $item) {
        $itemsList .= "- {$item['name']} ×{$item['quantity']} (₱{$item['price']})\n";
    }
    $_SESSION['order']['items_list'] = trim($itemsList);

    // 3. Timestamp
    $_SESSION['order']['timestamp'] = date('Y-m-d H:i:s');

    // 4. Save to log
    updateOrderLog($_SESSION['order']);

    // 5. Send data to Activepieces webhook
    $activepiecesWebhook = 'https://cloud.activepieces.com/api/v1/webhooks/mpyoP2rPPK6p8NdsBMcxT'; // Replace with your real URL
    file_get_contents($activepiecesWebhook, false, stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/json',
            'content' => json_encode($_SESSION['order']),
        ]
    ]));

    // 6. Redirect to order status page
    header("Location: orders.php");
    exit;
}
?>
