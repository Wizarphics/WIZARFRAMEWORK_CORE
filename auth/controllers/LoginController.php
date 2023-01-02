<?php

namespace wizarphics\wizarframework\auth\controllers;

use app\models\User;
use wizarphics\wizarframework\auth\middlewares\GuestMiddleWare;
use wizarphics\wizarframework\Controller as BaseController;
use wizarphics\wizarframework\http\Request;
use wizarphics\wizarframework\http\Response;
use wizarphics\wizarframework\interfaces\ResponseInterface;
use wizarphics\wizarframework\UserModel;

class LoginController extends BaseController
{
    protected UserModel $userModel;
    public function __construct()
    {
        $this->registerMiddleware(new GuestMiddleWare([
            'loadView', 'loginUserIn'
        ]));
        $this->setLayout('auth');
        $this->userModel = new (app()->userClass)();
    }
    public function loadView()
    {
        return $this->render('auth/login', [
            'model' => $this->userModel
        ]);
    }

    public function loginUserIn(Request $request, Response $response): ResponseInterface|String
    {
        $userModel = $this->userModel;
        $userModel->loadData($request->data());
        $valid = $userModel->validate(null, [
            'email' => [
                'rules' => ['required', 'valid_email', 'is_not_unique:users.email'],
                'errors' => [
                    'is_not_unique' => 'No user with the email provided exists.',
                ]
            ],
            'password' => ['required']
        ]);

        if ($valid) {
            $loginAttempt = auth()->attempt([
                'email' => $request->getVar('email'),
                'password' => $request->getVar('password'),
            ], (bool)$request->getVar('remberMe'));
            if ($loginAttempt->isOK()) {
                return redirect('/');
            } else {
                session()->setFlash('error', $loginAttempt->reason());
            }
        }


        return $this->render('auth/login', [
            'model' => $this->userModel
        ]);
    }

    public function logout()
    {
        auth()->logout();

        return redirect(route_to('login'));
    }
}
