<?php

namespace Application\Core\Models;

use Phalcon\Mvc\Model\Behavior\Timestampable;
use Phalcon\Mvc\Model;
use Phalcon\Db\Exception as DbException;
use Application\Core\Components\Mvc\Model\SoftDeleteModel;

/**
 *
 * @author Robert
 *
 * Class BaseModel
 */
class BaseModel extends Model
{

    use SoftDeleteModel;

    /**
     * 取得模型第一条错误
     *
     * @return string
     */
    public function getFirstError()
    {
        $errors = $this->getMessages();
        if (is_array($errors) === false) {
            return '';
        }
        $error = current($this->getMessages());
        if ($error) {
            return $error->getMessage();
        }
        return '';
    }

    /**
     * Author:Robert
     *
     * @param  array $valueList
     * @param  string $column
     * @return mixed
     * @throws
     */
    public static function findInOrder($valueList, $column = 'id')
    {
        if (!is_array($valueList)) {
            $valueList = explode(',', $valueList);
        }
        $modelName = get_called_class();
        $model = new $modelName();
        if (!property_exists($model, $column)) {
            throw new DbException("property  not exists in $modelName model");
        }
        $rows = $model->modelsManager->executeQuery("SELECT * FROM ".$modelName." WHERE $column IN  ({letter:array}) ORDER BY FIELD($column, {letter:array})", [
            'letter' => $valueList,
        ]);
        return $rows;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->useDynamicUpdate(true);
        $this->setup(array('notNullValidations' => false));
        $this->addBehavior(new Timestampable([
            'beforeCreate' => [
                'field' => ['updatedAt', 'createdAt'],
                'format' => 'Y-m-d H:i:s',
            ],
            'beforeUpdate' => [
                'field' => ['updated_at'],
                'format' => 'Y-m-d H:i:s',
            ],
        ]));
        $this->addSoftDeleteBehavior();
    }

    /**
     * 处理并转化输出为字符串
     *
     * @author Robert
     *
     * @param  null $columns
     * @param  bool|true $autoString
     * @return array
     */
    public function toArray($columns = null, $autoString = true)
    {
        $data = Model::toArray($columns);
        if ($autoString === true) {
            $data = array_map(function ($n) {
                return (string)$n;
            }, $data);
        }
        return $data;
    }
}
