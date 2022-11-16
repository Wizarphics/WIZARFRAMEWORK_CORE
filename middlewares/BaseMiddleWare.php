<?php


/*
 * Copyright (c) 2022.
 * User: Wizarphics
 * project: WizarFrameWork
 * Date Created: 16/11/22, 3:16 PM
 * Last Modified at: 16/11/22, 3:16 PM
 * Time: 3:16 PM
 * @author Adeola Dev <wizarphics@gmail.com>
 *
 */

namespace app\core\middlewares;

/**
 * Class BaseMiddleware
 *
 *@author Adeola Dev <wizarphics@gmail.com>
 *@package app\core\middlewares
 */

abstract class BaseMiddleware
{
    abstract public function execute();
}
