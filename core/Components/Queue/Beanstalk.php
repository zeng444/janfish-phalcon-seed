<?php

namespace Application\Core\Components\Queue;

use Phalcon\Queue\Beanstalk as PhalconBeanstalk;
use Phalcon\Queue\Beanstalk\Exception;

/**
 * Author:Robert
 * Description: 散列方式向多组Beanstalkd PUT数据
 * Usage:
 *  Product
 *    $queue = new Beanstalk([["host" => "beanstalkd-1","port" => "11300"],["host" => "beanstalkd-2","port" => "11300"]]);
 *    $queue->choose(self::KEY);
 *    $queue->put($data);
 *
 *  Consumer
 *    $queue = new Beanstalk([["host" => "beanstalkd-1","port" => "11300"],["host" => "beanstalkd-2","port" => "11300"]]);
 *    $queue->chooseServer(0);
 *    $queue->watch(self::KEY);
 *    $queue->reserve();
 *
 * Class Beanstalk
 * @package Application\Core\Components\Queue
 */
class Beanstalk extends PhalconBeanstalk
{

    /**
     * Author:Robert
     *
     * @var array
     */
    protected $_connections = [];

    /**
     * Author:Robert
     *
     * @var array
     */
    protected $_parameters = [];

    /**
     * Author:Robert
     *
     * @var int
     */
    private $_connectionCount = 0;

    /**
     * Author:Robert
     *
     * @var
     */
    private $currentTube;

    /**
     * Author:Robert
     *
     * @var bool
     */
    private $_choose_connection = false;

    /**
     * Author:Robert
     *
     * @var
     */
    private $hashRing = [];

    /**
     * 哑节点数
     */
    const REPLICAS_SERVER_NUMBER = 5;


    /**
     * Beanstalk constructor.
     * @param array $parameters
     * @throws \Exception
     */
    public function __construct(array $parameters = array())
    {
        foreach ($parameters as $parameter) {
            $this->addServer($parameter);
        }
    }


    /**
     * Author:Robert
     *
     * @param $target
     * @param string $algo
     * @return mixed
     * @throws Exception
     */
    private function getHash($target, $algo = 'crc32')
    {
        if (!function_exists($algo)) {
            throw new Exception("不存在hash算法".$algo);
        }
        $target = serialize($target);
        if ($algo == 'crc32') {
            return crc32($target);
        }
        return hexdec(substr(md5($target), 0, 8));
    }

    /**
     * Author:Robert
     *
     * @param $parameter
     * @throws \Exception
     */
    public function addServer($parameter)
    {
        if (!isset($parameter['host'])) {
            $parameter['host'] = self::DEFAULT_HOST;
        }
        if (!isset($parameter['port'])) {
            $parameter['port'] = self::DEFAULT_PORT;
        }
        if (!isset($parameter['persistent'])) {
            $parameter['persistent'] = false;
        }
        if (!isset($parameter['weight'])) {
            $parameter['weight'] = self::REPLICAS_SERVER_NUMBER;
        }
        for ($i = 0; $i < $parameter['weight']; $i++) {
            $position = $this->getHash($parameter['host'].":".$parameter['port'].$i);
            $this->hashRing[$position] = [
                'index' => sizeof($this->_parameters),
                //                'parameter' => $parameter,
            ];
        }
        $this->_parameters[] = $parameter;
        $this->_connectionCount++;
    }

    /**
     * 查找服务器
     * Author:Robert
     *
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function lookupServer($data)
    {
        $server = end($this->hashRing);
        if (sizeof($this->hashRing) === 1) {
            return $server;
        }
        $dataPosition = $this->getHash($data);
        ksort($this->hashRing, SORT_REGULAR);
        $server = end($this->hashRing);
        foreach ($this->hashRing as $key => $value) {
            if ($key > $dataPosition) {
                $server = $value;
                break;
            }
        }
        return $server;
    }


    /**
     * 获得服务器总数
     * Author:Robert
     *
     * @return int
     */
    public function getServerCount()
    {
        return $this->_connectionCount;
    }

