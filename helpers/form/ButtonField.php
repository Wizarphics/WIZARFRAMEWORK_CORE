<?php
/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: 18/11/22, 12:20 AM
 * Last Modified at: 16/11/22, 12:20 AM
 * Time: 12:20 AM
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace wizarphics\wizarframework\helpers\form;

use wizarphics\wizarframework\Model;

class ButtonField extends BaseField
{

    public const TYPE_BUTTON = 'button';
    public const TYPE_SUBMIT = 'submit';

    public const AS_BUTTON = 'button';
    public const AS_INPUT = 'input';

    public string $type;

    public string $as;


    public function __construct(Model $model, string $attribute, array $fieldAttributes = [])
    {
        $this->type = self::TYPE_BUTTON;
        $this->as = self::AS_BUTTON;
        parent::__construct($model, $attribute, $fieldAttributes);
        $class = 'btn btn-primary ' . $this->globalClass;
        $this->globalClass = $class;
    }

    public function __toString()
    {
        return sprintf(
            '<div class="col-md-12 mb-3">
                %s
            </div>',
            $this->as == self::AS_INPUT ? $this->renderInput() : $this->renderButton(),
        );
    }

    /**
     * @return string
     */
    public function renderInput(): string
    {
        return sprintf(
            '<input type="%s" class="%s" %s name="%s" value="%s">',
            $this->type,
            $this->globalClass,
            $this->fieldAttributes,
            $this->attribute,
            $this->model->getLabel($this->attribute),
        );
    }

    public function renderButton(): string
    {
        return sprintf(
            '<button type="%s" class="%s" %s name="%s">%s</button>',
            $this->type,
            $this->globalClass,
            $this->fieldAttributes,
            $this->attribute,
            $this->model->getLabel($this->attribute),
        );
    }

    public function submit()
    {
        $this->type = self::TYPE_SUBMIT;
        return $this;
    }

    public function as(string $format)
    {
        $this->as = $format;
        return $this;
    }
}
