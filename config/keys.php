<!-- config/keys.php -->
<?php
// SECURE API KEY STORAGE - NEVER EXPOSE TO FRONTEND
return [
    'BITCO_TASKS_API_KEY' => 'YOUR_BITCO_TASKS_KEY', // Replace with actual key
    'TIMEWALL_API_KEY' => 'YOUR_TIMEWALL_KEY',       // Replace with actual key
    'SMTP_API_KEY' => 'YOUR_SMTP_KEY',               // Replace with actual key
    'ADMIN_EMAIL' => 'admin@faucetclicks.infinityfree.me',
    'JWT_SECRET' => 'YOUR_STRONG_JWT_SECRET',        // Must be 32+ characters
    'FIREBASE_CONFIG' => [
        'apiKey' => 'YOUR_FIREBASE_API_KEY',
        'authDomain' => 'your-project.firebaseapp.com',
        'projectId' => 'your-project'
    ]
];
?>
