<?php

namespace Application\Core\Components\Test;

use Phalcon\Di;

//class UnitCase extends  \PHPUnit_Framework_TestCase
class UnitCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Author:Robert
     *
     * @var \Phalcon\DiInterface
     */
    protected $di;

    /**
     * Author:Robert
     *
     * @var bool
     */
    protected $_loaded = false;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->di = Di::getDefault();
    }

    /**
     * Author:Robert
     *
     * @param $di
     */
    public function setDi($di)
    {
        $this->di = $di;
    }

    /**
     * Author:Robert
     *
     * @return \Phalcon\DiInterface
     */
    public function getDi()
    {
        return $this->di;
    }

    /**
     * 对象实例化话执行
     *
     * @author Robert
     */
    public static function setUpBeforeClass()
    {
        //        echo 'run set up before class' . PHP_EOL;
    }

    /**
     * 执行测试前执行
     *
     * @author Robert
     */
    protected function setUp()
    {
        //        echo 'run set up before class2' . PHP_EOL;
        $this->_loaded = true;
    }

    /**
     * Author:Robert
     */
    protected function tearDown()
    {
    }
}
