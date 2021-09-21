<?php
/**
 * 校验规则，按优先级顺序排列
 * 1. 具有“set”属性的扩展规则（详见：buildValidators#扩展规则)
 * 2. 当前类自带的校验规则（ 不同的类对attribute设定的校验函数）
 * 3. 框架默认的校验规则（详见：buildValidators#框架默认规则)
 * 4. 自定义的校验规则 (详见：ValidatorCustomize::buildValidators)
 */
namespace Validator;

use Illuminate\Support\MessageBag;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Validator
{
//    use Concerns\FillableRules;
    /**
     * 是否有错误
     * @var bool
     */
    public $hasError = false;

    protected $data = [];

    protected $attributes = [];

    protected $message;

    protected $hasValidate = false;

    protected $onException = true;

    protected $container;

    protected $builder;

    private $_validators = [];

    private $_customize = [];

    public static function make($data = [], $attributes = [], $container = null)
    {
        $model = new static($data, $attributes, $container);

        $model->handle();

        ## 不抛出异常

        return $model;
    }

    public function __construct($data, $attributes, $container)
    {
        $this->data = $data;
        $this->attributes = $attributes;
        $this->container = $container;

        $this->message = new MessageBag();
        $this->builder = new ValidationRuleBuilder($container);
    }

    /**
     * 校验规则，按优先级顺序排列
     * 1. 具有“set”属性的扩展规则（详见：buildValidators#扩展规则)
     * 2. 当前类自带的校验规则（ 不同的类对attribute设定的校验函数）
     * 3. 框架默认的校验规则（详见：buildValidators#框架默认规则)
     * 4. 自定义的校验规则 (详见：ValidatorCustomize::buildValidators)
     *
     * 当出现错误时，不再进行其他模式的验证
     *
     * @return array|bool
     */
    public function handle()
    {
        if (!is_object($model = $this->container)) return true;
        if (empty($attributes = $this->attributes)) return true;

        $validators = $this->getValidators();
        if (empty($validators)) return true;

        $data = $this->data;
        foreach ($validators as $type => $rules) {
            switch ($type) {
                case ValidationRuleBuilder::FILLABLE:
                    $make = ValidatorFillable::make($data, $rules);
                    $data = $make->getData();
                    break;
                case ValidationRuleBuilder::INTERNAL:
                    ## 自定义函数校验
                    foreach ($rules as $attribute => $items) {
                        foreach($items as $rule) {
                            $method = array_shift($rule);
                            $model->$method($attribute, ...array_keys($rule));
                        }

                        $data[$attribute] = $model->$attribute;
                    }
                    break;
                case ValidationRuleBuilder::FRAMEWORK:
                    $values = [];
                    foreach ($rules as $attribute => $items) {
                        $value = $data[$attribute]?? null;
                        $hasRequired = $this->hasRequired(array_keys($items));
                        if (!$hasRequired && $value === null) continue;

                        $values[$attribute] = array_values($items);
                    }

                    if (empty($values)) break;

                    $make = ValidatorFramework::make($data, $values);
                    $this->setErrors($make->messages());
                    break;
                case ValidationRuleBuilder::CUSTOMIZE:
                    $make = ValidatorCustomize::make($data, $rules);
                    $this->setErrors($make->messages());
                    break;
            }
        }

        $this->data = $data;

        if (!$this->hasError) return true;

        return false;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getValidators()
    {
        if (empty($this->_validators)) {
            $this->createValidators();
        }

        return $this->_validators;
    }

    public function createValidators()
    {
        $scenario = $this->container->getScenario();

        foreach ($this->container->rules() as $rule) {
            if (!empty($on = $rule['on']?? false)) {
                $on = is_array($on)? $on: [$on];
                if (!in_array($scenario, $on)) continue;
                unset($rule['on']);
            }

            if (!is_array($rule) || !isset($rule[0], $rule[1])) {
                throw new \Exception(__METHOD__, 24);
            }

            $attributes = array_shift($rule);
            if (empty($rule)) continue;

            $attributes = array_intersect(Arr::wrap($attributes), $this->attributes);
            if (empty($attributes)) continue;

            $items = $this->builder->handle($rule);
            if (empty($items)) continue;

            foreach ($attributes as $attribute) {
                foreach ($items as $type => $list) {
                    $before = $this->_validators[$type][$attribute]?? [];
                    $this->_validators[$type][$attribute] = array_merge($before, $list);
                }

            }
        }

        ksort($this->_validators);

        return $this->_validators;
    }

    public function hasRequired($rules)
    {
        $list = ['required', 'required_if', 'required_unless', 'required_with',
            'required_with_all', 'required_without_all', 'required_without',
        ];

        $has = array_intersect($rules, $list);

        return !empty($has);
    }

    public function getOnException()
    {
        return $this->onException;
    }

    public function setOnException($on)
    {
        $this->onException = $on;

        return $this;
    }

    /**
     * @param $attribute
     * @param string|integer|array $message
     * 1. string message
     * 2. integer code
     * 3. array|list
     * 4. :validation
     * @param bool $default
     * @return $this
     */
    public function addError($attribute, $message, $default = false)
    {
        if (!$this->message) {
            $this->message = new MessageBag;
        }

        if (!empty($message)) {
            if (is_array($message)) {
                $code = count($message) > 1? null:$message[0]?? null;
                if (!is_null($code)) {
                    $message = $message[1]?? '';
                }
            }else if (is_numeric($message)) {
                $code = $message;
            }else if (is_string($message)) {
//                var_dump($message);exit;
//                $message = trans_valid($message, $attribute);
            }

            if (isset($code)) {
                $this->message->code = $code;
                $message = trans_error($code, $message);
            }
            $this->message->add($attribute, $message);

            $this->hasError = true;
        }

        if (!$this->onException) return $default;

        return $this->errors($this->onException);
    }

    /**
     * @param array|object $errors
     * @param null $default
     * @return Ruler
     */
    public function setErrors($errors, $default = null)
    {
        if (!$this->message) {
            $this->message = new MessageBag;
        }

        if ($errors instanceof MessageBag) {
            $errors = $errors->messages();
        }

        if (!empty($errors) && is_array($errors)) {
            $this->message->merge($errors);

            $this->hasError = true;
        }

        if (!$this->onException) return $default;

        return $this->errors($this->onException);
    }

    /**
     * 1. model 默认传errors信息
     * 2. 其他默认抛出errors信息
     * @param bool $throw 强制抛出异常
     * @return mixed
     */
    public function errors($throw = false)
    {
        if (!$this->hasError) return [];

        $messages = [];
        if ($this->message instanceof MessageBag) {
            $messages = $this->message->messages();
        }

        if ($throw) {
            throw new ValidationException($this);
        }else {
            return $messages;
        }
    }

    public function getErrno()
    {
        if (!$this->hasError) return 0;

        return $this->message->code?? 0;
    }

    public function clear()
    {
        $this->hasError = null;
        $this->message = null;
        $this->hasValidate = null;
        $key = $this->getKeyName()?? null;
        if (!is_null($key)) {
            $this->$key = null;
        }
    }
}
