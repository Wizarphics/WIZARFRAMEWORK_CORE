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
use wizarphics\wizarframework\validation\Validation;

#{AllowDynamicProperties}
abstract class Model
{
    public const RULE_REQUIRED = 'required';
    public const RULE_EMAIL = 'valid_email';
    public const RULE_MATCH = 'matches';
    public const RULE_UNIQUE = 'is_unique';
    public const RULE_ALPHA = 'alpha';
    public const RULE_ALPHA_SPACE = 'alpha_space';
    public const RULE_ALPHA_NUM = 'alpha_numeric';
    public const RULE_MIN = 'min_length';
    public const RULE_MAX = 'max_length';
    public array $errors = [];

    protected ValidationInterface $validator;

    /**
     * Class constructor.
     */
    public function __construct(?ValidationInterface $validator = null)
    {
        $validator ??= new Validation;
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
            // if (property_exists($this, $key)) {
            $this->{$key} = $value;
            // }
        }
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
        if ($this->validator->validate($data, $rules)) {
            return true;
        } else {
            $this->setErrors($this->validator->getErrors());
            return false;
        };
    }

    private function setErrors(array $errors): void
    {
        array_walk($errors, function (&$error, $field) {
            $error = str_replace($field, $this->getLabel($field), $error);
        });
        $this->errors = $errors;
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
