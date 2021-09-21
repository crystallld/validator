<?php
namespace Validator;

use Illuminate\Support\MessageBag;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Translation\Translator;

class ValidatorCustomize
{
    /**
     * The message bag instance.
     *
     * @var \Illuminate\Support\MessageBag
     */
    protected $messages;

    /**
     * @var
     */
    protected $customMessages;

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

    /**
     * The failed validation rules.
     *
     * @var array
     */
    protected $failedRules = [];

    /**
     * @var array
     */
    protected $replaces = [];

    public static function make(array $data, array $rules, $messages = [])
    {
        return new static($data, $rules, $messages);
    }

    /**
     * Create a new Validator instance.
     *
     * @param  array  $data
     * @param  array  $rules
     * @return void
     */
    public function __construct(array $data, array $rules, $messages = null)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $messages;
    }

    public static function has($type, $namespace = null)
    {
        $namespace = $namespace?? __NAMESPACE__;
        $class = $namespace."\\".ucwords($type);
        if (!class_exists($class)) return false;

        return new $class;
    }

    public function validate()
    {
        if ($this->fails()) {
            throw new ValidationException($this);
        }
    }

    /**
     * Determine if the data fails the validation rules.
     *
     * @return bool
     */
    public function fails()
    {
        return ! $this->passes();
    }

    /**
     * Get the failed validation rules.
     *
     * @return array
     */
    public function failed()
    {
        return $this->failedRules;
    }

    /**
     * Add conditions to a given field based on a Closure.
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @param  callable  $callback
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function sometimes($attribute, $rules, callable $callback)
    {
        return $this;
    }

    /**
     * After an after validation callback.
     *
     * @param  callable|string  $callback
     * @return $this
     */
    public function after($callback)
    {
        $this->after[] = function () use ($callback) {
            return call_user_func_array($callback, [$this]);
        };

        return $this;
    }

    public function passes()
    {
        $this->messages = new MessageBag;

        if (!empty($this->rules)) foreach ($this->rules as $attribute => $rules) {
            $attribute = str_replace('\.', '->', $attribute);
            $value = $this->data[$attribute]?? null;
            foreach ($rules as $rule) {
                if (!$rule instanceof Rule) continue;

                $result = $rule->passes($attribute, $value);

                if (!$result) {
                    $this->failedRules[$attribute][get_class($rule)] = [];
                    $this->messages->add($attribute, $rule->message());
                }else {
                    if (!is_bool($result)) {
                        $replace = is_array($result)? $result: [$result];
                        $this->replaces = array_merge($this->replaces, $replace);
                    }
                }
            }
        }

        if (!empty($this->customMessages)) {
            $this->messages->merge($this->customMessages);
        }

        return $this->messages->isEmpty();
    }

    public function messages()
    {
        if (!$this->messages) {
            $this->passes();
        }

        return $this->messages;
    }

    public function replaces()
    {
        return $this->replaces;
    }

    /**
     * Get all of the validation error messages.
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function errors()
    {
        return $this->messages;
    }

    public function addRule($rule)
    {
        array_push($this->rules, $rule);
    }
}