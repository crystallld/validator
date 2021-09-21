<?php
namespace Validator;

use Illuminate\Support\Facades\Validator;

class ValidatorFramework
{
    public static function make(array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        ## 兼容非laravel平台
        if (!class_exists(Validator::class)) return true;

        return Validator::make($data, $rules, $messages, $customAttributes);
    }

    public static function has($name)
    {
        $items = static::$builders;

        if (in_array($name, $items)) return true;

        if (!empty($options = $items[$name]?? [])) return $options;

        return false;
    }

    public static $builders = [
        'accepted',
        'active_url',
        'after'                 => ['value'],
        'after_or_equal'        => ['value'],
        'alpha',
        'alpha_dash',
        'alpha_num',
        'array',                 //=> ['max' => 'max', 'min' => 'min'],
        'before'                => ['value'],
        'before_or_equal'       => ['value'],
        'between'               => ['min', 'max'],
        'boolean',
        'confirmed',
        'date',                  //=> ['>' => 'after', '>=' => 'after_or_equal', '<' => 'before', '<=' => 'before_or_equal', '=' => 'date_equals', 'format' => 'date_format'],
        'date_equals'           => ['value'],
        'date_format'           => ['value'],
        'different'             => ['field'],
        'digits'                => ['value', 'between' => 'digits_between'],
        'digits_between'        => ['min', 'max'],
        'dimensions',
        'distinct',
        'email',
        'exists'                => ['table', 'column'],
        'file',
        'filled',
        'image',
        'in'                    => ['range'],
        'in_array'              => ['field'],
        'integer',               //=> ['max' => 'max', 'min' => 'min'],
        'ip',
        'ipv4',
        'ipv6',
        'json',
        'max'                   => ['value'],
        'mimetypes',
        'mimes'                 => ['range'],
        'min'                   => ['value'],
        'nullable',
        'not_in'                => ['range'],
        'numeric',               //=> ['max' => 'max', 'min' => 'min'],
        'present',
        'regex'                 => ['pattern'],
        'required',
        'required_if'           => ['field', 'value'],
        'required_unless'       => ['field', 'value'],
        'required_with'         => ['range'],
        'required_with_all'     => ['range'],
        'required_without_all'  => ['range'],
        'required_without'      => ['range'],
        'same'                  => ['field'],
        'size'                  => ['value'],//, 'max' => 'max', 'min' => 'min'
        'string',                //=> ['max' => 'max', 'min' => 'min'],
        'timezone',
        'unique'                => ['table', 'column', 'except', 'idColumn'],
        'url',
    ];
}