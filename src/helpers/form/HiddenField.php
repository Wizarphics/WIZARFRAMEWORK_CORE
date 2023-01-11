<?php

/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: 18/11/22, 11:38 PM
 * Last Modified at: 18/11/22, 11:38 PM
 * Time: 11:38 PM
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */


namespace wizarphics\wizarframework\helpers\form;

use wizarphics\wizarframework\Model;

class HiddenField extends BaseField
{

    public string $attribute;
    public string $fieldAttributes;
    protected string $value;
    /**
     */
    public function __construct(string $attribute, ?string $value, array $fieldAttributes = [])
    {
        $this->attribute = $attribute;
        $this->value = $value;
        $fieldAttributesStr = [];

        if (array_key_exists('id', $fieldAttributes)) {
            $this->id = $fieldAttributes['id'];
            unset($fieldAttributes['id']);
        }

        if (array_key_exists('class', $fieldAttributes)) {
            $this->globalClass .= ' ' . $fieldAttributes['class'];
            unset($fieldAttributes['class']);
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

    public function __tostring()
    {
        return sprintf(
            "
            %s
            %s
            %s
            ",
            $this->beforeInput,
            $this->renderInput(),
            $this->afterInput
        );
    }

    /**
     * @return string
     */
    public function renderInput(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" %s value="%s">',
            $this->attribute,
            $this->fieldAttributes,
            $this->value
        );
    }
}