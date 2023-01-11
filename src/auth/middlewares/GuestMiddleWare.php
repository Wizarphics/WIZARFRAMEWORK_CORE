<?php

namespace wizarphics\wizarframework\auth\middlewares;
use wizarphics\wizarframework\Application;
use wizarphics\wizarframework\middlewares\BaseMiddleWare;

class GuestMiddleWare extends BaseMiddleWare
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
        if (auth()->LoggedIn()) {
            if(empty($this->actions) || in_array(Application::$app->controller->action, $this->actions)){
                return redirect('/');
            }
        }
    }
}