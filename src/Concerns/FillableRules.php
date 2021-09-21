<?php
namespace Validator\Concerns;

trait FillableRules
{

    /**
     * default 规则，设置默认字段
     * @param $value
     * @param null $default
     * @return mixed
     */
    public function setDefaultValue($value, $default = null)
    {
        return is_null($value)? $default: $value;
    }

    /**
     * filter 规则，设置过滤条件
     * @param $attribute
     * @param $callable
     * @return mixed
     */
    public function setFilterValue($attribute, $callable)
    {
        if (!function_exists($callable)) return false;

        if ($this->$attribute === null) return false;

        $this->$attribute = $callable($this->$attribute);

        return $this->$attribute;
    }

}