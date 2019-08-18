<?php

namespace Application\Core\Components\Internet\Http;

use Phalcon\DI\InjectionAwareInterface;
use Phalcon\DiInterface;
use Phalcon\Http\Request as HttpRequest;

/**
 * 用户端验证接口请求的用户是否合法
 * Class OAuth2
 *
 * @package Application\Core\Components\Internet\Http
 */
class OAuth2 implements InjectionAwareInterface
{

    /**
     * @var
     */
    protected $_di;

    /**
     * @var
     */
    public $user;

    /**
     * @var string
     */
    public $accessTokenHeaderName = "Access-Token";

    /**
     * 错误信息
     *
     * @var string
     */
    public $errorMsg = "";

    /**
     * 错误编号
     *
     * @var string
     */
    public $errorCode = "";

    /**
     * 请求用户ID
     *
     * @var
     */
    public $userId;

    /**
     * 请求的HTTP HEADER
     *
     * @var
     */
    public $requestHeaders;

    /**
     * 请求的accessToken
     *
     * @var string
     */
    private $accessToken = "";

    /**
     * 请求的ACCESS-TOKEN变量
     */
    const ACCESS_TOKEN_HEADER_NAME = 'Access-Token';

    /**
     * 认证失败
     */
    const UNAUTHORIZED_HTTP_CODE = 401;

    /**
     * TOKEN已经过期了
     */
    //    const ACCESS_TOKEN_AUTHORIZED_OUT_DATE = 450;

    /**
     *
     */
    //    const REFRESH_TOKEN_AUTHORIZED_OUT_DATE = 451;

    /**
     * 析构
     */
    public function __construct()
    {
        $request = new HttpRequest();
        $this->requestHeaders = $request->getHeaders();
        if (isset($this->requestHeaders[self::ACCESS_TOKEN_HEADER_NAME])) {
            $this->accessToken = $this->requestHeaders[self::ACCESS_TOKEN_HEADER_NAME];
        }
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
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * 检查是否为一个有效的access Token
     */
    private function checkAccessToken()
    {
        if ($this->getHasToken() === false) {
            $this->errorMsg = "您的登录凭证不存在";
            $this->errorCode = self::UNAUTHORIZED_HTTP_CODE;
            return false;
        }
        $user = \User::getUserByAccessToken($this->accessToken);

        if (!$user) {
            $this->errorMsg = "您的请求不合法，错误的授权";
            $this->errorCode = self::UNAUTHORIZED_HTTP_CODE;
            return false;
        }
        //        if ($user->isAccessTokenExpires() === true) {
        //            $this->errorMsg = '对不起，您的登录凭证过期，请您重新登陆';
        //            $this->errorCode = self::ACCESS_TOKEN_AUTHORIZED_OUT_DATE;
        //            return false;
        //        }
        return $user;
    }

    /**
     * 获得用户ID
     *
     * @return string
     */
    public function getUserId()
    {
        if ($this->userId) {
            return $this->userId;
        }
        $user = $this->checkAccessToken();
        if (!$user) {
            return 0;
        }
        $this->userId = $user->id;
        return $this->userId;
    }

    /**
     * @return bool|null|\User
     */
    public function getUser()
    {
        if ($this->user) {
            return $this->user;
        }
        $user = $this->checkAccessToken();
        if (!$user) {
            return null;
        }
        $this->user = $user;
        return $user;
    }

    /**
     * 检查是否提交HEAD中的token
     *
     * @return bool
     */
    public function getHasToken()
    {
        if ($this->accessToken) {
            return true;
        }
        return false;
    }

    /**
     * @param  null $userId
     * @return bool|null
     */
    public function authorize($userId = null)
    {
        //验证签名
        if (!$user = $this->checkAccessToken()) {
            return false;
        }
        if ($userId === null) {
            $this->user = $user;
            return true;
        }
        if ($user->id != $userId) {
            $this->errorMsg = "对不起，您的登录凭证错误";
            $this->errorCode = self::UNAUTHORIZED_HTTP_CODE;
            return false;
        }
        $this->user = $user;
        $this->userId = $userId;
        return $this->userId;
    }
}
