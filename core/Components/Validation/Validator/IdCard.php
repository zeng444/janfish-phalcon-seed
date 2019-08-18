<?php

namespace Application\Core\Components\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

/**
 * Author:Robert
 *
 * Class IdCard
 * @package Application\Core\Components\Validation\Validator
 */
class IdCard extends Validator
{

    /**
     * Executes the validation
     *
     * @param Validation $validator
     * @param string $attributes
     * @return boolean
     */
    public function validate(Validation $validator, $attributes)
    {
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }
        $message = $this->getOption('message');
        $hasError = false;
        foreach ($attributes as $attribute) {
            if ($this->validateIDCard($validator->getValue($attribute)) === false) {
                $validator->appendMessage(
                    new Message(strtr($message, [":field" => $attribute]), $attribute)
                );
                $hasError = true;
            }
        }
        return $hasError;
    }


    /**
     * Author:Robert
     *
     * @param $idCard
     * @return bool
     */
    private function validateIDCard($idCard)
    {
        if (strlen($idCard) == 18) {
            return $this->check18IDCard($idCard);
        } elseif ((strlen($idCard) == 15)) {
            $idCard = $this->convertIDCard15to18($idCard);
            return $this->check18IDCard($idCard);
        } else {
            return false;
        }
    }


    /**
     * Author:Robert
     *
     * @param $idCardBody
     * @return bool|mixed
     */
    private function calcIDCardCode($idCardBody)
    {
        if (strlen($idCardBody) != 17) {
            return false;
        }
        //加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码对应值
        $code = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        $checksum = 0;

        for ($i = 0; $i < strlen($idCardBody); $i++) {
            $checksum += substr($idCardBody, $i, 1) * $factor[$i];
        }
        return $code[$checksum % 11];
    }

    /**
     * Author:Robert
     *
     * @param $idCard
     * @return bool|string
     */
    private function convertIDCard15to18($idCard)
    {
        if (strlen($idCard) != 15) {
            return false;
        } else {
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if (array_search(substr($idCard, 12, 3), array('996', '997', '998', '999')) !== false) {
                $idCard = substr($idCard, 0, 6) . '18' . substr($idCard, 6, 9);
            } else {
                $idCard = substr($idCard, 0, 6) . '19' . substr($idCard, 6, 9);
            }
        }
        $idCard = $idCard . $this->calcIDCardCode($idCard);
        return $idCard;
    }

    /**
     * Author:Robert
     *
     * @param $idCard
     * @return bool
     */
    private function check18IDCard($idCard)
    {
        if (strlen($idCard) != 18) {
            return false;
        }
        $idCardBody = substr($idCard, 0, 17); //身份证主体
        $idCardCode = strtoupper(substr($idCard, 17, 1)); //身份证最后一位的验证码

        if ($this->calcIDCardCode($idCardBody) != $idCardCode) {
            return false;
        } else {
            return true;
        }
    }
}
