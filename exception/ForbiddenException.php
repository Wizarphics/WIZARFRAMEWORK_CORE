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
 * Class ForbiddenException
 *
 *@author Adeola Dev <wizarphics@gmail.com>
 *@package pp\core\exception
 */

class ForbiddenException extends \Exception
{
    protected $message = 'You don\'t have permission to access this page';
    protected $code = 403;
}
