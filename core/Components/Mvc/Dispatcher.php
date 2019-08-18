<?php

namespace Application\Core\Components\Mvc;

/**
 *
 * @author      Robert
 * @description 解决Phalcon根控制器不支持驼峰的URL路由地址的问题
 *
 * Class Dispatcher
 * @package     Application\Admin\Components\Mvc
 */
class Dispatcher extends \Phalcon\Mvc\Dispatcher
{

    /**
     *
     * @author Robert
     *
     * @return string
     */
    public function getHandlerClass()
    {
        if (strpos($this->_handlerName, '_') === false) {
            return ucfirst($this->_handlerName).$this->_handlerSuffix;
        }
        return parent::getHandlerClass();
    }
}
