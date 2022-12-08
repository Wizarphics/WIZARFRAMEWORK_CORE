<?php

namespace wizarphics\wizarframework\auth;

use wizarphics\wizarframework\Application;
use wizarphics\wizarframework\UserModel;

class Authentication
{
    public function isGuest()
    {
        return Application::isGuest();
    }

    public function user():UserModel
    {
        return Application::$app->user;
    }
}
