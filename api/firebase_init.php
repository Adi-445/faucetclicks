<!-- api/firebase_init.php -->
<?php
require 'vendor/autoload.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;

$keys = include('../config/keys.php');
$firebaseConfig = $keys['FIREBASE_CONFIG'];

$factory = (new Factory)
    ->withServiceAccount($firebaseConfig['apiKey'])
    ->withDatabaseUri('https://' . $firebaseConfig['projectId'] . '.firebaseio.com');

$auth = $factory->createAuth();
$db = $factory->createDatabase();

function validateToken() {
    if (!isset($_COOKIE['faucet_token'])) {
        return null;
    }
    
    global $auth;
    try {
        $verifiedIdToken = $auth->verifyIdToken($_COOKIE['faucet_token']);
        return $verifiedIdToken->claims()->get('sub');
    } catch (Exception $e) {
        return null;
    }
}
?>
