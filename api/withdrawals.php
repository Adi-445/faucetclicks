<?php
require_once '../config/keys.php';
require_once 'firebase_init.php';
require_once 'mailer.php';

header('Content-Type: application/json');
$keys = include('../config/keys.php');
$user = validateToken();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $userDoc = $db->collection('users')->document($user['uid'])->snapshot();
        if (!$userDoc->exists()) {
            throw new Exception('User not found');
        }
        
        $userData = $userDoc->data();
        $points = $userData['points'] ?? 0;
        $cryptoValue = $points / 1000 * 0.01; // 1000 points = $0.01
        
        // Check withdrawal threshold
        $minWithdrawal = ($userData['has_referral'] ?? false) ? 0.10 : 0.90;
        if ($cryptoValue < $minWithdrawal) {
            throw new Exception("Minimum withdrawal is \$$minWithdrawal");
        }
        
        // Create withdrawal request
        $withdrawalId = 'wd_' . bin2hex(random_bytes(8));
        $withdrawalRef = $db->collection('withdrawals')->document($withdrawalId);
        $withdrawalRef->set([
            'user_id' => $user['uid'],
            'amount' => $cryptoValue,
            'address' => $data['address'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'completed_at' => null
        ]);
        
        // Send notification email to admin
        $subject = "FaucetClicks: New Withdrawal Request #{$withdrawalId}";
        $message = <<<EOT
New Withdrawal Request
        
User ID: {$user['uid']}
Username: {$user['displayName'] ?? 'N/A'}
Email: {$user['email']}
        
Amount: \${$cryptoValue}
Wallet Address: {$data['address']}
        
Status: Pending
        
Please process this withdrawal at your earliest convenience.
        
FaucetClicks Admin Panel
faucetclicks.infinityfree.me/admin
EOT;
        
        sendEmail($keys['ADMIN_EMAIL'], $subject, $message);
        
        // Update user's points (deduct after withdrawal is processed)
        // We don't deduct immediately to prevent abuse
        $db->collection('users')->document($user['uid'])->update([
            ['path' => 'last_withdrawal_request', 'value' => date('Y-m-d H:i:s')]
        ]);
        
        echo json_encode([
            'success' => true,
            'withdrawal_id' => $withdrawalId,
            'status' => 'pending',
            'message' => 'Withdrawal request submitted. Admin will process soon.'
        ]);
        
    } catch (Exception $e) {
        error_log("Withdrawal error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
