<?php

namespace Crhayes\Validation;

use Crhayes\Validation\Exceptions\ReplacementBindingException;
use Crhayes\Validation\Exceptions\ValidatorContextException;
use Illuminate\Support\Contracts\MessageProviderInterface;
use Illuminate\Validation\Factory;
use Input;
use Validator;

abstract class ContextualValidator implements MessageProviderInterface
{
	const DEFAULT_KEY = 'default';

	/**
	 * Store the attributes we are validating.
	 * 
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * Validator data
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * Validator files
	 *
	 * @var array
	 */
	protected $files = [];

	/**
	 * Store the validation rules.
	 * 
	 * @var array
	 */
	protected $rules = [];

	/**
	 * Store any custom messages for validation rules.
	 * 
	 * @var array
	 */
	protected $messages = [];

	/**
	 * Store any contexts we are validating within.
	 * 
	 * @var array
	 */
	protected $contexts = [];

	/**
	 * Store replacement values for any bindings in our rules.
	 * 
	 * @var array
	 */
	protected $replacements = [];

	/**
	 * Store any validation messages generated.
	 * 
	 * @var array
	 */
	protected $errors = [];

	/**
	 * Our constructor will store the attributes we are validating, and
	 * may also take as a second parameter the contexts within which 
	 * we are validating.
	 * 
	 * @param array 	$attributes
	 * @param mixed 	$context
	 */
	public function __construct($attributes = null, $context = null)
	{
		$this->attributes = $attributes ?: Input::all();

		if ($context) $this->addContext($context);
	}

	/**
	 * Static shorthand for creating a new validator.
	 *
	 * @param null $attributes
	 * @param null $context
	 * @return static
	 */
	public static function make($attributes = null, $context = null)
	{
		return new static($attributes, $context);
	}

	/**
	 * Set the validation attributes.
	 *
	 * @param  array $attributes
	 * @return \Crhayes\Validation\GroupedValidator
	 */
	public function setAttributes($attributes = null)
	{
		$this->attributes = $attributes ?: Input::all();

		return $this;
	}

	/**
	 * Retrieve the validation attributes.
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * Retrieve a file, data element, or all request data
	 *
	 * @param null $key
	 * @return array
	 */
	public function get($key = null) {

		if ($key) {
			if ($this->has($key)) {
				return $this->data[$key];
			}

			if ($this->hasFile($key)) {
				return $this->files[$key];
			}
		}

		return array_merge($this->data, $this->files); // Return All Data if key is null
	}

	/**
	 * Get only inputs specified in the data|file arrays
	 *
	 * @param array $keys
	 * @return array
	 */
	public function getOnly(array $keys) {

		return array_merge(array_only($this->data, $keys), array_only($this->files, $keys));
	}

	/**
	 * Retrieve all data ignoring the keys specified
	 *
	 * @param array $keys
	 * @return array
	 */
	public function getIgnore(array $keys) {

		return array_merge(array_except($this->data, $keys), array_except($this->files, $keys));
	}

	/**
	 * Check data key exists
	 *
	 * @param $required_keys
	 * @return bool
	 */
	public function has($required_keys) {

		$required_keys = is_array($required_keys) ? $required_keys : func_get_args();

		return count(array_intersect_key(array_flip($required_keys), $this->data)) === count($required_keys);
	}

	/**
	 * Check file key exists
	 *
	 * @param $required_keys
	 * @return bool
	 */
	public function hasFile($required_keys) {

		$required_keys = is_array($required_keys) ? $required_keys : func_get_args();

		return count(array_intersect_key(array_flip($required_keys), $this->files)) === count($required_keys);
	}


	/**
	 * Add a validation context.
	 *
	 * @param $context
	 * @return $this
	 */
	public function addContext($context)
	{
		$context = is_array($context) ? $context : [$context];
		
		$this->contexts = array_merge($this->contexts, $context);

		return $this;
	}

	/**
	 * Set the validation context.
	 *
	 * @param  array|string $context
	 * @return \Crhayes\Validation\GroupedValidator
	 */
	public function setContext($context)
	{
		$this->contexts = is_array($context) ? $context : [$context];

		return $this;
	}

	/**
	 * Retrieve the valiation context.
	 * 
	 * @return array
	 */
	public function getContexts()
	{
		return $this->contexts;
	}

	/**
	 * Bind a replacement value to a placeholder in a rule.
	 * 
	 * @param  string 	$field
	 * @param  array 	$replacement
	 * @return \Crhayes\Validation\ContextualValidator
	 */
	public function bindReplacement($field, array $replacement)
	{
		$this->replacements[$field] = $replacement;

		return $this;
	}

	/**
	 * Get a bound replacement by key.
	 * 
	 * @param  string $key
	 * @return array
	 */
	public function getReplacement($key)
	{
		return array_get($this->replacements, $key, []);
	}

	/**
	 * Perform a validation check against our attributes.
	 * 
	 * @return boolean
	 */
	public function passes()
	{
		$rules = $this->bindReplacements($this->getRulesInContext());

		$validation = Validator::make($this->attributes, $rules, $this->messages);

		// Set the attributes array to the split data/files from the validator
		$this->data = $validation->getData();
		$this->files = $validation->getFiles();

		if ($validation->passes()) return true;

		$this->errors = $validation->messages();

		return false;
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
	 * Get the messages for the instance.
	 *
	 * @return \Illuminate\Support\MessageBag
	 */
	public function getMessageBag()
	{
		return $this->errors();
	}

	/**
	 * Return any errors.
	 * 
	 * @return \Illuminate\Support\MessageBag
	 */
	public function errors()
	{
		if ( ! $this->errors) $this->passes();

		return $this->errors;
	}

	/**
	 * Get the validaton rules within the context of the current validation.
	 *
	 * @return array|mixed
	 * @throws Exceptions\ValidatorContextException
	 */
	private function getRulesInContext()
	{
		if ( ! $this->hasContext())	return $this->rules;

		$rulesInContext = array_get($this->rules, self::DEFAULT_KEY, []);

		foreach ($this->contexts as $context)
		{
			if ( ! array_get($this->rules, $context))
			{
				throw new ValidatorContextException(
					sprintf(
						"'%s' does not contain the validation context '%s'", 
						get_called_class(), 
						$context
					)
				);
			}

			$rulesInContext = array_merge($rulesInContext, $this->rules[$context]);
		}

		return $rulesInContext;
	}

	/**
	 * Spin through our contextual rules array and bind any replacement
	 * values to placeholders within the rules.
	 *
	 * @param $rules
	 * @return mixed
	 * @throws Exceptions\ReplacementBindingException
	 */
	private function bindReplacements($rules)
	{
		foreach ($rules as $field => &$rule)
		{
			$replacements = $this->getReplacement($field);

			try
			{
				$rule = preg_replace_callback('/@(\w+)/', function($matches) use($replacements)
				{
					return $replacements[$matches[1]];
				}, $rule);
			}
			catch (\ErrorException $e)
			{
				$replacementCount = substr_count($rule, '@');

				throw new ReplacementBindingException(
					sprintf(
						"Invalid replacement count in rule '%s' for field '%s'; Expecting '%d' bound %s",
						$rule,
						$field,
						$replacementCount,
						str_plural('replacement', $replacementCount)
					)
				);
			}
		}

		return $rules;
	}

	/**
	 * Check if the current validation has a context.
	 * 
	 * @return boolean
	 */
	private function hasContext()
	{
		return (count($this->contexts) OR array_get($this->rules, self::DEFAULT_KEY));
	}
}
