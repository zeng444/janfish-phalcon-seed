<?php
/**
 * Services are globally registered in this file
 *
 * @var \Phalcon\Config $config
 */

use Application\Core\Components\Internet\Http\Response as HttpResponse;
use Phalcon\Cache\Frontend\Data as FrontData;

//use Phalcon\Logger;
use Phalcon\Cache\Backend\Redis as BackendCache;
use Phalcon\Logger\Adapter\File as FileAdapter;
use Janfish\Phalcon\AsyncCaller\Client as AsyncCallerClient;
use Phalcon\Queue\Beanstalk;
use Phalcon\Mvc\Model\MetaData\Redis as MetaDataCache;

/**
 * Sets the config
 */
$di->setShared('config', function () use ($config) {
    return $config;
});

/**
 * 环境变量设置
 */
$di->setShared("env", function () {
    return (isset($_SERVER['SITE_ENV']) && $_SERVER['SITE_ENV']) ? strtolower($_SERVER['SITE_ENV']) : 'prod';
});


/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('db', function () use ($config, $di) {
    $dbConfig = $config->database->toArray();
    $adapter = $dbConfig['adapter'];
    unset($dbConfig['adapter']);
    $class = 'Phalcon\Db\Adapter\Pdo\\'.$adapter;
    $db = new $class($dbConfig);
    return $db;
});

/**
 *
 */
$di->setShared('apiResponse', function () {
    return new HttpResponse();
});


/**
 * redis服务
 */
if (isset($config->redis)) {
    $di->setShared('redis', function () use ($config) {
        return new Predis\Client($config->redis->toArray());
    });
    /**
     * 数据缓存
     * Author:Robert
     *
     * @return MetaDataCache
     */
    if ($di->get('env') !== 'dev') {
        $di['modelsMetadata'] = function () use ($config) {
            $redisConfig = $config->redis->toArray();
            $redisConfig['statsKey'] = '_PHCM_MM_SEED_'.$config->database->dbname;
            $redisConfig['lifetime'] = 86400;
            $redisConfig['index'] = 5;
            $metadata = new MetaDataCache($redisConfig);
            return $metadata;
        };
    }
}


$di->set('captcha', [
    'className' => 'Application\Core\Components\Security\Captcha',
]);

/**
 * cache服务
 */
if (isset($config->cache)) {
    $di->set('cache', function () use ($config) {
        $frontCache = new FrontData(["lifetime" => $config->cache->lifetime]);
        return new BackendCache($frontCache, $config->cache->toArray());
    });
}


/**
 * 注入用户OAuth验证服务
 */
$di->setShared('OAuth', [
    'className' => 'Application\Core\Components\Internet\Http\OAuth2',
]);


/**
 * 日志服务
 */
if (isset($config->logger)) {
    $di->setShared('logger', function () use ($config) {
        return new FileAdapter($config->logger->file);
        /*$logger->critical("This is a critical message");
        $logger->emergency("This is an emergency message");
        $logger->debug("This is a debug message");
        $logger->error("This is an error message");
        $logger->info("This is an info message");
        $logger->notice("This is a notice message");
        $logger->warning("This is a warning message");
        $logger->alert("This is an alert message");*/
    });
}

if (isset($config->modelMiddleware)) {
    $di->set('modelMiddleware', function () use ($config) {
        return new AsyncCallerClient($config->modelMiddleware->toArray());
    });
}


/**
 * 增加安全的request对象
 */
$di->set('request', [
    'className' => 'Application\Core\Components\Internet\Http\Request',
]);


if (isset($config->queue)) {
    $di->set('queue', function () use ($config) {
        return new Beanstalk($config->queue->toArray());
    });
}
