<!-- api/tasks.php -->
<?php
require_once '../config/keys.php';
require_once 'firebase_init.php';

header('Content-Type: application/json');
$keys = include('../config/keys.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user = validateToken();
    
    if (!$user) {
        http_response_code(401);
        exit;
    }
    
    try {
        $points = 0;
        $multiplier = 1.0;
        
        // Check new user bonus
        $userDoc = $db->collection('users')->document($user['uid'])->snapshot();
        if ($userDoc->exists()) {
            $userData = $userDoc->data();
            $signupDate = new DateTime($userData['signup_date']);
            $now = new DateTime();
            $diff = $now->diff($signupDate);
            
            if ($diff->h < 24) {
                $multiplier = $userData['multiplier'] ?? 1.10;
            }
        }
        
        // Process task
        switch ($data['task_type']) {
            case 'bitcotasks':
                $points = calculateBitcoPoints($data['task_id'], $keys['BITCO_TASKS_API_KEY']);
                break;
                
            case 'timewall':
                $points = calculateTimewallPoints($data['session_id'], $keys['TIMEWALL_API_KEY']);
                break;
        }
        
        $earned = $points * $multiplier;
        
        // Update points
        $db->collection('users')->document($user['uid'])->update([
            ['path' => 'points', 'value' => FieldValue::increment($earned)]
        ]);
        
        echo json_encode([
            'success' => true,
            'points_earned' => $earned,
            'total_points' => $userData['points'] + $earned
        ]);
        
    } catch (Exception $e) {
        error_log("Task error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['error' => 'Task verification failed']);
    }
}

function calculateBitcoPoints($taskId, $apiKey) {
    // Actual BitcoTasks API integration
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.bitcotasks.com/v1/tasks/$taskId/verify");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        return $result['points'] ?? 0;
    }
    return 0;
}

function calculateTimewallPoints($sessionId, $apiKey) {
    // Actual Timewall API integration
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.timewall.io/v2/sessions/$sessionId");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        return $result['time_spent'] * 0.1; // 0.1 points per second
    }
    return 0;
}
?>
