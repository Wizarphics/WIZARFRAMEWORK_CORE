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

use wizarphics\wizarframework\db\DbModel;

abstract class UserModel extends DbModel
{
    protected $passwordHandler;

    public function __construct()
    {
        $this->passwordHandler = new PasswordHandler();
    }

    abstract public function getDisplayName(): string;
}