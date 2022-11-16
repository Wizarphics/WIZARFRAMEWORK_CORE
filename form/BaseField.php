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

namespace app\core\form;

use app\core\Model;

abstract class BaseField
{

    public Model $model;
    public string $attribute;
    public array $fieldAttributes;

    /**
     * @param Model $model
     * @param string $attribute
     * @param array|null $fieldAttributes
     */
    public function __construct(Model $model, string $attribute, array $fieldAttributes = [])
    {
        $this->model = $model;
        $this->attribute = $attribute;
        $this->fieldAttributes = $fieldAttributes;
    }
    abstract public function renderInput(): string;
    public function __toString()
    {
        return sprintf(
            '<div class="col-md-12 mb-3">
                <label class="form-label">%s</label>
                %s
                <div class="invalid-feedback">
                    %s
                </div>
            </div>',
            $this->model->getLabel($this->attribute),
            $this->renderInput(),
            $this->model->getFirstError($this->attribute)
        );
    }
}
