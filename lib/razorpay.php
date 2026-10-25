<?php
/**
 * Minimal Razorpay integration — hand-rolled cURL calls instead of the
 * official SDK, matching this project's no-package-manager style.
 */

$__razorpayLocalConfig = __DIR__ . '/../config-razorpay.local.php';
if (file_exists($__razorpayLocalConfig)) {
    require_once $__razorpayLocalConfig;
}
unset($__razorpayLocalConfig);

define('RAZORPAY_ENABLED',
    defined('RAZORPAY_KEY_ID') && RAZORPAY_KEY_ID !== ''
    && defined('RAZORPAY_KEY_SECRET') && RAZORPAY_KEY_SECRET !== ''
);

/**
 * Creates a Razorpay order. Returns the decoded order (contains 'id') or
 * null on failure.
 */
function razorpayCreateOrder(int $amountPaise, string $receipt): ?array {
    if (!RAZORPAY_ENABLED) return null;

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_USERPWD        => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode([
            'amount'   => $amountPaise,
            'currency' => 'INR',
            'receipt'  => $receipt,
        ]),
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        error_log('razorpayCreateOrder failed: HTTP ' . $httpCode . ' ' . $error . ' ' . $response);
        return null;
    }

    $order = json_decode($response, true);
    return isset($order['id']) ? $order : null;
}

/**
 * Verifies the signature Razorpay Checkout.js returns to the browser after
 * a successful payment (order_id|payment_id signed with the key secret).
 */
function razorpayVerifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool {
    if (!RAZORPAY_ENABLED) return false;
    $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, RAZORPAY_KEY_SECRET);
    return hash_equals($expected, $signature);
}

/**
 * Verifies the X-Razorpay-Signature header on incoming webhook requests,
 * signed over the raw request body with the webhook secret (separate from
 * the API key secret).
 */
function razorpayVerifyWebhookSignature(string $body, string $signature): bool {
    if (!defined('RAZORPAY_WEBHOOK_SECRET') || RAZORPAY_WEBHOOK_SECRET === '') return false;
    $expected = hash_hmac('sha256', $body, RAZORPAY_WEBHOOK_SECRET);
    return hash_equals($expected, $signature);
}
