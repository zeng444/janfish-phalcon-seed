<?php
error_reporting(E_ALL);
//error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);  //dev
ini_set('display_errors', '0');
ini_set('log_errors', '1');

return new \Phalcon\Config([
    'version' => '0.1',
    'logger' => [
        'file' => LOG_PATH.'debug.log',
    ],
    'modelMiddleware' => [
        'host' => '@@QUEUE_SERVER@@',
        'port' => '@@QUEUE_PORT@@',
        'tube' => '@@INSURANCE_MIDDLEWARE_QUEUE@@',
        'workerNum' => 2,
        'daemonize' => false,
        'logPath' => LOG_PATH.'async_task.log',
    ],
    'queue' => [
        "host" => "@@QUEUE_SERVER@@",
        "port" => "@@QUEUE_PORT@@",
    ],
    'redis' => [
        'host' => '@@REDIS_SERVER@@',
        'port' => '@@REDIS_PORT@@',
        'scheme' => 'tcp',
        'database' => 12, //固定存储
    ],
    'cache' => [
        'host' => '@@REDIS_SERVER@@',
        //        'prefix' => '',
        'port' => '@@REDIS_PORT@@',
        'persistent' => false,
        //      'auth'=>'root',
        'index' => 21, //缓存
        'lifetime' => 172800,
    ],
    'database' => [
        'adapter' => 'Mysql',
        'host' => '@@DB_SERVER@@',
        'username' => '@@DB_SERVER_USERNAME@@',
        'password' => '@@DB_SERVER_PASSWORD@@',
        'dbname' => 'stock.game',
        'charset' => 'utf8',
        'options' => [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
        ],
    ],
    'platform_service' => [
        'baseUrl' => '@@PLATFORM_SERVICE_URL@@',
    ],
    'application' => [
        'name' => 'YOU_APPLICATION_NAME',
        'uploadFileDir' => ROOT_PATH.'api/public/files/',
        'modelsDir' => CORE_PATH.'models/',
        'frontendUrl' => 'http://YOUR_LOCAL_DOMAIN/',
        'baseUrl' => 'http://YOUR_LOCAL_DOMAIN/',
        'imagePrefix' => 'http://YOUR_LOCAL_DOMAIN/files/',
    ],
]);
