<!-- api/auth.php -->
<?php
require_once '../config/keys.php';
require_once 'firebase_init.php';

header('Content-Type: application/json');
$keys = include('../config/keys.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        switch ($data['action']) {
            case 'login':
                $user = $auth->signInWithEmailAndPassword($data['email'], $data['password']);
                $idToken = $user->idToken();
                
                // Set secure HTTP-only cookie
                setcookie('faucet_token', $idToken, [
                    'expires' => time() + 86400,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
                
                echo json_encode(['success' => true, 'user' => $user->asArray()]);
                break;
                
            case 'google':
                // Google OAuth would be handled via Firebase SDK
                echo json_encode(['redirect' => $auth->createSignInWithGoogleUrl()]);
                break;
                
            case 'register':
                $user = $auth->createUserWithEmailAndPassword(
                    $data['email'], 
                    $data['password'],
                    ['displayName' => $data['name']]
                );
                
                // Apply new user bonus
                $db->collection('users')->document($user->uid())->set([
                    'points' => 50,
                    'multiplier' => 1.10,
                    'signup_date' => date('Y-m-d H:i:s'),
                    'referral_code' => generateReferralCode(),
                    'has_referral' => !empty($data['referral'])
                ]);
                
                echo json_encode(['success' => true]);
                break;
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function generateReferralCode() {
    return 'TEEN' . strtoupper(substr(md5(rand()), 0, 4));
}
?>
