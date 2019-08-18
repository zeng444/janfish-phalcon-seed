<?php
define('ROOT_PATH', dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR);
define('CLIENT_PATH', ROOT_PATH.'cli'.DIRECTORY_SEPARATOR);
define('CORE_PATH', ROOT_PATH.'core'.DIRECTORY_SEPARATOR);
define('LOG_PATH', ROOT_PATH.'logs'.DIRECTORY_SEPARATOR);

use Phalcon\Di\FactoryDefault\Cli as CliDi;
use Phalcon\Cli\Console as ConsoleApp;

$env = isset($_ENV['SITE_ENV']) ? strtolower($_ENV['SITE_ENV']) : 'prod';

/**
 * Read the configuration
 */

if ($env === 'dev') {
    $config = include ROOT_PATH."configs/dev.php";
} else {
    $config = include ROOT_PATH."configs/config.php";
}

/**
 * Autoload Object
 */
include_once CORE_PATH.'vendor/autoload.php';

/**
 * Read auto-loader
 */
require __DIR__.'/config/loader.php';

/**
 * Read the services
 */
$di = new CliDi();
require ROOT_PATH.'configs/services.php';

/**
 * Create a console application
 */
$console = new ConsoleApp($di);

/**
 * Process the console arguments
 */
$arguments = [];

foreach ($argv as $k => $arg) {
    if ($k == 1) {
        $arguments['task'] = $arg;
    } elseif ($k == 2) {
        $arguments['action'] = $arg;
    } elseif ($k >= 3) {
        $arguments['params'][] = $arg;
    }
}

try {

    /**
     * Handle
     */
    $console->handle($arguments);
} catch (Exception $e) {
    echo $e->getMessage().PHP_EOL;
    echo implode(PHP_EOL, $e->getTrace());
    exit(255);
}
