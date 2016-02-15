<?php
/**
 * Simple PHP Github webhook deploy script
 *
 * Automatically deploy the code using PHP and Github.
 *
 * @link   https://github.com/emretekince/simple-php-github-webhook-deploy
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Init vars
$LIVE = "/var/www/LIVE";
$USER = 'username';
$PASS = 'password';
$REMOTE_REPO = "https://{$USER}:{$PASS}@github.com/{$USER}/yourrepo.git";
$SECRET_KEY = 'yoursecret';

$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'];
$event = $_SERVER['HTTP_X_GITHUB_EVENT'];
$delivery = $_SERVER['HTTP_X_GITHUB_DELIVERY'];

if (!isset($signature, $event, $delivery)) {
    echo 'Undefined payloads';
    return false;
}

$payload = file_get_contents('php://input');

if (!validateSignature($signature, $payload, $SECRET_KEY)) {
    echo 'Invalid signature';
    return false;
}

if (file_exists($LIVE . '/.git')) {
    // If there is already a repo, just run a git pull to grab the latest changes
    $res = shell_exec("cd {$LIVE} && git pull");
} else {
    // If the repo does not exist, then clone it into the parent directory
    $res = shell_exec("cd {$LIVE} && git clone {$REMOTE_REPO} .");
}

print_r($res);

die("done " . time());

function validateSignature($gitHubSignatureHeader, $payload, $SECRET_KEY)
{
    list ($algo, $gitHubSignature) = explode("=", $gitHubSignatureHeader);
    if ($algo !== 'sha1') {
        // see https://developer.github.com/webhooks/securing/
        return false;
    }
    $payloadHash = hash_hmac($algo, $payload, $SECRET_KEY);
    return ($payloadHash === $gitHubSignature);
}