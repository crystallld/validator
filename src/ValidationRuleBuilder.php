<?php
namespace Validator;

use Illuminate\Support\Str;
use Illuminate\Contracts\Validation\Rule;

class ValidationRuleBuilder
{
    const FILLABLE = 1;
    const INTERNAL = 2;
    const FRAMEWORK = 3;
    const CUSTOMIZE = 4;

    protected $rule;

    protected $type;

    protected $validator;

    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function getDefaultRules()
    {
        $fillable = ValidatorFillable::$builders;
        $framework = ValidatorFramework::$builders;

        return array_merge($fillable, $framework);
    }

    public function setRule($value)
    {
        $this->rule = $value;

        return $this->rule;
    }

    public function handle($rule = null)
    {
        $rule = $rule?? $this->rule;

        $items = $unset = [];
        foreach ($rule as $key => $value) {
            if (in_array($key, $unset)) continue;

            $closure = false;
            if (is_numeric($key)) {
                $name = $value;
                $options = $rule;
            }else {
                $name = $key;
                $options = $value;
                $closure = true;
            }

            $options = $this->builder($name, $options);
            if (empty($this->type)) continue;

            $name = array_shift($options);
            if (!empty($options)) {
                if (!$closure) {
                    $unset = array_merge($unset, array_keys($options));
                }
            }

            switch ($this->type) {
                case static::FRAMEWORK:
                    $value = $name;
                    if (!empty($options)) {
                        $value .= ':'.implode(',', $options);
                    }
                    break;
                default:
                    $value = [$name];
                    if (!empty($options)) {
                        $value = array_merge($value, $options);
                    }
                    break;
            }

            $items[$this->type][$name] = $value;
        }

        return $items;
    }

    /**
     * 预处理
     */
    protected function prepare($data = null)
    {
        $data = empty($data)? $this->rule: $data;
        $defaults = $this->getDefaultRules();
        $rules = $unset = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $unset)) continue;

            if (is_numeric($key)) {
                $name = $value;
                if (in_array($name, $defaults)) {
                    $rules[] = [$name];
                }else {
                    if (!empty($options = $defaults[$name]?? [])) {
                        $value = array_intersect_key($data, array_flip($options));
                        array_push($unset, ... array_keys($value));
                    }else {
                        $rules[] = $this->rule;
                        break;
                    }
                }
            }else {
                $name = $key;
                if (empty($options = $defaults[$name]?? [])) continue;

                if (!is_array($value) || is_numeric(key($value))) {
                    $value = [reset($options) => $value];
                }
            }

            array_unshift($value, $name);
            $rules[$name] = $value;
        }

        return $rules;
    }

    protected function builder($name, $options = null)
    {
        $this->type = null;

        $data = $this->builderFillable($name, $options);

        if (empty($this->type)) {
            $data = $this->builderInternal($name, $options);
        }

        if (empty($this->type)) {
            $data = $this->builderFramework($name, $options);
        }

        if (empty($this->type)) {
            $data = $this->builderCustomize($name, $options);
        }

        if (empty($this->type)) return [];

        return $data;
    }

    protected function builderFillable($name, $options = null)
    {
        if (!$has = ValidatorFillable::has($name)) return false;

        $this->type = static::FILLABLE;

        $result = [$name];
        if ($has === true) return $result;
        if ($options === null) return $result;

        if (!is_array($options) || is_numeric($options)) {
            $options = [reset($has) => $options];
        }else {
            $options = array_intersect_key($options, array_flip($has));
        }

        return array_merge($result, $options);
    }

    protected function builderInternal($name, $options = null)
    {
        $prefix = 'validate';
        do {
            $exists = method_exists($this->container, $name);

            if ($exists) break;
            if (strpos($name, $prefix) !== false) break;

            $name = Str::camel($prefix.'_'.$name);
        }while(true);

        if (!$exists) return false;

        $this->type = static::INTERNAL;

        $result = [$name];
        if (empty($options)) return $result;

        return array_merge($result, $options);
    }

    protected function builderFramework($name, $options = null)
    {
        if (!$has = ValidatorFramework::has($name)) return false;

        $this->type = static::FRAMEWORK;
        $result = [$name];
        if ($has === true) return $result;
        if ($options === null) return $result;

        if (!is_array($options) || is_numeric($options)) {
            $options = [reset($has) => $options];
        }else {
            $extend = [];
            foreach ($has as $option) {
                if (!isset($options[$option])) continue;

                $value = $options[$option];
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                $extend[$option]= $value;
            }
            $options = $extend;
        }

        return array_merge($result, $options);
    }

    protected function builderCustomize($name, $options = null)
    {
        if (!class_exists($name)) return false;
        $builder = new $name;

        if (!($builder instanceof Rule)) return false;

        $this->type = static::FRAMEWORK;
        $result = [$name];
        if (empty($options)) return $result;

        $params = get_object_vars($builder);
        $options = array_intersect_key($options, $params);

        return array_merge($result, $options);
    }
}