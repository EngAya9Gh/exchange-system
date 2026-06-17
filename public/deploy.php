<?php

// A secret token to secure the webhook
$secret = 'vcmoneytransfer_secure_webhook_12345';

// Check if the secret matches the one sent by GitHub
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (!$signature) {
    // Alternatively check a query parameter if manually triggered: ?token=xxx
    if (isset($_GET['token']) && $_GET['token'] === $secret) {
        // Continue
    } else {
        http_response_code(403);
        die('Forbidden');
    }
} else {
    // Validate GitHub signature
    $payload = file_get_contents('php://input');
    $hash = 'sha256=' . hash_hmac('sha256', $payload, $secret, false);
    if (!hash_equals($hash, $signature)) {
        http_response_code(403);
        die('Invalid signature');
    }
}

// Execute the deployment script
// The path must point to the deploy.sh file in the root directory
$output = shell_exec('cd .. && bash deploy.sh 2>&1');

// Output the result for logging
echo "<pre>$output</pre>";
