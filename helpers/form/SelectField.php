<?php

/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: 15/11/22, 8:10 AM
 * Last Modified at: 15/11/22, 8:10 AM
 * Time: 8:10
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace wizarphics\wizarframework\helpers\form;

use wizarphics\wizarframework\Model;

class SelectField extends BaseField
{

    public array $options = [];
    public bool $multiple = false;
    /**
     * @param Model $model
     * @param string $attribute
     */
    public function __construct(Model $model, string $attribute, array $options = [])
    {
        $this->options = $options;
        parent::__construct($model, $attribute);
        $class = 'form-select '.$this->globalClass;
        $this->globalClass = $class;
    }

    public function __tostring(): string
    {
        return sprintf(
            '
        <div class="col-md-12 mb-3">
            <label class="form-label" %s>
                %s
            </label>
            %s
        </div>
        ',
            $this->id,
            $this->model->getLabel(rtrim($this->attribute, '[]')),
            $this->renderInput(),
        );
    }

    /**
     * @return string
     */
    public function renderInput(): string
    {
        return sprintf(
            '<select class="%s %s" %s name="%s" %s>
                %s
            </select>',
            $this->globalClass,
            $this->model->hasError($this->attribute) ? 'is-invalid' : '',
            $this->multiple ? 'multiple' : '',
            $this->attribute,
            $this->id,
            $this->renderOptions()
        );
    }

    public function renderOptions(): string
    {
        $options = $this->options;
        $optionString = '';
        $attribute = rtrim($this->attribute, '[]');
        $selected = $this->model->{$attribute};
        foreach ($options as $key => $val) {
            // Keys should always be strings for strict comparison
            $key = (string) $key;

            if (is_array($val)) {
                if (empty($val)) {
                    continue;
                }

                $optionString .= '<optgroup label="' . $key . "\">\n";

                foreach ($val as $optgroupKey => $optgroupVal) {
                    // Keys should always be strings for strict comparison
                    $optgroupKey = (string) $optgroupKey;

                    if ($this->multiple == true || is_array($selected)) {
                        $sel = in_array($optgroupKey, $selected) ? ' selected' : '';
                    } else {
                        $sel = ($optgroupKey === $selected) ? ' selected="selected"' : '';
                    }
                    $optionString .= '<option value="' . htmlspecialchars($optgroupKey) . '"' . $sel . '>' . $optgroupVal . "</option>\n";
                }
                $optionString .= "</optgroup>\n";
            } else {
                $optionString .= '<option value="' . htmlspecialchars($key) . '"'
                    . (($key === $selected) ? ' selected="selected"' : '') . '>'
                    . $val . "</option>\n";
            }
        }
        return  $optionString;
    }
    public function multiple()
    {
        $this->multiple = true;
        return $this;
    }
}
