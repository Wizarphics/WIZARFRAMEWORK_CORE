<?php
/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: $file.created
 * 7/3/22, 4:02 AM
 * Last Modified at: 7/3/22, 4:02 AM
 * Time: 4:2
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace wizarphics\wizarframework;

use wizarphics\wizarframework\interfaces\ValidationInterface;

#{AllowDynamicProperties}
abstract class Model
{
    public const RULE_REQUIRED = 'required';
    public const RULE_EMAIL = 'email';
    public const RULE_MIN = 'min';
    public const RULE_MAX = 'max';
    public const RULE_MATCH = 'match';
    public const RULE_UNIQUE = 'unique';
    public array $errors = [];

    protected ValidationInterface $validator;

    /**
     * Class constructor.
     */
    public function __construct(ValidationInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * [Description for loadData]
     *
     * @param mixed $data
     * 
     * @return void
     * 
     * Created at: 11/24/2022, 2:55:45 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function loadData($data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * [Description for validate]
     *
     * @return bool
     * 
     * Created at: 11/24/2022, 2:55:51 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function validatee(): bool
    {
        if (!Csrf::verify(Application::$app->request)) {
            // $this->addError(csrf::tokenFieldName, 'Invalid Request Csrf Token is invalid or missing.');
            session()->setFlash('error', 'Invalid Request Csrf Token is invalid or missing.');

            return false;
        };
        foreach ($this->rules() as $attribute => $rules) {
            $value = $this->{$attribute};
            foreach ($rules as $rule) {
                $ruleName = $rule;
                if (!is_string($ruleName)) {
                    $ruleName = $rule[0];
                }
                if ($ruleName === self::RULE_REQUIRED && !$value) {
                    $this->addErrorFrorRule($attribute, self::RULE_REQUIRED);
                }
                if ($ruleName === self::RULE_EMAIL && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addErrorFrorRule($attribute, self::RULE_EMAIL);
                }
                if ($ruleName === self::RULE_MIN && strlen($value) < $rule['min']) {
                    $this->addErrorFrorRule($attribute, self::RULE_MIN, $rule);
                }
                if ($ruleName === self::RULE_MAX && strlen($value) > $rule['max']) {
                    $this->addErrorFrorRule($attribute, self::RULE_MAX, $rule);
                }
                if ($ruleName === self::RULE_MATCH && $value !== $this->{$rule['match']}) {
                    $rule['match'] = $this->getLabel($rule['match']);
                    $this->addErrorFrorRule($attribute, self::RULE_MATCH, $rule);
                }
                if ($ruleName === self::RULE_UNIQUE) {
                    $className = $rule['class'];
                    $uniqueAttribute = $rule['attribute'] ?? $attribute;
                    $tableName = $className::tableName();
                    $SQL = "SELECT * FROM $tableName WHERE $uniqueAttribute = :attr";
                    $statement = Application::$app->db->prepare($SQL);
                    $statement->bindValue(":attr", $value);
                    $statement->execute();
                    $record = $statement->fetchObject();
                    if ($record) {
                        $this->addErrorFrorRule($attribute, self::RULE_UNIQUE, ['field' => $this->getLabel($attribute)]);
                    }
                }
            }
        }

        return empty($this->errors);
    }

    public function validate(?array $data = null, ?array $rules = null): bool
    {
        if (!Csrf::verify(Application::$app->request)) {
            // $this->addError(csrf::tokenFieldName, 'Invalid Request Csrf Token is invalid or missing.');
            session()->setFlash('error', 'Invalid Request Csrf Token is invalid or missing.');

            return false;
        };

        $data ??= get_object_vars($this);
        $rules ??= $this->rules();

        return $this->validator->validate($data, $rules);
    }

    /**
     * [Description for rules]
     *
     * @return array
     * 
     * Created at: 11/24/2022, 2:55:58 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    abstract public function rules(): array;

    /**
     * [Description for labels]
     *
     * @return array
     * 
     * Created at: 11/24/2022, 2:56:03 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function labels(): array
    {
        return [];
    }
    /**
     * [Description for addErrorFrorRule]
     *
     * @param string $attribute
     * @param string $rule
     * @param array $params
     * 
     * @return void
     * 
     * Created at: 11/24/2022, 2:56:21 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    private function addErrorFrorRule(string $attribute, string $rule, $params = []): void
    {
        $message = $this->errorMessages()[$rule] ?? '';
        $params = array_unique(array_merge($params, ['field' => $this->getLabel($attribute), 'value' => $this->{$attribute}]));
        foreach ($params as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }
        $this->errors[$attribute][] = $message;
    }

    /**
     * [Description for addError]
     *
     * @param string $attribute
     * @param string $message
     * 
     * @return void
     * 
     * Created at: 11/24/2022, 2:56:29 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function addError(string $attribute, string $message): void
    {
        $this->errors[$attribute][] = $message;
    }

    /**
     * [Description for errorMessages]
     *
     * @return array
     * 
     * Created at: 11/24/2022, 2:56:37 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function errorMessages(): array
    {
        return [
            self::RULE_REQUIRED => 'This {field} field is required',
            self::RULE_EMAIL => 'This {field} field must be a valid email address',
            self::RULE_MATCH => 'This {field} field must be the same as {match}',
            self::RULE_MAX => 'Max length of this {field} field must be {max}',
            self::RULE_MIN => 'Min length of this {field} field must be {min}',
            self::RULE_UNIQUE => 'Record with this {field} field already exists',
        ];
    }

    /**
     * [Description for getLabel]
     *
     * @param mixed $attribute
     * 
     * @return string
     * 
     * Created at: 11/24/2022, 2:56:53 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getLabel($attribute): string
    {
        return $this->labels()[$attribute] ?? $attribute;
    }

    /**
     * [Description for hasError]
     *
     * @param mixed $attribute
     * 
     * @return bool|string|array
     * 
     * Created at: 11/24/2022, 2:57:47 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function hasError($attribute): bool|array|string
    {
        return $this->errors[$attribute] ?? false;
    }

    /**
     * [Description for getFirstError]
     *
     * @param mixed $attribute
     * 
     * @return string|false
     * 
     * Created at: 11/24/2022, 2:58:06 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getFirstError($attribute): string|false
    {
        return $this->errors[$attribute][0] ?? false;
    }
}
