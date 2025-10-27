<?php
// app/routes/configRoutes.php
function handleConfigRoutes($path, $method)
{
    global $matched;

    if ($path === '/config/frontend') {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *'); // Restrict to your domain in production
        header('Access-Control-Allow-Methods: GET');
        header('Access-Control-Allow-Headers: Content-Type');

        $matched = true;

        // Detect if HTTP or HTTPS dynamically
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https'
            : 'http';

        // Updated: make /api part of the base URL
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/STI-DigiLibrary/public/api';

        $config = [
            'api' => [
                'baseUrl' => $baseUrl,
                'endpoints' => [
                    // Flattened - removed 'auth' nesting
                    'changePassword'       => '/auth/change-password',
                    'confirmResetPassword' => '/auth/confirm-reset-password',
                    'login'                => '/auth/login',
                    'resetPassword'        => '/auth/reset-password',
                    'signup'               => '/auth/signup',
                    'verify'               => '/auth/verify',
                    'verifyCode'           => '/auth/verify-code',
                    'verifyLockedCode'     => '/auth/verify-locked-code',
                    'sendLockedCode'       => '/auth/locked-code',
                    'books'                => '/books',
                    'theses'               => '/theses',
                    'users'                => '/users',
                    'catalog'              => '/catalog',
                    'libraryIds'           => '/library-ids',
                ],
            ],
            'recaptcha' => [
                'siteKey' => '6Ldr480rAAAAAFzjsARYcwQUgmlLcJ6SR1clGOsL'
            ],
            'meta' => [
                'appName'     => 'STI DigiLibrary',
                'version'     => '1.0.0',
                'environment' => 'development'
            ]
        ];

        echo json_encode($config, JSON_PRETTY_PRINT);
        return;
    }
}
