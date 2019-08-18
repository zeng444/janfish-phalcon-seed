<?php

use Janfish\Phalcon\AsyncCaller\Server as AsyncCallerServer;

/**
 * 模型异步执行的中间件
 * Author:Robert
 *
 * Class AsyncmodelTask
 */
class AsyncmodelTask extends \Phalcon\Cli\Task
{

    /**
     * Author:Robert
     *
     */
    public function handleAction()
    {
        $config = $this->getDI()->get('config');
        $asyncModel = new AsyncCallerServer($config->modelMiddleware->toArray());
        $asyncModel->start();
    }

}
