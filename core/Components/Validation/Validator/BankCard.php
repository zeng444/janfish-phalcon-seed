<?php

namespace Application\Core\Components\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

/**
 * Author:Robert
 *
 * Class BackCard
 * @package Application\Core\Components\Validation\Validator
 */
class BankCard extends Validator
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
            if ($this->isBankCard($validator->getValue($attribute)) === false) {
                $validator->appendMessage(new Message(strtr($message, [":field" => $attribute]), $attribute));
                $hasError = true;
            }
        }
        return $hasError;
    }

    /**
     * Author:Robert
     *
     * @param string $no
     * @return bool
     */
    private function isBankCard(string $no): bool
    {
        $arr_no = str_split($no);
        $last_n = $arr_no[count($arr_no) - 1];
        krsort($arr_no);
        $i = 1;
        $total = 0;
        foreach ($arr_no as $n) {
            if ($i % 2 == 0) {
                $ix = $n * 2;
                if ($ix >= 10) {
                    $nx = 1 + ($ix % 10);
                    $total += $nx;
                } else {
                    $total += $ix;
                }
            } else {
                $total += $n;
            }
            $i++;
        }
        $total -= $last_n;
        $total *= 9;
        if ($last_n == ($total % 10)) {
            return true;
        }
        return false;
    }
}
