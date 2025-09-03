<?php
require_once '../config/keys.php';

function sendEmail($to, $subject, $text) {
    $keys = include('../config/keys.php');
    
    $apiKey = $keys['SMTP_API_KEY'];
    
    $data = [
        'sender' => [
            'name' => 'FaucetClicks Admin',
            'email' => 'no-reply@faucetclicks.infinityfree.me'
        ],
        'to' => [
            [
                'email' => $to,
                'name' => 'Admin'
            ]
        ],
        'subject' => $subject,
        'textContent' => $text,
        'htmlContent' => "<p>" . nl2br(htmlspecialchars($text)) . "</p>"
    ];
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'api-key: ' . $apiKey,
        'content-type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        error_log("Email send failed ({$httpCode}): " . $response);
        return false;
    }
    
    return true;
}
?>
