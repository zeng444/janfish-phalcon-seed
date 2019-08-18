<?php

namespace Application\Core\Components\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

/**
 *
 * @author Robert
 *
 * Class Uniqueness
 * @package Application\Core\Components\Validation\Validator
 *     $validator = new Validation();
 *     $validator->add('name',
 *     new Uniqueness([
 *      'model' => $this,
 *       'message' => '集合名已经存在了',
 *       'exists' => function ($name) {
 *        if (Collection::findFirstByName($name)) {
 *       return true;
 *     }
 *     return false;
 *     }
 *     ])
 * );
 * return $this->validate($validator);
 *
 */
class Uniqueness extends Validator
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
        foreach ($attributes as $attribute) {
            if ($this->isUniqueness($validator, $attribute) === false) {
                $validator->appendMessage(new Message(strtr($message, [":field" => $attribute]), $attribute));
                return false;
            }
        }
        return true;
    }

    /**
     *
     * @author Robert
     *
     * @param Validation $validator
     * @param $attribute
     * @return bool
     */
    protected function isUniqueness(Validation $validator, $attribute)
    {
        $model = $this->getOption("model");
        if (!$model) {
            $model = $validator->getEntity();
        }
        $primaryKeys = $model->getModelsMetaData()->getPrimaryKeyAttributes($model);
        if (sizeof($primaryKeys) == 1) {
            $primaryKey = current($primaryKeys);
        } else {
            $primaryKey = 'id';
        }
        $id = intval($validator->getValue($primaryKey));
        if (!$model) {
            $model = $validator->getEntity();
        }
        $validatorMethod = $this->getOption("exists");
        $modelName = get_class($model);
        $value = $validator->getValue($attribute);
        if ($validatorMethod !== null) {
            $exist = call_user_func($validatorMethod, $value);
        } else {
            $exist = $modelName::findFirst([
                'conditions' => "$attribute=:attribute:",
                'bind' => [
                    'attribute' => $value,
                ],
            ]);
        }
        if (($id && $exist && $id != $exist->$primaryKey) || (!$id && $exist)) {
            return false;
        }
        return true;
    }
}
