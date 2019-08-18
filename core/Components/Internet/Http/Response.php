<?php

namespace Application\Core\Components\Internet\Http;

use Phalcon\DI\InjectionAwareInterface;
use Phalcon\DiInterface;

/**
 * 包装response的数据
 * Class ApiResponse
 *
 * @package Application\Core\Components\Internet
 */
class Response implements InjectionAwareInterface
{

    /**
     * HTTP CODE
     *
     * @var int
     */
    public $httpCode = 200;

    /**
     * Bad Request
     */
    const HTTP_BAD_REQUEST_CODE = 400;

    /**
     * Not Found
     */
    const HTTP_NOT_FOUND_CODE = 404;

    /**
     * ok
     */
    const HTTP_OK_CODE = 200;

    /**
     *  Internal Server Error
     */
    const HTTP_INTERNAL_SERVER_ERROR_CODE = 500;
    //    const HTTP_INTERNAL_SERVER_ERROR_CODE = 200;

    /**
     * Unauthorized
     */
    const HTTP_UNAUTHORIZED_CODE = 401;

    /**
     * No Content
     */
    const HTTP_NO_CONTENT_CODE = 204;

    /**
     * @var string
     */
    private $responseType = '';

    /**
     * Author:Robert Tsang
     *
     * @var
     */
    protected $_di;

    /**
     * Author:Robert Tsang
     *
     * @var bool
     */
    protected $debug = false;


    /**
     * Response constructor.
     * @param $options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['debug'])) {
            $this->debug = $options['debug'];
        }
    }

    public function setDI(DiInterface $di)
    {
        $this->_di = $di;
    }

    public function getDi()
    {
        return $this->_di;
    }

    /**
     * 设置http code
     *
     * @param $code
     */
    public function setHttpCode($code)
    {
        $this->httpCode = $code;
    }

    /**
     * 获取http code
     *
     * @return int
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /**
     * 输出数据
     *
     * @param  $data
     * @param  int $code
     * @param  $message
     * @return array
     */
    public function success($data, $code = self::HTTP_OK_CODE, $message = '')
    {
        $this->responseType = 'success';
        $this->setHttpCode($code);
        $responseData = [
            "data" => $data,
            "message" => $message,
            "status" => "success",
            "code" => (string)$code,
        ];
        $this->debug($responseData);

        return $responseData;
    }

    /**
     * Author:Robert Tsang
     *
     * @param $responseData
     */
    protected function debug($responseData)
    {
        if ($this->debug) {
            $request = new \Phalcon\Http\Request();
            $requestHeaders = $request->getHeaders();
            if ($requestHeaders) {
                $this->getDi()->get('logger')->debug('REQUEST HEADER'.json_encode($requestHeaders));
            }

            $method = '';
            if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD']) {
                $method = $_SERVER['REQUEST_METHOD'];
            }
            if ($method == 'POST') {
                $this->getDi()->get('logger')->debug('REQUEST POST '.json_encode($_POST));
            }

            $this->getDi()->get('logger')
                 ->debug('RESPONSE '.$_SERVER['REQUEST_URI'].' '.$this->responseType.' '.json_encode($responseData));
        }
    }

    /**
     * 错误输出
     *
     * @param  string $message
     * @param  $code
     * @param  string $exception
     * @return array
     */
    public function error($message, $code = self::HTTP_INTERNAL_SERVER_ERROR_CODE, $exception = "")
    {
        $this->responseType = 'error';
        if ($code < 10000) {
            $this->setHttpCode($code);
        } else {
            $this->setHttpCode(self::HTTP_OK_CODE);
        }
        $responseData = [
            "data" => $exception,
            "message" => $message,
            "status" => "error",
            "code" => (string)$code,
        ];
        $this->debug($responseData);
        return $responseData;
    }
}
