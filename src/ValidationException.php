<?php

namespace Validator;

use Illuminate\Validation\ValidationException as BaseValidationException;
use Illuminate\Support\MessageBag;

class ValidationException extends BaseValidationException
{
    public function __construct($validator, $response = null, $errorBag = 'default')
    {
        parent::__construct($validator, $response, $errorBag);

        $this->code = $this->validator->getErrno();
    }

    public function errors()
    {
        return $this->validator->errors(false);
    }
}
