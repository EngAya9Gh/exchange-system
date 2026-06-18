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

// Execute the deployment script in the background to prevent GitHub timeout
shell_exec('cd .. && nohup bash deploy.sh > deploy.log 2>&1 &');

// Output a success message immediately
echo "Deployment started in the background.";
