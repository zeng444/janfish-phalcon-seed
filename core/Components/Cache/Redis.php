<?php

namespace Application\Core\Components\Cache;

use Phalcon\Cache\Backend;
use Phalcon\Cache\Exception;
use Phalcon\Cache\BackendInterface;
use Phalcon\Di;

/**
 *
 * @author  Robert
 *
 * Class Redis
 * @package Application\Core\Components\Cache
 */
class Redis extends Backend implements BackendInterface
{

    /**
     * @var \Phalcon\DiInterface
     */
    protected $_di;

    /**
     * @param \Phalcon\Cache\FrontendInterface $frontend
     * @param null $options
     */
    public function __construct($frontend, $options = null)
    {
        if (!$options) {
            $options = [];
        }
        $this->_di = Di::getDefault();
        parent::__construct($frontend, $options);
    }

    /**
     *
     * @author Robert
     *
     * @param  null $keyName
     * @param  null $content
     * @param  null $lifetime
     * @param  bool|true $stopBuffer
     * @return bool
     * @throws Exception
     */
    public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = true)
    {
        if (!$keyName) {
            throw new Exception("The cache must be started first");
        }
        $key = "_PHCR".$this->_prefix.$keyName;
        $frontend = $this->_frontend;

        $cachedContent = $content;
        if (!$cachedContent) {
            $cachedContent = $frontend->getContent();
        }
        $cachedContent = $frontend->beforeStore($cachedContent);
        $ttl = $lifetime;
        if (!$ttl) {
            $ttl = $frontend->getLifetime();
        }
        $redis = $this->_di->get('redis');
        $success = $redis->setEx($key, $ttl, $cachedContent);
        if (!$success) {
            throw new Exception("Failed storing the data in redis");
        }
        if ($stopBuffer === true) {
            $frontend->stop();
        }
        $isBuffering = $frontend->isBuffering();
        if ($isBuffering === true) {
            echo $cachedContent;
        }
        return true;
    }

    /**
     *
     * @author Robert
     *
     * @param  null $prefix
     * @return array
     */
    public function queryKeys($prefix = null)
    {
        return [];
    }

    /**
     *
     * @author Robert
     *
     * @param  null $keyName
     * @param  null $lifetime
     * @return null
     */
    public function get($keyName = null, $lifetime = null)
    {
        $redis = $this->_di->get('redis');
        $key = "_PHCR".$this->_prefix.$keyName;
        $cachedContent = $redis->get($key);
        if (!$cachedContent) {
            return null;
        }
        if (is_numeric($cachedContent)) {
            return $cachedContent;
        }
        return $this->_frontend->afterRetrieve($cachedContent);
    }

    /**
     *
     * @author Robert
     *
     * @param  null $keyName
     * @return bool
     */
    public function delete($keyName = null)
    {
        $key = "_PHCR".$this->_prefix.$keyName;
        $redis = $this->_di->get('redis');
        if (!$redis->del($key)) {
            return false;
        }
        return true;
    }

    /**
     *
     * @author Robert
     *
     * @param  null $keyName
     * @param  null $lifetime
     * @return bool
     */
    public function exists($keyName = null, $lifetime = null)
    {
        if (!$keyName) {
            return false;
        }
        $redis = $this->_di->get('redis');
        $key = "_PHCR".$this->_prefix.$keyName;
        if (!$redis->get($key)) {
            return false;
        }
        return true;
    }
}
