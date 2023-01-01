<?php
/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: $file.created
 * 7/6/22, 1:20 PM
 * Last Modified at: 7/6/22, 1:20 PM
 * Time: 1:20
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace wizarphics\wizarframework;

use wizarphics\wizarframework\auth\Password;
use wizarphics\wizarframework\db\DbModel;
use wizarphics\wizarframework\interfaces\ValidationInterface;
use wizarphics\wizarframework\validation\Validation;

abstract class UserModel extends DbModel
{
    protected $passwordHandler;

    public function __construct(?ValidationInterface $validator = null)
    {
        $validator ??= new Validation;
        parent::__construct($validator);
        $this->passwordHandler = new Password();
    }

    public function __get(string $key)
    {
        return $this->{$key};
    }

    public function __set(string $key, mixed $value)
    {
        $this->{$key} = $value;
    }

    abstract public function getDisplayName(): string;
}
