<?php

namespace wizarphics\wizarframework\validation;

use wizarphics\wizarframework\interfaces\ValidationInterface;

class Validation implements ValidationInterface
{

	public const RULE_REQUIRED = 'required';
	public const RULE_ALPHA = 'alpha';
	public const RULE_ALPHA_SPACE = 'alpha_space';
	public const RULE_ALPHA_NUM = 'alpha_numeric';
	public const RULE_MIN = 'min_length';
	public const RULE_MAX = 'max_length';


	/**
	 * Class constructor.
	 */
	public function __construct()
	{
	}
	/**
	 * Runs the validation process, returning true/false determining whether
	 * or not validation was successful.
	 *
	 * @param array|null $data The array of data to validate.
	 * @param null|string $rule The rules to apply.
	 * @return bool
	 */
	public function validate(array $data = null, array $rules = null): bool
	{
		return true;
	}

	/**
	 * Check; runs the validation process, returning true or false
	 * determining whether or not validation was successful.
	 *
	 * @param array|bool|float|int|null|object|string $value Value to validate.
	 * @param string $rule
	 * @param array<string> $errors
	 * @return bool True if valid, else false.
	 */
	public function check($value, string $rule, array $errors = array()): bool
	{
	}

	/**
	 * Takes a Request object and grabs the input data to use from its
	 * array values.
	 *
	 * @param \wizarphics\wizarframework\traits\RequestInterface $request
	 * @return ValidationInterface
	 */
	public function useRequest(\wizarphics\wizarframework\traits\RequestInterface $request): ValidationInterface
	{
	}

	/**
	 * Stores the rules that should be used to validate the items.
	 *
	 * @param array $rules
	 * @param array $messages
	 * @return ValidationInterface
	 */
	public function setRules(array $rules, array $messages = array()): ValidationInterface
	{
	}

	/**
	 * Checks to see if the rule for key $field has been set or not.
	 *
	 * @param string $field
	 * @return bool
	 */
	public function hasRule(string $field): bool
	{
	}

	/**
	 * Returns the error for a specified $field (or empty string if not set).
	 *
	 * @param string $field
	 * @return string
	 */
	public function getError(string $field): string
	{
	}

	/**
	 * Returns the array of errors that were encountered during
	 * a run() call. The array should be in the following format:
	 *
	 * [
	 * 'field1' => 'error message',
	 * 'field2' => 'error message',
	 * ]
	 * @return array<string>
	 */
	public function getErrors(): array
	{
	}

	/**
	 * Sets the error for a specific field. Used by custom validation methods.
	 *
	 * @param string $rule
	 * @param array $params
	 * @return ValidationInterface
	 */
	public function addError(string $rule, array $params = array()): ValidationInterface
	{
	}

	/**
	 * Resets the class to a blank slate. Should be called whenever
	 * you need to process more than one array.
	 * @return ValidationInterface
	 */
	public function reset(): ValidationInterface
	{
	}
}
