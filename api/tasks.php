<?php
require_once '../config/keys.php';
require_once 'firebase_init.php';

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
        $earnedPoints = 0;
        
        // Check new user bonus
        $multiplier = 1.0;
        if (isset($userData['signup_date'])) {
            $signupDate = new DateTime($userData['signup_date']);
            $now = new DateTime();
            $diff = $now->diff($signupDate);
            if ($diff->h < 24) {
                $multiplier = 1.10;
            }
        }
        
        // Process task based on type
        switch ($data['task_type']) {
            case 'bitcotasks':
                // Verify with BitcoTasks API
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://api.bitcotasks.com/v1/tasks/{$data['task_id']}/verify");
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $keys['BITCO_TASKS_API_KEY'],
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $result = json_decode($response, true);
                    $basePoints = $result['points'] ?? 0;
                    $earnedPoints = $basePoints * $multiplier;
                } else {
                    throw new Exception('Task verification failed with BitcoTasks');
                }
                break;
                
            case 'timewall':
                // Verify with Timewall API
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://api.timewall.io/v2/sessions/{$data['session_id']}");
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'X-API-Key: ' . $keys['TIMEWALL_API_KEY']
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $result = json_decode($response, true);
                    $timeSpent = $result['time_spent'] ?? 0;
                    $basePoints = $timeSpent * 0.1; // 0.1 points per second
                    $earnedPoints = $basePoints * $multiplier;
                } else {
                    throw new Exception('Task verification failed with Timewall');
                }
                break;
                
            default:
                throw new Exception('Invalid task type');
        }
        
        // Update user points
        $newPoints = ($userData['points'] ?? 0) + $earnedPoints;
        $db->collection('users')->document($user['uid'])->update([
            ['path' => 'points', 'value' => $newPoints],
            ['path' => 'last_activity', 'value' => date('Y-m-d H:i:s')]
        ]);
        
        // Log activity
        $activityRef = $db->collection('activities')->newDocument();
        $activityRef->set([
            'user_id' => $user['uid'],
            'type' => 'task_completion',
            'task_type' => $data['task_type'],
            'points_earned' => $earnedPoints,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'points_earned' => $earnedPoints,
            'total_points' => $newPoints,
            'multiplier' => $multiplier
        ]);
        
    } catch (Exception $e) {
        error_log("Task error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
