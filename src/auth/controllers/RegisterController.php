<?php

namespace wizarphics\wizarframework\auth\controllers;

use app\models\User;
use wizarphics\wizarframework\Controller as BaseController;
use wizarphics\wizarframework\http\Request;
use wizarphics\wizarframework\http\Response;
use wizarphics\wizarframework\UserModel;

class RegisterController extends BaseController
{

    public UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new (app()->userClass)();
    }

    public function loadView(Request $request, Response $response)
    { {
            if (auth()->isLoggedIn()) {
                return redirect(route_to('/'));
            }

            $userModel = $this->userModel;
            $this->setLayout('auth');

            return $this->render('auth/register', [
                'model' => $userModel
            ]);
        }
    }

    public function registerUser(Request $request, Response $response)
    {

        $userModel = $this->userModel;
        $this->setLayout('auth');
        if ($request->isPost()) {
            $userModel->loadData($request->postData());
            if ($userModel->validate() && $userModel->save()) {
                session()->setFlash('success', 'Thanks for registering');
                return $response->redirect("/auth/login");
            }
        }

        return $this->render('auth/register', [
            'model' => $userModel
        ]);
    }
}
