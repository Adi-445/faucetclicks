<!-- api/withdrawals.php -->
<?php
require_once '../config/keys.php';
require_once 'firebase_init.php';
require_once 'mailer.php';

header('Content-Type: application/json');
$keys = include('../config/keys.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user = validateToken();
    
    if (!$user) {
        http_response_code(401);
        exit;
    }
    
    $userDoc = $db->collection('users')->document($user['uid'])->snapshot();
    if (!$userDoc->exists()) {
        http_response_code(404);
        exit;
    }
    
    $userData = $userDoc->data();
    $cryptoValue = $userData['points'] / 1000 * 0.01; // 1000 points = $0.01
    
    // Check withdrawal threshold
    $minWithdrawal = $userData['has_referral'] ? 0.10 : 0.90;
    if ($cryptoValue < $minWithdrawal) {
        echo json_encode(['error' => "Minimum withdrawal is $$minWithdrawal"]);
        exit;
    }
    
    // Create withdrawal request
    $withdrawalId = 'wd_' . bin2hex(random_bytes(8));
    $db->collection('withdrawals')->document($withdrawalId)->set([
        'user_id' => $user['uid'],
        'amount' => $cryptoValue,
        'address' => $data['address'],
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Notify admin
    sendAdminNotification($user['uid'], $cryptoValue, $data['address']);
    
    echo json_encode([
        'success' => true,
        'withdrawal_id' => $withdrawalId,
        'status' => 'pending'
    ]);
}

function sendAdminNotification($userId, $amount, $address) {
    $keys = include('../config/keys.php');
    $subject = "New Withdrawal Request: \$$amount";
    $message = "User ID: $userId\nAmount: \$$amount\nAddress: $address\n\nPlease process this withdrawal.";
    
    sendEmail($keys['ADMIN_EMAIL'], $subject, $message);
}
?>
