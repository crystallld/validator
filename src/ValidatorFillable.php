<?php
namespace Validator;

use Illuminate\Support\Str;

class ValidatorFillable
{
    /**
     * The data under validation.
     *
     * @var array
     */
    protected $data;

    /**
     * The rules to be applied to the data.
     *
     * @var array
     */
    protected $rules;

    public static $builders = [
        'default'               => ['value'],
        'filter'                => ['filter'],
        'update'                => ['value'],
    ];

    public static function make(array $data, array $rules)
    {
        $model = new static($data, $rules);
        $model->handle();

        return $model;
    }

    public static function has($name)
    {
        $items = static::$builders;

        if (in_array($name, $items)) return true;

        if (!empty($options = $items[$name]?? [])) return $options;

        return false;
    }

    /**
     * Create a new Validator instance.
     *
     * @param  array  $data
     * @param  array  $rules
     * @return void
     */
    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public function getData()
    {
        return $this->data;
    }

    public function handle()
    {
        foreach ($this->rules as $attribute => $items) {
            $value = $this->data[$attribute]?? null;
            foreach ($items as $rule) {
                $name = array_shift($rule);
                $method = Str::camel('fill_'.$name.'_value');
                if (!method_exists($this, $method)) continue;

                $options = array_values($rule);
                $value = $this->$method($value, ... $options);

                $this->data[$attribute] = $value;
            }
        }

        return $this;
    }

    /**
     * default 规则，设置默认字段
     * @param $value
     * @param null $default
     * @return mixed
     */
    public function fillDefaultValue($value, $default = null)
    {
        return is_null($value)? $default: $value;
    }

    /**
     * filter 规则，设置过滤条件
     * @param $value
     * @param $callable
     * @return mixed
     */
    public function fillFilterValue($value, $callable)
    {
        if (!function_exists($callable)) return $value;

        if ($value === null) return $value;

        return $callable($value);
    }

    public function fillUpdateValue($value, $update)
    {
        return $update;
    }
}