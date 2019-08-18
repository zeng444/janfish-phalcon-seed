<?php

namespace Application\Core\Components\Internet\Http\Authorization;

use Phalcon\DI\InjectionAwareInterface;
use Phalcon\DiInterface;
use Application\Core\Components\ErrorManager;
use Phalcon\Http\Request as HttpRequest;

class Basic implements InjectionAwareInterface
{

    use  ErrorManager;

    /**
     * Author:Robert
     *
     * @var
     */
    public $username;


    /**
     * Author:Robert
     *
     * @var
     */
    public $password;


    /**
     * Author:Robert
     *
     * @var
     */
    protected $_di;

    /**
     * Author:Robert
     *
     * @var
     */
    public $requestHeaders;


    /**
     * Author:Robert
     *
     * @var
     */
    protected $authorization;


    /**
     * ��֤ʧ��Code
     */
    const UNAUTHORIZED_HTTP_CODE = 401;


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
     *
     */
    public function __construct()
    {
        $request = new HttpRequest();
        $this->requestHeaders = $request->getHeaders();
        if (isset($this->requestHeaders['Authorization'])) {
            $this->authorization = $this->requestHeaders['Authorization'];
        }
    }

    /**
     * Author:Robert
     *
     * @return bool
     */
    public function parseHeader()
    {
        if (!preg_match('/^Basic\s([^\s]+)/i', $this->authorization, $matched)) {
            return false;
        }
        $code = base64_decode($matched[1]);
        if (!preg_match('/([^:]+):([^:]+)/', $code, $matched[1])) {
            return false;
        }
        list(, $this->username, $this->password) = $matched[1];
        return true;
    }

    /**
     * Author:Robert
     *
     * @param  $username
     * @param  $password
     * @return bool
     */
    public function authorize($username, $password)
    {
        if ($this->parseHeader() === false) {
            $this->setError('401 Authorization Required', self::UNAUTHORIZED_HTTP_CODE);
            return false;
        }
        if ($username !== $this->username || $password !== $this->password) {
            $this->setError('401 Authorization Required', self::UNAUTHORIZED_HTTP_CODE);
            return false;
        }
        return true;
    }
}
