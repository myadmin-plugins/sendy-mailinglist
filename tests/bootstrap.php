<?php

declare(strict_types=1);

// Autoloader
$autoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

// Define constants the plugin depends on (only if not already defined)
if (!defined('SENDY_ENABLE')) {
    define('SENDY_ENABLE', '1');
}
if (!defined('SENDY_API_KEY')) {
    define('SENDY_API_KEY', 'test-api-key');
}
if (!defined('SENDY_LIST_ID')) {
    define('SENDY_LIST_ID', 'test-list-id');
}
if (!defined('SENDY_APIURL')) {
    define('SENDY_APIURL', 'https://sendy.example.com');
}
if (!defined('STATISTICS_SERVER')) {
    define('STATISTICS_SERVER', 'localhost');
}

// Stub the global functions the plugin calls
if (!function_exists('myadmin_log')) {
    /**
     * @param string $module
     * @param string $level
     * @param string $message
     * @param int    $line
     * @param string $file
     */
    function myadmin_log(string $module, string $level, string $message, int $line, string $file): void
    {
        // no-op for tests
    }
}

if (!function_exists('_')) {
    /**
     * Gettext stub for environments where ext-gettext is absent.
     *
     * @param string $text
     * @return string
     */
    function _(string $text): string
    {
        return $text;
    }
}

// Pre-load StatisticClient before Plugin's require_once triggers.
// Plugin.php does require_once __DIR__.'/../../../workerman/...' which resolves
// relative to src/, reaching into the parent project's vendor tree.
// We pre-load the same file here so the require_once in Plugin.php is a no-op.
$realStatisticClient = __DIR__ . '/../src/../../../workerman/statistics/Applications/Statistics/Clients/StatisticClient.php';
if (!class_exists('StatisticClient', false) && file_exists($realStatisticClient)) {
    require_once $realStatisticClient;
}
if (!class_exists('StatisticClient', false)) {
    class StatisticClient
    {
        /**
         * @param string $module
         * @param string $action
         */
        public static function tick(string $module, string $action): void
        {
        }

        /**
         * @param string $module
         * @param string $action
         * @param bool   $success
         * @param int    $code
         * @param string $message
         * @param string $server
         */
        public static function report(string $module, string $action, bool $success, int $code, string $message, string $server): void
        {
        }
    }
}
