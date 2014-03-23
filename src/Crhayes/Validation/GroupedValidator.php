<?php

namespace Crhayes\Validation;

use Crhayes\Validation\Exceptions\MissingValidatorException;
use Crhayes\Validation\Exceptions\ValidatorContextException;

class GroupedValidator
{
	/**
	 * An array of Validator objects we will spin through
	 * when running our grouped validation.
	 *
	 * @var array
	 */
	private $validators = [];

	/**
	 * An array of errors returned from all of the validators.
	 *
	 * @var array
	 */
	private $errors = [];

    /**
     * @var BatchValidator
     */
    protected $batchValidator;

	/**
	 * Create a new GroupedValidator, with the option of specifying
	 * either a single validator object or an array of validators.
	 *
	 * @param mixed 	$validator
	 */
	public function __construct(BatchValidator $batchValidator = null,$validator = [])
	{
        $this->batchValidator = $batchValidator;

		if ($validator) $this->addValidator($validator);
	}

	/**
	 * Static shorthand for creating a new grouped validator.
	 *
	 * @param  mixed 	$validator
	 * @return \Crhayes\Validation\GroupedValidator
	 */
	public static function make(BatchValidator $batchValidator = null,$validator = [])
	{
		return new static($batchValidator,$validator);
	}

	/**
	 * Add a validator to spin through. Accepts either a single
	 * Validator object or an array of validators.
	 *
	 * @param mixed 	$validator
	 */
	public function addValidator($validator)
	{
		$validator = is_array($validator) ? $validator : [$validator];

		$this->validators = array_merge($this->validators, $validator);

		return $this;
	}

    /**
     * Performs a batch validation for data requiring the same validation
     *
     * @param array $dataCollection
     * @param ContextualValidator $validator
     * @param null $context
     * @return $this
     */
    public function batchValidate(array $dataCollection, ContextualValidator $validator, \Closure $callable = null)
    {
        if(empty($this->batchValidator)) throw new MissingValidatorException;

        $this->batchValidator->process($dataCollection,$validator);

        if(is_callable($callable)){
            call_user_func($callable,$this->batchValidator);
        }

        $this->addValidator($this->batchValidator->getValidators());

        return $this;
    }

	/**
	 * Perform a check to see if all of the validators have passed.
	 *
	 * @return boolean
	 */
	public function passes()
	{
		if ( ! count($this->validators)) throw new MissingValidatorException('No validators provided: You must provide at least one validator');

		foreach ($this->validators as $validator)
        {
			if ( ! $validator->passes())
			{
				$this->errors += $validator->getMessageBag()->getMessages();
			}
		}

		return (count($this->errors)) ? false : true;
	}

    /**
     * Get the registered validators
     *
     * @return array
     */
    public function getValidators()
    {
        return $this->validators;
    }
	/**
	 * Perform a check to see if any of the validators have failed.
	 *
	 * @return boolean
	 */
	 public function fails()
	 {
	 	return ! $this->passes();
	 }

	/**
	 * Return the combined errors from all validators.
	 *
	 * @return \Illuminate\Support\MessageBag
	 */
	public function errors()
	{
		return new \Illuminate\Support\MessageBag($this->errors);
	}
}
