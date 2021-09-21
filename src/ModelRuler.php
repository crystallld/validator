<?php
/**
 * 根据model属性，实例化相应的规则条件
 */
namespace Validator;

use Illuminate\Database\Eloquent\Model;

trait ModelRuler
{
    use Ruler {
        validate as baseValidate;
        activeAttributes as baseActiveAttributes;
    }
    /**
     * 是否区分大小写
     * @var bool
     */
    protected $onLowercase = false;

    public function attributeLabels()
    {
        return [];
    }

    public static $defaultScenarios = [
        'create', 'update',
    ];

    public function getDefaultValue($option)
    {
       return null;
    }

    /**
     * 保存前校验
     */
    public function beforeSave()
    {
        $scenario = $this->getScenario();
        if (empty($scenario)) {## 设置默认场景
            $scenario = $this->exists? 'update': 'create';

            $this->setScenario($scenario);
        }

        if (!$this->validate()) return false;

        $data = $this->getValidateData();
        $attributes = $this->attributes();
        foreach ($data as $attribute => $value) {
            if (!in_array($attribute, $attributes)) continue;

            $this->$attribute = $value;
        }

        return true;
    }
    /**
     * @param array $changes 变动数据
     * @param boolean $insert 是否是insert
     * @return bool
     */
    public function afterSave($changes = [], $insert = true)
    {
        return true;
    }

    /**
     * insert|update 数据
     * @param array $options
     * @return bool
     */
    public function save(array $options = [])
    {
        if (!$this->beforeSave()) return false;

        $insert = $this->exists;
        if (!parent::save($options)) return false;

        return $this->afterSave($this->changes, $insert);
    }

    public function beforeDelete()
    {
        return true;
    }

    public function afterDelete()
    {
        return true;
    }

    public function delete()
    {
        if (!$this->beforeDelete()) return false;

        $delete = parent::delete();
        if (!$delete) return false;

        return $this->afterDelete($delete);
    }

    /**
     * 默认加载所有的字段
     * @TODO scenario 为空时，默认激活所有字段
     * @return array
     */
    public function activeAttributes()
    {
        $result = $this->baseActiveAttributes();

        $scenario = $this->getScenario();
        $defaults = static::$defaultScenarios;

        if (empty($result) && in_array($scenario, $defaults)) {
            return $this->attributes();
        }

        return $result;
    }

    /**
     * 校验前需要事件
     * @return bool
     */
    public function beforeValidate()
    {
        return true;
    }

    public function validate($data = [], $attributes = null)
    {
        if (!$this->beforeValidate()) return false;

        $data = empty($data)? $this->getAttributes(): $data;

        if (!$this->baseValidate($data, $attributes)) return false;

        return true;
    }

    public function validateUnique($attribute)
    {
        $value = $this->$attribute;
        if (empty($value)) return $this->addError($attribute, 'The value not empty.');

        $exists = $this->exists;
        if ($exists) {
            $value = $this->$attribute;
            $original = $this->getOriginal($attribute);
            if ($value != $original) return $this->addError($attribute, $original.': 不能被修改.');
        }else {
            $check = static::where($attribute, $value)->exists();
            if ($check) return $this->addError($attribute, $value.': 已存在.');
        }

        return $this;
    }
}