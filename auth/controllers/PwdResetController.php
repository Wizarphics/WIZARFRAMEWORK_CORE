<?php

namespace wizarphics\wizarframework\auth\controllers;

use app\models\User;
use wizarphics\wizarframework\Application;
use wizarphics\wizarframework\auth\models\PwdResetModel;
use wizarphics\wizarframework\auth\Password;
use wizarphics\wizarframework\http\Request;
use wizarphics\wizarframework\http\Response;
use wizarphics\wizarframework\UserModel;
use wizarphics\wizarframework\Controller as BaseController;

class PwdResetController extends BaseController
{
    const RESET_EVENT = 'PasswordReset';
    protected UserModel $userModel;
    protected PwdResetModel $pwdResetModel;
    public function __construct()
    {
        $this->setLayout('auth');
        $this->userModel = new (app()->userClass);
        $this->pwdResetModel = new PwdResetModel();
    }
    public function forgotPasswordView()
    {
        return $this->render('auth/forgot-password', [
            'model' => $this->userModel
        ]);
    }

    public function requestResetPassword(Request  $request, Response $response)
    {
        if ($this->userModel->validate($request->postData(), ['email' => 'required|valid_email|is_not_unique:users.email'])) {

            $user = $this->userModel->findOne([
                'email' => $request->getVar('email')
            ]);
            $status = $this->pwdResetModel::sendResetLink($user);

            // $status === PwdResetModel::RESET_LINK_SENT
            // ? session()->setFlash('status', __($status)) 
            // : session()->setFlash('errors', ['email' => __($status)]);
            if ($status === PwdResetModel::RESET_LINK_SENT) {
                session()->setFlash('success', __($status));
                return redirect(route_to('forgot-password'));
            } else {
                $this->userModel->addError('email', __($status));
            }
        }

        return $this->render('auth/forgot-password', ['model' => $this->userModel]);
    }

    public function resetPasswordView(Request $request, Response $response): string
    {
        $validator = bin2hex($request->getVar('validator'));
        $selector = $request->getVar('selector');
        $model = $this->userModel;
        return $this->render('auth/reset-password', compact('validator', 'selector', 'model'));
    }

    public function resetPassword(Request $request)
    {
        $validator = bin2hex($request->getVar('validator'));
        $selector = $request->getVar('selector');
        $model = $this->userModel;
        $valide = $model->validate(
            $request->postData(['password', 'passwordConfirm', 'validator', 'selector']),
            [
                'password' => 'required|min_length:8',
                'passwordConfirm' => 'required|matches:password',
                'validator' => 'required',
                'selector' => 'required'
            ]
        );

        if ($valide) {
            $status = PwdResetModel::reset(
                $request->postData(['password', 'validator', 'selector']),
                function (UserModel $user, $password) {
                    $user->password = $password;
                    $user->save();
                    // $user->save();

                    app()->triggerEvent(self::RESET_EVENT, $user);
                }
            );

            if ($status === PwdResetModel::PASSWORD_RESET) {
                session()->setFlash('success', __($status));
                return redirect(route_to('login'));
            } else {
                $model->addError('email', __($status));
            }
        }

        return $this->render('auth/reset-password', compact('validator', 'selector', 'model'));
    }
}