    /**
     * 选择并连接服务
     * Author:Robert
     *
     * @param $index
     * @throws Exception
     */
    public function chooseServer($index)
    {
        $this->_choose_connection = true;
        $this->connect($this->_parameters[$index]);
    }

    /**
     * 连接服务
     * Author:Robert
     *
     * @param $parameter
     * @return resource
     * @throws Exception
     */
    public function connect($parameter = null)
    {
        if ($parameter === null) {
            $parameter = current($this->_parameters);
        }
        if ($parameter['persistent']) {
            $connection = pfsockopen($parameter["host"], $parameter["port"], $errorNo, $errorMsg);
        } else {
            $connection = fsockopen($parameter["host"], $parameter["port"], $errorNo, $errorMsg);
        }
        if (!is_resource($connection)) {
            throw new Exception("Can't connect to Beanstalk server");
        }
        stream_set_timeout($connection, -1, null);
        $this->_connection = $connection;
        return $connection;
    }

    /**
     * 断开连接
     * Author:Robert
     *
     * @return bool
     */
    public function disconnect(): bool
    {
        parent::disconnect();
        foreach ($this->_connections as &$connection) {
            if (!is_resource($connection['connection'])) {
                continue;
            }
            fclose($connection['connection']);
            $connection['connection'] = null;
        }
        return true;
    }

    /**
     * 手动或者自动选择需要使用的连接句柄，默认为轮询
     * Author:Robert
     *
     * @param null $index
     * @return mixed
     * @throws Exception
     */
    protected function chooseConnect($index)
    {
        if (!isset($this->_connections[$index]) || !is_resource($this->_connections[$index]['connection'])) {
            $parameter = $this->_parameters[$index];
            $connection = $this->connect($parameter);
            $this->_connections[$index] = [
                'connection' => $connection,
                'parameter' => $parameter,
            ];
        }
        return $this->_connections[$index]['connection'];
    }


    /**
     * Author:Robert
     *
     * @param string $tube
     * @return bool|string
     */
    public function choose($tube)
    {
        if (!$tube) {
            return false;
        }
        $this->currentTube = $tube;
        return $this->currentTube;
    }


    /**
     * Author:Robert
     *
     * @param string $tube
     * @return bool|int
     * @throws Exception
     */
    public function watch($tube)
    {
        if ($this->_choose_connection === false) {
            $this->chooseServer(0);
        }
        $this->write("watch ".$tube);
        $response = $this->readStatus();
        if ($response[0] != "WATCHING") {
            return false;
        }
        return (int)$response[1];
    }

    /**
     * Author:Robert
     *
     * @param mixed $data
     * @param array|null $options
     * @return bool|int
     * @throws Exception
     */
    public function put($data, array $options = null)
    {
        $findOut = null;
        if ($this->_choose_connection === false) {
            $findOut = $this->lookupServer($data);
            $this->_connection = $this->chooseConnect($findOut['index']);
        }
        $this->write("use ".$this->currentTube);
        $response = $this->readStatus();
        if ($response[0] != "USING") {
            return false;
        }
        $priority = !isset($options["priority"]) ? self::DEFAULT_PRIORITY : $options["priority"];
        $delay = !isset($options["delay"]) ? self::DEFAULT_DELAY : $options["delay"];
        $ttr = !isset($options["ttr"]) ? self::DEFAULT_TTR : $options["ttr"];
        $serialized = serialize($data);
        $length = strlen($serialized);
        $this->write("put ".$priority." ".$delay." ".$ttr." ".$length."\r\n".$serialized);
        $response = $this->readStatus();
        $status = $response[0];
        if ($status != "INSERTED" && $status != "BURIED") {
            return false;
        }
        return (int)$response[1];
        //        return $findOut;
    }
}
