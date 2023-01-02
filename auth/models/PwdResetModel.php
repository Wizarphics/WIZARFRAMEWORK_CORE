<?php

namespace wizarphics\wizarframework\auth\models;

use app\configs\Email as ConfigsEmail;
use app\models\User;
use DateInterval;
use DateTime;
use wizarphics\wizarframework\db\DbModel;
use wizarphics\wizarframework\email\Email;
use wizarphics\wizarframework\UserModel;
use wizarphics\wizarframework\View;

class PwdResetModel extends DbModel
{
    /**
     * Constant representing a successfully sent reminder.
     *
     * @var string
     */
    const RESET_LINK_SENT = 'Passwords.sent';
    const RESET_SUBJECT = 'Passwords.resetTitle';

    /**
     * Constant representing a successfully reset password.
     *
     * @var string
     */
    const PASSWORD_RESET = 'Passwords.reset';

    /**
     * Constant representing the user not found response.
     *
     * @var string
     */
    const INVALID_USER = 'Passwords.user';

    /**
     * Constant representing an invalid token.
     *
     * @var string
     */
    const INVALID_TOKEN = 'Passwords.token';

    /**
     * Constant representing a throttled reset attempt.
     *
     * @var string
     */
    const RESET_THROTTLED = 'Passwords.throttled';

    private static $_temp = 'Passwords.resetEmail';

    private static $_salt = '$$$$$$$$';

    public static function sendResetLink(?UserModel $user): string
    {
        $email = $user->email;
        if (is_null($user) || !$user) {
            return static::INVALID_USER;
        }

        [$token, $selector] = static::generateResetToken($user);
        $token_selector = bin2hex($token) . ":" . $selector;
        // Once we have the reset token, we are ready to send the message out to this
        // user with a link to reset their password.
        $url = site_url(route_to('reset-password')) . '?' . http_build_query([
            'token' => $token_selector,
        ]);

        return static::sendPasswordResetNotification($url, $user);
    }

    private static function sendPasswordResetNotification(string $url, UserModel $user)
    {
        $link = create_link($url, 'url', 'Reset Password');
        $emailer = new Email(new ConfigsEmail());
        $emailer->setMailType('html');
        $emailer->setProtocol('smtp');
        $emailer->SMTPCrypto = 'tls';
        $emailer->setTo($user->email);
        $emailer->setFrom(env('email.fromEmail'));
        $emailer->setSubject(__(self::RESET_SUBJECT));
        $emailer->setMessage(__(static::resetMessage([
            'link' => $link,
            'email' => $user->email,
            'name' => $user->getDisplayName()
        ])));
        $emailer->setCRLF("\r\n");
        $emailer->setNewline("\r\n");
        if ($emailer->send() == false) {
            log_message('error', $emailer->printDebugger(['headers']));
            // session()->setFlash('error', __('Auth.unableSendEmailToUser', [$user->email]));
            return 'Auth.unableSendEmailToUser';
        }

        // Clear the email
        $emailer->clear();
        return static::RESET_LINK_SENT;
    }

    private static function resetMessage($params): string
    {
        $temp = self::$_temp;
        try {
            /** @var View $view */
            $view = app()->view;
            $view->title = __(self::RESET_SUBJECT);
            $content = $view->renderViewComponent(__($temp), 'email', $params);
        } catch (\Throwable $e) {
            $content = __($temp);
        }
        return $content;
    }

    private static function generateResetToken(UserModel $user): array
    {
        $selector = bin2hex(random_bytes(8));;
        $email = $user->email;
        $expires = (new DateTime())->add(DateInterval::createFromDateString('1 Hour'))->format('Y-m-d H:i:s');
        $token = random_bytes(32);
        self::deleteExisting($user);
        $hashedToken = self::hashToken($token);
        $pwdResetToken = (new self);
        $pwdResetToken->loadData([
            'pwd_reset_secret' => $email,
            'pwd_reset_selector' => $selector,
            'pwd_reset_token' => $hashedToken,
            'pwd_reset_expires' => $expires,
        ]);
        $pwdResetToken->save();

        return [$token, $selector];
    }

    private static function hashToken(string $token): string|false
    {
        return $hashedToken = hash_hmac('sha256', $token, self::$_salt);
    }

    private static function deleteExisting(UserModel $user): void
    {
        if (self::exists($user)) {
            (new self)->_db->where([
                'pwd_reset_secret' => $user->email,
            ])->delete([], (new self)->tableName());
        }
    }

    private static function exists(UserModel $user): bool
    {
        return (new self)->findOne([
            'pwd_reset_secret' => $user->email,
        ]) ? true : false;
    }

    public static function reset(array $data, callable $callback): string
    {
        $token = explode(':', $data['token']);
        $validator = $token[0];
        $password = $data['password'];
        $selector = $token[1];
        $SQL = "SELECT * FROM `pwd_reset` WHERE pwd_reset_selector = :pwd_reset_selector AND pwd_reset_expires <= " . (time() + 1800) . ";";
        $stm = (new static)->_db->prepare($SQL);
        $stm->bindValue('pwd_reset_selector', $selector);
        $stm->execute();
        $stm->setFetchMode(\PDO::FETCH_CLASS, static::class);
        $pwdToken = $stm->fetch();
        if ($pwdToken === false)
            return static::INVALID_TOKEN;

        $tokenCheck = hash_equals($pwdToken->pwd_reset_token, self::hashToken($validator));
        if (!$tokenCheck)
            return static::INVALID_TOKEN;

        $tokenEmail = $pwdToken->pwd_reset_secret;
        $user = (new (app()->userClass))->findOne([
            'email' => $tokenEmail,
        ]);

        if ($user === false || $user == null)
            return static::INVALID_USER;

        $callback($user, $password);
        
        return static::PASSWORD_RESET;
    }

    /**
     * @return string
     */
    public function tableName(): string
    {
        return 'pwd_reset';
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return [
            'pwd_reset_secret', 'pwd_reset_selector', 'pwd_reset_token', 'pwd_reset_expires',
        ];
    }

    /**
     * @return string
     */
    public function primaryKey(): string
    {
        return 'id';
    }

    /**
     * [Description for rules]
     * @return array Created at: 11/24/2022, 2:55:58 PM (Africa/Lagos)
     */
    public function rules(): array
    {
        return [];
    }
}
