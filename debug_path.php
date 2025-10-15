<?php
$requestPath = '/STI-DigiLibrary/server/public/config/frontend';
echo 'Original path: ' . $requestPath . PHP_EOL;

$requestPath = preg_replace('#^/STI-DigiLibrary/server/public#', '', $requestPath);
echo 'After first strip: "' . $requestPath . '"' . PHP_EOL;

$requestPath = preg_replace('#^/server/public#', '', $requestPath);
echo 'After second strip: "' . $requestPath . '"' . PHP_EOL;

echo PHP_EOL . 'Testing condition:' . PHP_EOL;
echo 'Path: "' . $requestPath . '"' . PHP_EOL;
echo 'Expected: "/config/frontend"' . PHP_EOL;
echo 'Method: GET' . PHP_EOL;
echo 'Match: ' . ($requestPath === '/config/frontend' ? 'YES' : 'NO') . PHP_EOL;
