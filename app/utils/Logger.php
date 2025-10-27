<?php

namespace App\Utils;

class Logger
{
    private static $logLevels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'NONE' => 4
    ];

    private static $currentLevel = 'DEBUG';

    public static function setLogLevel($level)
    {
        if (array_key_exists(strtoupper($level), self::$logLevels)) {
            self::$currentLevel = strtoupper($level);
        }
    }

    public static function debug($message, array $context = [])
    {
        self::log('DEBUG', $message, $context);
    }

    public static function info($message, array $context = [])
    {
        self::log('INFO', $message, $context);
    }

    public static function warning($message, array $context = [])
    {
        self::log('WARNING', $message, $context);
    }

    public static function error($message, array $context = [])
    {
        self::log('ERROR', $message, $context);
    }

    private static function log($level, $message, array $context = [])
    {
        if (self::$logLevels[$level] < self::$logLevels[self::$currentLevel]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_PRETTY_PRINT) : '';
        $logMessage = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;

        // Log to file
        $logDir = dirname(dirname(__DIR__)) . '/logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/app.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        // Also log to PHP error log
        error_log($logMessage);
    }
}
