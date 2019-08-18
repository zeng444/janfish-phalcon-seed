<?php

namespace Application\Core\Components\Internet;

use Phalcon\DI\InjectionAwareInterface;
use Phalcon\DiInterface;
use Phalcon\Http\Request as HttpRequest;

/**
 * HTTP请求频率限制器
 *
 *  $config =[
 *   'enable' => true,
 *   'log_path' => 'logs/block.log',
 *   'unit_time' => 5,
 *   'allowed_request_number' => 2,
 *   'quick_request_sleep_duration' => 3,
 *   'allowed_max_quick_request_number' => 2,
 *   'block_duration' => 600,
 *  ]
 *
 *  if($frequencyManager->setPage("register")->setRule($config)->handle(false)===true){
 *
 *      echo "ERROR - ".$frequencyManager->getBlockRemainTime()."\n";
 *      print_r($frequencyManager->getBlockList("register"));
 *
 * }
 *
 * Class FrequencyManager
 *
 * @package Howyou\Core\Components\Internet
 */
class FrequencyManager implements InjectionAwareInterface
{

    /**
     * 客户端地址
     *
     * @var
     */
    public $clientAddress;

    /**
     * 开关，默认开启
     *
     * @var bool
     */
    public $enable = true;

    /**
     * 对应的页面
     *
     * @var string
     */
    public $page = 'all';

    /**
     * 单位时间
     *
     * @var int
     */
    public $unitTime = 300;

    /**
     * 允许的请求数，超过就进入快速请求违规状态
     *
     * @var int
     */
    public $allowedRequestNumber = 2;


    /**
     * 快速请求的休息时间
     *
     * @var int
     */
    public $quickRequestSleepDuration = 2;


    /**
     * 允许最多的快速请求的次数，超过就进入block状态
     *
     * @var int
     */
    public $allowedMaxQuickRequestNumber = 2;

    /**
     *  封锁时间
     *
     * @var
     */
    public $blockDuration = 1800;

    /**
     * 快速请求计数时间
     *
     * @var int
     */
    public $quickRequestUnitTime = 1800;

    /**
     * 日志文件
     *
     * @var
     */
    public $logPath;

    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [];

