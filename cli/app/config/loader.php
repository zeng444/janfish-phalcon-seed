<?php
$loader = new \Phalcon\Loader();
$loader->registerDirs([
    CLIENT_PATH.'app'.DIRECTORY_SEPARATOR.'tasks'.DIRECTORY_SEPARATOR,
    $config->application->modelsDir,
]);
$loader->register();
