<?php

namespace Application\Core\Components\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

/**
 * Author:Robert
 *
 * Class Realname
 * @package Application\Core\Components\Validation\Validator
 */
class Realname extends Validator
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
            if ($this->isRealname($validator->getValue($attribute)) === false) {
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
     * @param $name
     * @return bool
     */
    private function isRealname(string $name): bool
    {
        if (!preg_match('/^[\x{4e00}-\x{9fff}]+$/u', $name)) {
            return false;
        }
        return true;
    }
}
