<!-- api/user.php -->
<?php
require_once '../config/keys.php';
require_once 'firebase_init.php';

header('Content-Type: application/json');
$user = validateToken();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $userDoc = $db->collection('users')->document($user['uid'])->snapshot();
    if (!$userDoc->exists()) {
        throw new Exception('User not found');
    }
    
    $userData = $userDoc->data();
    
    // Calculate crypto value
    $cryptoValue = $userData['points'] / 1000 * 0.01;
    
    // Check new user bonus
    $bonusActive = false;
    if (isset($userData['signup_date'])) {
        $signupDate = new DateTime($userData['signup_date']);
        $now = new DateTime();
        $diff = $now->diff($signupDate);
        $bonusActive = ($diff->h < 24);
    }
    
    echo json_encode([
        'uid' => $user['uid'],
        'name' => $user['displayName'] ?? 'User',
        'email' => $user['email'],
        'points' => $userData['points'],
        'crypto_value' => $cryptoValue,
        'has_referral' => $userData['has_referral'] ?? false,
        'referral_code' => $userData['referral_code'] ?? '',
        'bonus_active' => $bonusActive,
        'level' => floor($userData['points'] / 1000) + 1
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
