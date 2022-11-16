<?php
/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: $file.created
 * 7/5/22, 10:15 AM
 * Last Modified at: 7/5/22, 10:15 AM
 * Time: 10:15
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace wizarphics\wizarframework\form;

use wizarphics\wizarframework\Model;

class InputField extends BaseField
{
    public const TYPE_TEXT = 'text';
    public const TYPE_PASSWORD = 'password';
    public const TYPE_NUMBER = 'number';
    public const TYPE_TEL = 'tel';
    public const TYPE_EMAIL = 'email';
    public const TYPE_DATE_TIME = 'datetime-local';
    public const TYPE_DATE = 'date';
    public const TYPE_TIME = 'time';
    public const TYPE_COLOR = 'color';

    public string $type;

    /**
     * @param Model $model
     * @param string $attribute
     * @param array|null $fieldAttributes
     */
    public function __construct(Model $model, string $attribute, array $fieldAttributes = [])
    {
        $this->type = self::TYPE_TEXT;
        parent::__construct($model, $attribute, $fieldAttributes);
    }

    public function emailField()
    {
        $this->type = self::TYPE_EMAIL;
        return $this;
    }

    public function passwordField()
    {
        $this->type = self::TYPE_PASSWORD;
        return $this;
    }

    public function numberField()
    {
        $this->type = self::TYPE_NUMBER;
        return  $this;
    }

    public function telField()
    {
        $this->type = self::TYPE_TEL;
        return $this;
    }

    public function colorField()
    {
        $this->type = self::TYPE_COLOR;
        return $this;
    }

    public function dateTime()
    {
        $this->type = self::TYPE_DATE_TIME;
        return  $this;
    }

    public function date()
    {
        $this->type = self::TYPE_DATE;
        return $this;
    }

    public function time()
    {
        $this->type = self::TYPE_TIME;
        return  $this;
    }
    /**
     * @return string
     */
    public function renderInput(): string
    {
        $fieldAttributes = [];

        foreach ($this->fieldAttributes as $key => $value) {
            if (is_int($key))
                $fieldAttributes[$value] = "true";
            else
                $fieldAttributes[$key] = $value;
        }

        $addtionalFields = implode(" AND ", array_map(fn ($attr, $value) => "$attr = $value", array_keys($fieldAttributes), $fieldAttributes));
        return sprintf(
            '<input type="%s" name="%s" %s value="%s" class="form-control %s">',
            $this->type,
            $this->attribute,
            $addtionalFields,
            $this->model->{$this->attribute},
            $this->model->hasError($this->attribute) ? 'is-invalid' : '',
        );
    }
}
