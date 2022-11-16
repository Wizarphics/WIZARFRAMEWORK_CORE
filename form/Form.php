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

class Form
{
    public static function begin($action, $method)
    {
        echo sprintf('<form action="%s" method="%s">',$action, $method );
        return new Form();
    }

    public static function end()
    {
        echo '</form>';
    }

    public function field(Model $model, $attribute, $addtionalField=[])
    {
        return new  InputField($model, $attribute, $addtionalField);
    }

    public function textArea(Model $model, $attribute){
        return new TextAreaField($model, $attribute);
    }
}