    /**
     * 锁定后返回的http code
     */
    const BLOCKED_HTTP_CODE = 403;


    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->_config = $options;
        if (is_array($options) && $options) {
            $this->setRule($options);
        }
        $request = new HttpRequest();
        $this->clientAddress = $request->getClientAddress(true);
    }

    /**
     * 设置规则
     *
     * @param  array $options
     * @return $this
     */
    public function setRule(array $options = [])
    {

        if (isset($options['enable'])) {
            $this->enable = $options['enable'];
        }
        if (isset($options['log_path'])) {
            $this->logPath = $options['log_path'];
        }

        if (isset($options['unit_time'])) {
            $this->unitTime = $options['unit_time'];
        }

        if (isset($options['allowed_request_number'])) {
            $this->allowedRequestNumber = $options['allowed_request_number'];
        }

        if (isset($options['quick_request_sleep_duration'])) {
            $this->quickRequestSleepDuration = $options['quick_request_sleep_duration'];
        }
        if (isset($options['quick_request_unit_time'])) {
            $this->quickRequestUnitTime = $options['quick_request_unit_time'];
        }
        if (isset($options['allowed_max_quick_request_number'])) {
            $this->allowedMaxQuickRequestNumber = $options['allowed_max_quick_request_number'];
        }

        if (isset($options['quick_request_unit_time'])) {
            $this->quickRequestUnitTime = $options['quick_request_unit_time'];
        }

        if (isset($options['block_duration'])) {
            $this->blockDuration = $options['block_duration'];
        }
        return $this;
    }


    /**
     * @param DiInterface $di
     */
    public function setDI(DiInterface $di)
    {
        $this->_di = $di;
    }

    /**
     * @return mixed
     */
    public function getDi()
    {
        return $this->_di;
    }

    /**
     * 设置标签
     *
     * @param  $page
     * @return $this
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     *
     * @param  string $tag
     * @return string
     */
    protected function getHashKey($tag = '')
    {
        $tag = $tag ? $tag.'_' : '';
        $page = $this->page ? '['.$this->page.']_' : '';
        $key = 'fb_'.$page.$tag.$this->clientAddress;
        //        echo $key."\n";
        return $key;
    }


    /**
     * 返回是否违规
     *
     * @param  bool|false $autoBlock 是否自动处理
     * @return bool
     */
    public function handle($autoBlock = true)
    {
        if ($this->enable === false) {
            return false;
        }

        if ($this->isBlock($this->clientAddress)) {
            if ($autoBlock === true) {
                $this->applyBlock();
            }
            return true;
        }

        $number = $this->addToRequestCounter($this->clientAddress);

        if ($number > $this->allowedRequestNumber - 1) {
            //快速请求计数达到阀值休息7秒
            $this->addToBlock($this->quickRequestSleepDuration);
            //            echo '删除访问计数' . "\n";
            $this->resetRequestCounter();
            //            echo '进入短锁定' . "\n";

            //            echo "第" . $ddd . '次短锁定' . "\n";
            if ($this->addToQuickRequestCounter() > $this->allowedMaxQuickRequestNumber) {
                //                echo '进入长锁定' . "\n";
                //快速请求次数达到阀值，休息20分钟
                $this->addToBlock($this->blockDuration);
                $this->resetQuickRequestCounter();
            }
        }
        return false;
    }

    /**
     * 请求计数器
     *
     * @return mixed
     */
    protected function addToRequestCounter()
    {
        $redis = $this->getDi()->get('redis');
        $key = $this->getHashKey();
        $number = $redis->incr($key);
        if ($number == 1) {
            $redis->expire($key, $this->unitTime);
        }
        return $number;
    }

    /**
     * 重置请求计数器
     *
     * @return mixed
     */
    protected function resetRequestCounter()
    {
        $redis = $this->getDi()->get('redis');
        $key = $this->getHashKey();
        return $redis->del($key);
    }

    /**
     * 快速快速请求计数器
     *
     * @return mixed
     */
    protected function addToQuickRequestCounter()
    {
        $redis = $this->getDi()->get('redis');
        $key = $this->getHashKey('quick');
        $number = $redis->incr($key);
        if ($number == 1) {
            $redis->expire($key, $this->quickRequestUnitTime);
        }
        return $number;
    }

    /**
     * 重置快速请求计数器
     *
     * @return mixed
     */
    protected function resetQuickRequestCounter()
    {
        $redis = $this->getDi()->get('redis');
        $key = $this->getHashKey('quick');
        return $redis->del($key);
    }

    /**
     * 锁定用户
     *
     * @param  $duration
     * @return mixed
     */
    protected function addToBlock($duration)
    {
        $redis = $this->getDi()->get('redis');
        $key = $this->getHashKey('blist');
        $this->writeLog('锁定'.$duration.'秒');
        return $redis->setEx($key, $duration, 1);
    }

    /**
     * 解除封锁
     *
     * @return mixed
     */
    public function unblock()
    {
        $redis = $this->getDi()->get('redis');
        $key = $this->getHashKey('blist');
        return $redis->del($key);
    }


    /**
     * 剩余时间
     *
     * @return mixed
     */
    public function getBlockRemainTime()
    {
        $redis = $this->getDi()->get('redis');
        $key = $this->getHashKey('blist');
        return $redis->ttl($key);
    }

    /**
     * 返回被锁定的列表
     *
     * @param  string $page
     * @return mixed
     */
    public function getBlockList($page = '')
    {
        $page = $page ? $page : $this->page;
        $redis = $this->getDi()->get('redis');
        $lists = $redis->keys('fb_\['.$page.'\]_blist_*');
        $lists = $lists ? $lists : [];
        $lists = array_map(function ($val) {
            $key = explode('_', $val);
            return $key[sizeof($key) - 1];
        }, $lists);
        return $lists;
    }

    /**
     * 是否被锁定
     *
     * @return mixed
     */
    public function isBlock()
    {
        $redis = $this->getDi()->get('redis');
        $key = $this->getHashKey('blist');
        if ($redis->get($key)) {
            return true;
        }
        return false;
    }


    /**
     * 执行锁定
     */
    public function applyBlock()
    {
        header("http/1.1 ".self::BLOCKED_HTTP_CODE." Forbidden");
        exit();
    }

    /**
     * @param $message
     */
    protected function writeLog($message)
    {
        if ($this->logPath) {
            error_log('['.date('Y-m-d H:i:s').'] '.'['.$this->clientAddress.'] ['.$this->page.'] '.$message."\n", 3, $this->logPath);
        }
    }
}
