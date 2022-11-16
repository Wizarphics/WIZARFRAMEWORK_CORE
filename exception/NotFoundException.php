<?php


/*
 * Copyright (c) 2022.
 * User: Wizarphics
 * project: WizarFrameWork
 * Date Created: 16/11/22, 3:30 PM
 * Last Modified at: 16/11/22, 3:30 PM
 * Time: 3:30 PM
 * @author Adeola Dev <wizarphics@gmail.com>
 *
 */

namespace wizarphics\wizarframework\exception;

/**
 * Class NotFoundException
 *
 *@author Adeola Dev <wizarphics@gmail.com>
 *@package pp\core\exception
 */

class NotFoundException extends \Exception
{
    protected $message = 'Resource Not Found.';
    protected $code = 404;
}
