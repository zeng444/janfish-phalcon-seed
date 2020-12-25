<?php
$loader = new \Phalcon\Loader();
$loader->registerDirs([
    CLIENT_PATH.'app'.DIRECTORY_SEPARATOR.'tasks'.DIRECTORY_SEPARATOR,
]);
$loader->registerNamespaces([
    'Application\Core' => CORE_PATH,
]);
$loader->register();
