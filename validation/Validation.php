<?php

namespace wizarphics\wizarframework\validation;

use InvalidArgumentException;
use RuntimeException;
use TypeError;
use wizarphics\wizarframework\http\Request;
use wizarphics\wizarframework\interfaces\RequestInterface;
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
	 * Files to load with validation functions.
	 *
	 * @var array
	 */
	protected $ruleSetFiles;

	/**
	 * The loaded instances of our validation files.
	 *
	 * @var array
	 */
	protected $ruleSetInstances = [];

	/**
	 * Stores the actual rules that should
	 * be ran against $data.
	 *
	 * @var array
	 */
	protected $rules = [];

	/**
	 * The data that should be validated,
	 * where 'key' is the alias, with value.
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * Any generated errors during validation.
	 * 'key' is the alias, 'value' is the message.
	 *
	 * @var array
	 */
	protected $errors = [];

	/**
	 * Stores custom error message to use
	 * during validation. Where 'key' is the alias.
	 *
	 * @var array
	 */
	protected $customErrors = [];

	/**
	 * Class constructor.
	 */
	public function __construct(?array $ruleSets = null)
	{
		$ruleSets ??= [];
		$this->ruleSetFiles = array_merge([
			GeneralRules::class,
			BasicFormatRules::class
		], $ruleSets);
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
		$data ??= $this->data;
		$this->requireRules();
		if (null != $rules) {
			$this->setRules($rules);
		}

		$this->rules = $this->replacePlaceholders($data);


		foreach ($this->rules as $field => $ruleSetup) {
			$rules = $ruleSetup['rules'] ?? $ruleSetup;

			if (is_string($rules)) {
				$rules = $this->arrayFromRules($rules);
			}

			$values = $data[$field] ?? [];
			if ($values === []) {
				// We'll process the values right away if an empty array
				$this->run($field, $ruleSetup['label'] ?? $field, $values, $rules, $data);

				continue;
			}

			// Process single field
			$this->run($field, $ruleSetup['label'] ?? $field, $values, $rules, $data);
		}

		return $this->getErrors() === [];
	}

	/**
	 * Runs all of $rules against $field, until one fails, or
	 * all of them have been processed. If one fails, it adds
	 * the error to $this->errors and moves on to the next,
	 * so that we can collect all of the first errors.
	 *
	 * @param array|string $value
	 * @param array|null   $rules
	 * @param array        $data          The array of data to validate
	 */
	protected function run(string $field, ?string $label, $value, $rules = null, ?array $data = null): bool
	{
		if ($data === null) {
			throw new InvalidArgumentException('You must supply the parameter: data.');
		}


		foreach ($rules as $rule) {
			$isCallable = is_callable($rule);

			$passed = false;
			$param  = false;

			if (!$isCallable && preg_match('/(.*?)\:(.*)/', $rule, $match)) {
				$rule  = $match[1];
				$param = $match[2];
			}

			// Placeholder for custom errors from the rules.
			$error = null;

			// If it's a callable, call and get out of here.
			if ($isCallable) {
				$passed = $param === false ? $rule($value) : $rule($value, $param, $data);
			} else {

				$found = false;
				// Check in our rulesets
				foreach ($this->ruleSetInstances as $set) {
					if (!method_exists($set, $rule)) {
						continue;
					}

					$found  = true;
					$passed = $param === false
						? $set->{$rule}($value, $error)
						: $set->{$rule}($value, $param, $data, $error, $field);

					break;
				}

				// If the rule wasn't found anywhere, we
				// should throw an exception so the developer can find it.
				if (!$found) {
					throw new RuntimeException(__('Validation.ruleNotFound', [$rule]));
				}
			}

			// Set the error message if we didn't survive.
			if ($passed === false) {
				// if the $value is an array, convert it to as string representation
				if (is_array($value)) {
					$value = $this->isStringList($value)
						? '[' . implode(', ', $value) . ']'
						: json_encode($value);
				} elseif (is_object($value)) {
					$value = json_encode($value);
				}

				$param = ($param === false) ? '' : $param;
				$value = (string) $value;

				$this->addError($field, $rule, compact([
					'field', 'param', 'value',
				]));

				return false;
			}
		}

		return true;
	}

	/**
	 * Is the array a string list `list<string>`?
	 */
	private function isStringList(array $array): bool
	{
		$expectedKey = 0;

		foreach ($array as $key => $val) {
			// Note: also covers PHP array key conversion, e.g. '5' and 5.1 both become 5
			if (!is_int($key)) {
				return false;
			}

			if ($key !== $expectedKey) {
				return false;
			}
			$expectedKey++;

			if (!is_string($val)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * convert rules string with pipe operator into an array.
	 */
	public function arrayFromRules(string $rules): array
	{
		if (strpos($rules, '|') === false) {
			return [$rules];
		}

		$string = $rules;
		$rules  = [];
		$length = strlen($string);
		$cursor = 0;

		while ($cursor < $length) {
			$pos = strpos($string, '|', $cursor);

			if ($pos === false) {
				// we're in the last rule
				$pos = $length;
			}

			$rule = substr($string, $cursor, $pos - $cursor);

			while (
				(substr_count($rule, '[') - substr_count($rule, '\['))
				!== (substr_count($rule, ']') - substr_count($rule, '\]'))
			) {
				// the pipe is inside the brackets causing the closing bracket to
				// not be included. so, we adjust the rule to include that portion.
				$pos  = strpos($string, '|', $cursor + strlen($rule) + 1) ?: $length;
				$rule = substr($string, $cursor, $pos - $cursor);
			}

			$rules[] = $rule;
			$cursor += strlen($rule) + 1; // +1 to exclude the pipe
		}

		return array_unique($rules);
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
		$this->reset();

		return $this->setRule('check', null, $rule, $errors)->validate(['check' => $value]);
	}

	/**
	 * Takes a Request object and grabs the input data to use from its
	 * array values.
	 *
	 * @param RequestInterface $request
	 * @return ValidationInterface
	 */
	public function useRequest(RequestInterface $request): ValidationInterface
	{
		/** @var Request $request */
		if (strpos($request->getHeaderLine('Content-Type'), 'application/json') !== false) {
			$this->data = $request->getJSON(true);

			return $this;
		}

		if (
			in_array(strtolower($request->Method()), ['put', 'patch', 'delete'], true)
			&& strpos($request->getHeaderLine('Content-Type'), 'multipart/form-data') === false
		) {
			$this->data = $request->RawInput();
		} else {
			$this->data = $request->getVar() ?? [];
		}

		return $this;
	}


	/**
	 * Sets an individual rule and custom error messages for a single field.
	 *
	 * The custom error message should be just the messages that apply to
	 * this field, like so:
	 *
	 *    [
	 *        'rule' => 'message',
	 *        'rule' => 'message'
	 *    ]
	 *
	 * @param array|string $rules
	 *
	 * @return $this
	 *
	 * @throws TypeError
	 */
	public function setRule(string $field, ?string $label, $rules, array $errors = [])
	{
		if (!is_array($rules) && !is_string($rules)) {
			throw new TypeError('$rules must be of type string|array');
		}

		$ruleSet = [
			$field => [
				'label' => $label,
				'rules' => $rules,
			],
		];

		if ($errors) {
			$ruleSet[$field]['errors'] = $errors;
		}

		$this->setRules($ruleSet + $this->getRules(), $this->customErrors);

		return $this;
	}

	/**
	 * Stores the rules that should be used to validate the items.
	 * Rules should be an array formatted like:
	 *
	 *    [
	 *        'field' => 'rule1|rule2'
	 *    ]
	 *
	 * @param array $errors // An array of custom error messages
	 */
	public function setRules(array $rules, array $errors = []): ValidationInterface
	{
		$this->customErrors = $errors;

		foreach ($rules as $field => &$rule) {
			if (is_array($rule)) {
				if (array_key_exists('errors', $rule)) {
					$this->customErrors[$field] = $rule['errors'];
					unset($rule['errors']);
				}

				// if $rule is already a rule collection, just move it to "rules"
				// transforming [foo => [required, foobar]] to [foo => [rules => [required, foobar]]]
				if (!array_key_exists('rules', $rule)) {
					$rule = ['rules' => $rule];
				}
			}
		}

		$this->rules = $rules;

		return $this;
	}


	/**
	 * Replace any placeholders within the rules with the values that
	 * match the 'key' of any properties being set. For example, if
	 * we had the following $data array:
	 *
	 * [ 'id' => 13 ]
	 *
	 * and the following rule:
	 *
	 *  'required|is_unique[users,email,id,{id}]'
	 *
	 * The value of {id} would be replaced with the actual id in the form data:
	 *
	 *  'required|is_unique[users,email,id,13]'
	 */
	protected function replacePlaceholders(array $data): array
	{
		$rules = $this->rules;
		$replacements = [];

		foreach ($data as $key => $value) {
			$replacements["{{$key}}"] = $value;
		}

		if ($replacements !== []) {
			foreach ($rules as &$rule) {
				$ruleSet = $rule['rules'] ?? $rule;

				if (is_array($ruleSet)) {
					foreach ($ruleSet as &$row) {
						if (is_string($row)) {
							$row = strtr($row, $replacements);
						}
					}
				}

				if (is_string($ruleSet)) {
					$ruleSet = strtr($ruleSet, $replacements);
				}

				if (isset($rule['rules'])) {
					$rule['rules'] = $ruleSet;
				} else {
					$rule = $ruleSet;
				}
			}
		}

		return $rules;
	}

	/**
	  * Returns all of the rules currently defined.
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Checks to see if the rule for key $field has been set or not.
     */
    public function hasRule(string $field): bool
    {
        return array_key_exists($field, $this->rules);
    }

	/**
     * Checks to see if an error exists for the given field.
     */
    public function hasError(string $field): bool
    {
        $pattern = '/^' . str_replace('\.\*', '\..+', preg_quote($field, '/')) . '$/';

        return (bool) preg_grep($pattern, array_keys($this->getErrors()));
    }

    /**
     * Returns the error(s) for a specified $field (or empty string if not
     * set).
     */
    public function getError(?string $field = null): string
    {
        if ($field === null && count($this->rules) === 1) {
            $field = array_key_first($this->rules);
        }

        $errors = array_filter($this->getErrors(), static fn ($key) => preg_match(
            '/^' . str_replace(['\.\*', '\*\.'], ['\..+', '.+\.'], preg_quote($field, '/')) . '$/',
            $key
        ), ARRAY_FILTER_USE_KEY);

        return $errors === [] ? '' : implode("\n", $errors);
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
		return $this->errors ?? [];
	}

	/**
	 * Sets the error for a specific field. Used by custom validation methods.
	 *
	 * @param string $rule
	 * @param array $params
	 * @return ValidationInterface
	 */
	public function addError(string $field, string $rule, array $params = array()): ValidationInterface
	{
		$message = $this->getErrorMessage($rule, ...$params);
		$this->errors[$field][] = $message;
		return $this;
	}


	/**
	 * Attempts to find the appropriate error message
	 *
	 * @param string|null $value The value that caused the validation to fail.
	 */
	protected function getErrorMessage(
		string $rule,
		string $field,
		?string $label = null,
		?string $param = null,
		?string $value = null,
		?string $originalField = null
	): string {
		$param ??= '';

		// Check if custom message has been defined by user
		if (isset($this->customErrors[$field][$rule])) {
			$message = __($this->customErrors[$field][$rule]);
		} elseif (null !== $originalField && isset($this->customErrors[$originalField][$rule])) {
			$message = __($this->customErrors[$originalField][$rule]);
		} else {
			// Try to grab a localized version of the message...
			// lang() will return the rule name back if not found,
			// so there will always be a string being returned.
			$message = __('Validation.' . $rule);
		}

		$message = str_replace('{field}', empty($label) ? $field : __($label), $message);
		$message = str_replace(
			'{param}',
			empty($this->rules[$param]['label']) ? $param : __($this->rules[$param]['label']),
			$message
		);

		return str_replace('{value}', $value ?? '', $message);
	}

	/**
	 * Loads all of the rulesets classes that have been defined in the
	 * Config\Validation and stores them locally so we can use them.
	 */
	protected function requireRules()
	{
		if (empty($this->ruleSetFiles)) {
			throw new RuntimeException(__('Validation.noRuleSets'));
		}

		foreach ($this->ruleSetFiles as $file) {
			$this->ruleSetInstances[] = new $file();
		}
	}

	/**
	 * Resets the class to a blank slate. Should be called whenever
	 * you need to process more than one array.
	 * @return ValidationInterface
	 */
	public function reset(): ValidationInterface
	{
		$this->data         = [];
		$this->rules        = [];
		$this->errors       = [];
		$this->customErrors = [];

		return $this;
	}
}
