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

namespace wizarphics\wizarframework\middlewares;

use wizarphics\wizarframework\Application;
use wizarphics\wizarframework\exception\ForbiddenException;

/**
 * Class AuthMiddleware
 *
 *@author Adeola Dev <wizarphics@gmail.com>
 *@package wizarphics\wizarframework\middlewares
 */

class AuthMiddleWare extends BaseMiddleWare
{
    public array $actions = [];


    /**
     * @param array $actions 
     */
    public function __construct(array $actions = [])
    {
        $this->actions = $actions;
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        if (Application::isGuest()) {
            if(empty($this->actions) || in_array(Application::$app->controller->action, $this->actions)){
                throw new ForbiddenException();
            }
        }
    }
}
