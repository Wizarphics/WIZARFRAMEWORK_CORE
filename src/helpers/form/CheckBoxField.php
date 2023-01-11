<?php

/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: 17/11/22, 06:49 AM
 * Last Modified at: 17/11/22, 06:49 AM
 * Time: 06:49 AM
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */


namespace wizarphics\wizarframework\helpers\form;

use wizarphics\wizarframework\Model;

class CheckBoxField extends BaseField
{

    public Model $model;
    public string $attribute;

    public string $checkId;

    public string $role = 'checkbox';

    public string $className = 'form-check-input';
    public string $superClass = 'form-check';

    public string $type = 'checkbox';

    public string $value = '';

    public bool $labelBefore = false;

    public bool $isCheckable = true;

    public const TYPE_RANGE = 'range';

    /**
     * @param Model $model
     * @param string $attribute
     */
    public function __construct(Model $model, string $attribute, string $value, string $checkId = null)
    {
        $this->value = $value;
        $this->checkId = $checkId ?: $attribute;
        parent::__construct($model, $attribute);
    }

    public function __tostring(): string
    {
        return $this->labelBefore === false ? sprintf(
            '
        <div class="form-check %s col-md-12 mb-3">
            %s
            %s
            %s
            <label class="form-check-label" for="%s">
                %s
            </label>
        </div>
        ',
            $this->superClass,
            $this->beforeInput,
            $this->renderInput(),
            $this->afterInput,
            $this->checkId,
            $this->model->getLabel($this->attribute)
        ) : sprintf(
            '
        <div class="%s col-md-12 mb-3">
            <label class="form-check-label" for="%s">
                %s
            </label>
            %s
        </div>
        ',
            $this->superClass,
            $this->checkId,
            $this->model->getLabel($this->attribute),
            $this->renderInput(),
        );
    }

    /**
     * @return string
     */
    public function renderInput(): string
    {
        return sprintf(
            '<input class="%s %s" type="%s" name="%s" role="%s" value="%s" id="%s" "%s">',
            $this->className,
            $this->model->hasError($this->attribute) ? 'is-invalid' : '',
            $this->type,
            $this->attribute,
            $this->role,
            $this->value != null ? $this->value : $this->model->{$this->attribute},
            $this->checkId,
            $this->checkChecked()
        );
    }

    public function switch()
    {
        $this->superClass = 'form-switch';
        $this->role = 'switch';
        return $this;
    }

    public function radio(?string $value = '')
    {
        $this->type = $this->role = 'radio';
        $this->value = $value;
        return $this;
    }

    private function checkChecked(): string
    {
        $value = $this->value != null ? $this->value : $this->model->{$this->attribute};
        if ($value !== '' && $value = $this->model->{$this->attribute} && $this->isCheckable) {
            return 'checked';
        } else {
            return '';
        }
    }

    public function range()
    {
        $this->labelBefore = true;
        $this->type = $this->role = self::TYPE_RANGE;
        $this->className = 'form-range';
        $this->superClass = '';
        $this->isCheckable = false;
        return $this;
    }
}
