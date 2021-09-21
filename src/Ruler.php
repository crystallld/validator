<?php
namespace Validator;

trait Ruler
{
    protected $scenario;

    protected $onException = true;

    private $_validator;

    /**
     * 获取当前类的attribute
     * @return mixed
     */
    public function attributes()
    {
        return array_keys(get_object_vars($this));
    }

    /**
     * 需要验证的场景
     * @return mixed
     */
    public function scenarios()
    {
        return [];
    }

    /**
     * 设置的验证规则；赋值校验在前，有效校验在后
     * eg.
     * attribute, rule, ... options
     * attribute, rule1, rule2, ...
     * [attribute1, ...], rule, ... options
     * attribute, rule1, rule2, ... options
     * attribute, rule1, rule2 => [... options], ... options
     * @return mixed
     */
    public function rules()
    {
        return [];
    }

    /**
     * 判断attribute是否存在
     * @param $attribute
     * @return mixed
     */
    public function hasAttribute($attribute)
    {
        $attributes = $this->attributes();

        return in_array($attribute, $attributes);
    }

    public function getScenario()
    {
        return $this->scenario;
    }

    public function setScenario($scenario)
    {
        $this->scenario = $scenario;

        return $this->scenario;
    }

    /**
     * 默认加载所有的字段
     * @TODO scenario 为空时，默认激活所有字段
     * @return array
     */
    public function activeAttributes()
    {
        $scenario = $this->getScenario();
        $scenarios = $this->scenarios();

        return $scenarios[$scenario]?? [];
    }

    public function validate($data = [], $attributes = null)
    {
        $attributes = $attributes?? $this->activeAttributes();

        $this->_validator = new Validator($data, $attributes, $this);


        return $this->_validator->handle();
    }

    public function addError($attribute, $message)
    {
        return $this->_validator->addError($attribute, $message);
    }

    public function getValidateData()
    {
        return $this->_validator->getData();
    }
}