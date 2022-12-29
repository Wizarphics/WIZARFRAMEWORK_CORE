<?php

namespace wizarphics\wizarframework\auth;

use app\models\User;
use wizarphics\wizarframework\Application;
use wizarphics\wizarframework\UserModel;

class Authentication
{
    public function isGuest()
    {
        return Application::isGuest();
    }

    public function user():UserModel|User
    {
        return Application::$app->user;
    }
}
