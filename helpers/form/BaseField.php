<?php
/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: 16/11/22, 04:43 PM
 * Last Modified at: 16/11/22, 04:43 PM
 * Time: 04:43 PM
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace wizarphics\wizarframework\helpers\form;

use wizarphics\wizarframework\Model;

abstract class BaseField
{

    public Model $model;
    public string $attribute;
    public string $fieldAttributes;

    public string $id = '';

    public string $globalClass = '';
    public string $superClass = '';
    public string|false $afterInput = '';
    public string|false $beforeInput = '';

    /**
     * @param Model $model
     * @param string $attribute
     * @param array|null $fieldAttributes
     */
    public function __construct(Model $model, string $attribute, array $fieldAttributes = [])
    {
        $this->model = $model;
        $this->attribute = $attribute;
        $fieldAttributesStr = [];

        if (array_key_exists('id', $fieldAttributes)) {
            $this->id = $fieldAttributes['id'];
            unset($fieldAttributes['id']);
        }

        if (array_key_exists('class', $fieldAttributes)) {
            $this->globalClass .= ' ' . $fieldAttributes['class'];
            unset($fieldAttributes['class']);
        }

        if (array_key_exists('superClass', $fieldAttributes)) {
            $this->superClass .= ' ' . $fieldAttributes['superClass'];
            unset($fieldAttributes['superClass']);
        }

        foreach ($fieldAttributes as $key => $value) {
            if (is_int($key))
                $fieldAttributesStr[$value] = "true";
            else
                $fieldAttributesStr[$key] = $value;
        }
        $addtionalFields = implode(" ", array_map(fn ($attr, $value) => "$attr = '$value'", array_keys($fieldAttributesStr), $fieldAttributesStr));
        $this->fieldAttributes = $addtionalFields;
    }
    abstract public function renderInput(): string;
    public function __toString()
    {
        return sprintf(
            '<div class="col-md-12 mb-3 %s">
                <label class="form-label">%s</label>
                %s
                %s
                %s
                <div class="invalid-feedback">
                    %s
                </div>
            </div>',
            $this->superClass,
            $this->model->getLabel(rtrim($this->attribute, '[]')),
            $this->beforeInput,
            $this->renderInput(),
            $this->afterInput,
            $this->model->getFirstError($this->attribute)
        );
    }

    public function append($afterInput)
    {
        $this->afterInput = $afterInput;
        return $this;
    }

    public function prepend($beforeInput)
    {
        $this->beforeInput = $beforeInput;
        return $this;
    }
}
