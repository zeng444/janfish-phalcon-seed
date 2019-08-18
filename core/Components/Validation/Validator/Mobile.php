<?php

namespace Application\Core\Components\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

/**
 * Author:Robert
 *
 * Class Mobile
 * @package Application\Core\Components\Validation\Validator
 */
class Mobile extends Validator
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
            if ($this->checkMobile($validator->getValue($attribute)) === false) {
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
     * @param $mobile
     * @return bool
     */
    private function checkMobile($mobile)
    {
        if (preg_match('/^1[\d]{10}$/', $mobile)) {
            return true;
        }
        return false;
    }
}
