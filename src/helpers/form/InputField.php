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

namespace wizarphics\wizarframework\helpers\form;

use wizarphics\wizarframework\Model;

class InputField extends BaseField
{
    public const TYPE_TEXT = 'text';
    public const TYPE_PASSWORD = 'password';
    public const TYPE_NUMBER = 'number';
    public const TYPE_CHECKBOX = 'checkbox';
    public const TYPE_TEL = 'tel';
    public const TYPE_EMAIL = 'email';
    public const TYPE_DATE_TIME = 'datetime-local';
    public const TYPE_DATE = 'date';
    public const TYPE_TIME = 'time';
    public const TYPE_COLOR = 'color';

    public const TYPE_SEARCH = 'search';

    public const TYPE_FILE = 'file';

    public string $type;

    public string $className = 'form-control';

    /**
     * @param Model $model
     * @param string $attribute
     * @param array|null $fieldAttributes
     */
    public function __construct(Model $model, string $attribute, array $fieldAttributes = [])
    {
        $this->type = self::TYPE_TEXT;
        parent::__construct($model, $attribute, $fieldAttributes);
        $class= $this->className.' '.$this->globalClass;
        $this->globalClass = $class;
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

    public function fileField()
    {
        $this->type = self::TYPE_FILE;
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


    public function search()
    {
        $this->type = self::TYPE_SEARCH;
        return  $this;
    }

    /**
     * @return string
     */
    public function renderInput(): string
    {
        $attribute = rtrim($this->attribute, '[]');
        return sprintf(
            '<input type="%s" name="%s" %s value="%s" class="%s %s">',
            $this->type,
            $this->attribute,
            $this->fieldAttributes,
            $this->model->{$attribute},
            $this->globalClass,
            $this->model->hasError($this->attribute) ? 'is-invalid' : '',
        );
    }
}
