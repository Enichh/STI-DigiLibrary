<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust this in production to your frontend domain
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Only expose what's necessary for the frontend
$config = [
    'api' => [
        'baseUrl' => 'http://' . $_SERVER['HTTP_HOST'] . '/STI-DigiLibrary/server/public',
        'endpoints' => [
            'changePassword' => '/auth/change-password.php',
            'confirmResetPassword' => '/auth/confirm-reset-password.php',
            'issueAdminCode' => '/auth/issue-admin-code.php',
            'login' => '/auth/login.php',
            'resetPassword' => '/auth/reset-password.php',
            'sendLockedCode' => '/auth/locked-code.php',
            'signup' => '/auth/signup.php',
            'verify' => '/auth/verify.php',
            'verifyAdminCode' => '/auth/verify-admin-code.php',
            'verifyCode' => '/auth/verify-code.php',
            'verifyLockedCode' => '/auth/verify-locked-code.php',
            'books' => '/books.php',
        ],
        'users' => [
            'getUsers' => '/users.php'
        ]
    ],
    'recaptcha' => [
        'siteKey' => '6Ldr480rAAAAAFzjsARYcwQUgmlLcJ6SR1clGOsL'
    ]
];

// In a production environment, you might want to add authentication here
// to verify the request is coming from your frontend

echo json_encode($config);
