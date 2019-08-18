<?php

namespace Application\Core\Components\Internet\Http;

use Phalcon\Http\Request as PhalconRequest;

/**
 * 对phalcon Request安全性重写
 * Class HttpRequest
 *
 * @package Application\Core\Components\Internet\Http
 */
class Request extends PhalconRequest
{

    /**
     * @param  null $name
     * @param  null $filters
     * @param  null $defaultValue
     * @param  bool|false $notAllowEmpty
     * @param  bool|false $noRecursive
     * @return string
     */
    public function get($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
    {
        $val = parent::get($name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
        if (!$filters) {
            $val = $this->xssFilter($val);
        }
        return $val;
    }

    /**
     * @param  null $name
     * @param  null $filters
     * @param  null $defaultValue
     * @param  bool|false $notAllowEmpty
     * @param  bool|false $noRecursive
     * @return string
     */
    public function getPost($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
    {
        if ('ignoreXSS' === $filters) {
            return parent::getPost($name, null, $defaultValue, $notAllowEmpty, $noRecursive);
        }
        $val = parent::getPost($name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
        if (!$filters) {
            $val = $this->xssFilter($val);
        }
        return $val;
    }


    /**
     * @param  null $name
     * @param  null $filters
     * @param  null $defaultValue
     * @param  bool|false $notAllowEmpty
     * @param  bool|false $noRecursive
     * @return string
     */
    public function getPut($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
    {
        $val = parent::getPut($name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
        if (!$filters) {
            $val = $this->xssFilter($val);
        }
        return $val;
    }

    /**
     * @param  null $name
     * @param  null $filters
     * @param  null $defaultValue
     * @param  bool|false $notAllowEmpty
     * @param  bool|false $noRecursive
     * @return string
     */
    public function getQuery($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
    {
        $val = parent::getQuery($name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
        if (!$filters) {
            $val = $this->xssFilter($val);
        }
        return $val;
    }


    /**
     * @param  $str
     * @return string
     */
    protected function xssFilter($str)
    {
        if (!$str) {
            return $str;
        }
        if (!is_array($str)) {
            $str = htmlspecialchars($str);
        } else {
            $str = array_map([$this, 'xssFilter'], $str);
        }
        return $str;
    }
}
