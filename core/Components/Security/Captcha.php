<?php

namespace Application\Core\Components\Security;

use Phalcon\DI\InjectionAwareInterface;
use Phalcon\DiInterface;

/**
 * 验证码生成验证器
 * Class Captcha
 *
 * @package Application\Core\Components\Security
 */
class Captcha implements InjectionAwareInterface
{
    /**
     * @var
     */
    protected $_di;


    /**
     * 验证码有效期 单位分
     */
    const DEFAULT_VERIFY_CODE_EXPIRES_TIME = 10;

    /**
     * 验证码长度
     */
    const DEFAULT_VERIFY_CODE_LENGTH = 6;

    /**
     *
     */
    const REGISTER_MODEL = 'REGISTER';

    /**
     *
     */
    const FORGOTTEN_PASSWORD_MODEL = 'FORGOTTEN_PASSWORD';


    /**
     * Author:Robert
     *
     * @var array
     */
    public static $modelMap = [
        self::REGISTER_MODEL => '注册验证',
        self::FORGOTTEN_PASSWORD_MODEL => '忘记密码',
    ];

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
     * 获取所有的验证模型名称
     *
     * @return array
     */
    public static function getModelNames()
    {
        return array_keys(self::$modelMap);
    }

    /**
     * 按用户ID和业务类型生成验证码KEY
     *
     * @param $modelName
     * @param $userId
     *
     * @return string
     */
    public function getModelKey($modelName, $userId)
    {
        if ($userId) {
            return $modelName.'_'.$userId;
        }

        return $modelName;
    }


    /**
     * 生成注册验证码
     *
     * @param        $modelName
     * @param string $uid
     * @param int $expires
     * @param int $length
     *
     * @return string
     * @throws \Exception
     */
    public function generateVerifyCode($modelName, $uid = "", $expires = 0, $length = 0)
    {
        if ($this->isExistModel($modelName) === false) {
            throw new \Exception("不存在的验证业务模型，请在".__CLASS__."对象中添加");
        }
        $key = $this->getModelKey($modelName, $uid);

        $expires = $expires ? $expires : 60 * self::DEFAULT_VERIFY_CODE_EXPIRES_TIME;
        $length = $length ? $length : self::DEFAULT_VERIFY_CODE_LENGTH;
        $value = '';
        for ($i = 0; $i < $length; $i++) {
            $value .= rand(0, 9);
        }

        $result = $this->_di->get('redis')->setEx($key, $expires, $value);
        if ($result != 'OK') {
            return '';
        }

        return $value;
    }


    /**
     * 是否有效的模型
     *
     * @param $model
     *
     * @return bool
     */
    public function isExistModel($model)
    {

        if (in_array($model, self::getModelNames())) {
            return true;
        }

        return false;
    }


    /**
     * 检查某个验证码是否已经过期
     *
     * @param $modelName
     * @param $uid
     *
     * @return bool
     * @throws \Exception
     */
    public function isExpired($modelName, $uid)
    {
        if ($this->isExistModel($modelName) === false) {
            throw new \Exception("不存在的验证业务模型，请在".__CLASS__."对象中添加");
        }
        $key = $this->getModelKey($modelName, $uid);
        $verifyCode = $this->_di->get('redis')->get($key);
        if (!$verifyCode) {
            return true;
        }

        return false;
    }


    /**
     * 验证是否合法
     *
     * @param           $modelName
     * @param string $uid
     * @param           $value
     * @param bool|true $destroy
     *
     * @return bool
     * @throws \Exception
     */
    public function validate($modelName, $uid, $value, $destroy = true)
    {
        if ($this->isExistModel($modelName) === false) {
            throw new \Exception("不存在的验证业务模型");
        }
        //测试环境使用
        //        if ($this->_di->get('env') === 'dev' || $value == '4268') {
        if ($this->_di->get('env') !== 'prod' && $value == '42684268') {
            return true;
        }
        $key = $this->getModelKey($modelName, $uid);
        $verifyCode = $this->_di->get('redis')->get($key);
        if ($verifyCode && $verifyCode == $value) {
            if ($destroy === true && !$this->removeCode($modelName, $uid)) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Author:Robert
     *
     * @param $modelName
     * @param $uid
     * @return bool
     */
    public function removeCode($modelName, $uid)
    {
        $key = $this->getModelKey($modelName, $uid);
        $this->_di->get('redis')->del($key);
        return true;
    }
}
