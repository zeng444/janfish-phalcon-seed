<?php
/**
 * Registering an autoloader
 */
$loader = new \Phalcon\Loader();
$loader->registerNamespaces([
    'Application\Core' => CORE_PATH,
]);
$loader->register();
