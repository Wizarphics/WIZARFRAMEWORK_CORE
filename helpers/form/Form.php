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

class Form
{
    public static function begin(string $action, string $method, array $fieldAttributes)
    {
        $fieldAttributesStr = [];
        foreach ($fieldAttributes as $key => $value) {
            if (is_int($key))
                $fieldAttributesStr[$value] = "true";
            else
                $fieldAttributesStr[$key] = $value;
        }
        $addtionalFields = implode(" ", array_map(fn ($attr, $value) => "$attr = '$value'", array_keys($fieldAttributesStr), $fieldAttributesStr));
        echo sprintf('<form action="%s" method="%s" %s>
        %s
        ', $action, $method, $addtionalFields, csrfField());
        return new Form();
    }

    public static function end()
    {
        echo '</form>';
    }

    public function field(Model $model, $attribute, $addtionalField = [])
    {
        return new  InputField($model, $attribute, $addtionalField);
    }

    public function textArea(Model $model, $attribute)
    {
        return new TextAreaField($model, $attribute);
    }

    public function select(Model $model, $attribute, $options = [])
    {
        return new SelectField($model, $attribute, $options);
    }

    public function select_multiple(Model $model, $attribute, $options = [])
    {
        return $this->select($model, $attribute, $options)->multiple();
    }

    public function button(Model $model, string $attribute, $fieldAttributes = [])
    {
        return new ButtonField($model, $attribute, fieldAttributes: $fieldAttributes);
    }

    public function submit_btn(Model $model, string $attribute, $fieldAttributes = [])
    {
        return $this->button($model, $attribute, $fieldAttributes)->submit();
    }

    public function input_button(Model $model, string $attribute, array $fieldAttributes = [])
    {
        return $this->button($model, $attribute, $fieldAttributes)->as(ButtonField::AS_INPUT);
    }

    public function input_submit(Model $model, string $attribute, array $fieldAttributes = [])
    {
        return $this->input_button($model, $attribute, $fieldAttributes)->submit();
    }

    public function checkbox(Model $model, $attribute, $checkId = null)
    {
        return new CheckBoxField($model, $attribute, $checkId);
    }
}
