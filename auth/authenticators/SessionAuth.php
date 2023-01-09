<?php

namespace wizarphics\wizarframework\auth\authenticators;

use app\models\User;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use stdClass;
use wizarphics\wizarframework\auth\AuthResult;
use wizarphics\wizarframework\auth\models\RMTokenModel;
use wizarphics\wizarframework\auth\Password;
use wizarphics\wizarframework\interfaces\AuthenticationInterface;
use wizarphics\wizarframework\UserModel;

class SessionAuth implements AuthenticationInterface
{

    // User states
    private const STATE_UNKNOWN   = 0; // Not checked yet.
    private const STATE_ANONYMOUS = 1;
    private const STATE_PENDING   = 2; // 2FA or Activation required.
    private const STATE_LOGGED_IN = 3;

    protected UserModel $userHandler;

    protected RMTokenModel $RememberModel;

    /**
     * Authenticated or authenticating (pending login) User
     */
    protected User|UserModel|null $user = null;

    /**
     * The User auth state
     */
    private int $userState = self::STATE_UNKNOWN;

    /**
     * Should the user be remembered?
     */
    protected bool $rememberMe = false;

    private $_sessionName, $_cookieName, $_cookieExpiry;

    public function __construct(UserModel $userHandler)
    {
        $this->userHandler = $userHandler;
        $this->RememberModel = new RMTokenModel;
        $this->_sessionName = env("auth.session_name");
        $this->_cookieName = env('auth.cookie_name');
        $this->set_cookieExpiry(env('auth.cookie_expiry'));
    }

    /**
     * Attempts to authenticate a user with the given $credentials.
     * Logs the user in with a successful check.
     *
     * @param array $credentials
     * @return AuthResult
     */
    public function attempt(array $credentials, bool $remember = false): AuthResult
    {
        $this->rememberMe = $remember;

        $result = $this->check($credentials);

        // Credentials mismatch.
        if (!$result->isOK()) {
            $this->user = null;

            // Fire an event on failure so devs have the chance to
            // let them know someone attempted to login to their account
            unset($credentials['password']);
            app()->triggerEvent(self::FAILED_LOGIN, $credentials);

            return $result;
        }

        /** @var User|UserModel $user */
        $user = $result->info();

        $this->user = $user;

        $this->_startLogin($user);

        $this->handleRememberMeToken();

        $this->_completeLogin($user);

        return $result;
    }

    /**
     * Checks a user's $credentials to see if they match an
     * existing user.
     *
     * @phpstan-param array{email?: string, username?: string, password?: string} $credentials
     */
    public function check(array $credentials): AuthResult
    {
        // Can't validate without a password.
        if (empty($credentials['password']) || count($credentials) < 2) {
            return new AuthResult([
                'success' => false,
                'reason'  => __('Auth.badAttempt'),
            ]);
        }

        // Remove the password from credentials so we can
        // check afterword.
        $givenPassword = $credentials['password'];

        unset($credentials['password']);

        // Find the existing user
        /** @var UserModel|null $user */
        $user = $this->userHandler->findOne($credentials);

        if ($user === null) {
            return new AuthResult([
                'success' => false,
                'reason'  => __('Auth.badAttempt'),
            ]);
        }

        /** @var Password $passwordHandler */
        $passwordHandler = $this->userHandler->passwordHandler;

        // Now, try matching the passwords.
        if (!$passwordHandler->verify($givenPassword, $user->password)) {
            return new AuthResult([
                'success' => false,
                'reason'  => __('Auth.invalidPassword'),
            ]);
        }

        // Check to see if the password needs to be rehashed.
        // This would be due to the hash algorithm or hash
        // cost changing since the last time that a user
        // logged in.
        if ($passwordHandler->needsRehash($user->password)) {
            $user->password = $passwordHandler->hashPassword($givenPassword);
            $user->save();
        }

        return new AuthResult([
            'success'   => true,
            'extraInfo' => $user,
        ]);
    }

    /**
     * Completes login process
     */
    protected function _completeLogin(UserModel $user): void
    {
        $this->userState = self::STATE_LOGGED_IN;

        // a successful login
        app()->triggerEvent(self::LOGIN_EVENT, $user);
    }

    private function issueUserState()
    {
        if ($this->userState !== self::STATE_UNKNOWN) {
            // Checked already.
            return;
        }


        /** @var int|string|null $userId */
        $userId = $this->getSession('id');

        // Has User Info in Session.
        if ($userId !== null) {
            $this->user = $this->userHandler->find($userId);

            if ($this->user === null) {
                // The user is deleted.
                $this->userState = self::STATE_ANONYMOUS;

                // Remove User Info in Session.
                $this->removeSessionUserInfo();

                return;
            }

            $this->userState = self::STATE_LOGGED_IN;

            return;
        }

        // No User Info in Session.
        // Check remember-me token.
        $this->checkRememberMe();
    }

    /**
     * Starts login process
     */
    public function _startLogin(UserModel $user): void
    {
        /** @var int|string|null $userId */
        $userId = $this->getSession('id');

        // Check if already logged in.
        if ($userId !== null) {
            throw new LogicException(
                'The user has User Info in Session, so already logged in or in pending login state.'
                    . ' If a logged in user logs in again with other account, the session data of the previous'
                    . ' user will be used as the new user.'
                    . ' Fix your code to prevent users from logging in without logging out or delete the session data.'
                    . ' user_id: ' . $userId
            );
        }

        $this->user = $user;

        // Regenerate the session ID to help protect against session fixation
        if (ENVIRONMENT !== 'testing') {
            session()->regenerate(true);
        }

        // Let the session know we're logged in
        $this->setSession('id', $user->id);
    }

    public function isGuest()
    {
        $this->issueUserState();
        return $this->userState == self::STATE_ANONYMOUS;
    }

    /**
     * Checks if the user is currently logged in.
     * @return bool
     */
    public function loggedIn(): bool
    {
        $this->issueUserState();
        return $this->userState === self::STATE_LOGGED_IN;
    }

    /**
     * Gets the key value in Session User Info
     *
     * @return int|string|null
     */
    private function getSession(string $key)
    {
        $sessionUserInfo = $this->fetchSessionUserInfo();

        return $sessionUserInfo[$key] ?? null;
    }

    /**
     * Sets the key value in Session User Info
     *
     * @param int|string|null $value
     */
    private function setSession(string $key, $value): void
    {
        $sessionUserInfo       = $this->fetchSessionUserInfo();
        $sessionUserInfo[$key] = $value;
        session()->set($this->_sessionName, $sessionUserInfo);
    }

    /**
     * Gets User Info in Session
     */
    private function fetchSessionUserInfo(): array
    {
        $items = session()->getValue($this->_sessionName);
        return $items ? $items : [];
    }

    /**
     * Removes User Info in Session
     */
    private function removeSessionUserInfo(): void
    {
        session()->remove($this->_sessionName);
    }

    /**
     * @return bool true if logged in by remember-me token.
     */
    private function checkRememberMe(): bool
    {
        // Get remember-me token.
        $remember = $this->fetchRememberToken();
        if ($remember === null) {
            $this->userState = self::STATE_ANONYMOUS;

            return false;
        }

        // Check the remember-me token.
        $token = $this->validateRememberToken($remember);
        if ($token === false) {
            $this->userState = self::STATE_ANONYMOUS;

            return false;
        }

        $user = $this->userHandler->find($token->user_id);

        if ($user === null) {
            // The user is deleted.
            $this->userState = self::STATE_ANONYMOUS;

            // Remove remember-me cookie.
            $this->removeRememberCookie();

            return false;
        }

        $this->_startLogin($user);

        $this->refreshRememberToken($token);

        $this->userState = self::STATE_LOGGED_IN;

        return true;
    }

    private function fetchRememberToken(): ?string
    {
        /** @var \wizarphics\wizarframework\http\Request $request */
        $request = app()->request;

        $cookieName = env('cookie.prefix') . $this->_cookieName;
        return $request->cookieData($cookieName);
    }

    /**
     * @return false|\stdClass
     */
    private function validateRememberToken(string $remember)
    {
        [$selector, $validator] = explode(':', $remember);

        $hashedValidator = hash('sha256', $validator);

        $token = $this->RememberModel->getToken($selector);

        if ($token === null) {
            return false;
        }

        if (hash_equals($token->hash, $hashedValidator) === false) {
            return false;
        }

        return $token;
    }

    private function handleRememberMeToken(): void
    {
        if ($this->rememberMe) {
            $this->rememberUser($this->user);

            // Reset so it doesn't mess up future calls.
            $this->rememberMe = false;
        } elseif ($this->fetchRememberToken()) {
            $this->removeRememberCookie();
            $this->RememberModel->remove($this->user);
        }

        // We'll give a 20% chance to need to do a purge since we
        // don't need to purge THAT often, it's just a maintenance issue.
        // to keep the table from getting out of control.
        if (random_int(1, 100) <= 20) {
            $this->RememberModel->deleteOldTokens();
        }
    }

    /**
     * Removes any remember-me tokens, if applicable.
     */
    public function forget(?UserModel $user = null): void
    {
        $user ??= $this->user;
        if ($user === null) {
            return;
        }

        $this->RememberModel->remove($user);
    }

    private function removeRememberCookie(): void
    {
        /** @var \wizarphics\wizarframework\http\Response $response */
        $response = app('response');

        // Remove remember-me cookie
        $response->unsetCookie(
            $this->_cookieName,
            env('cookie.domain'),
            env('cookie.path'),
            env('cookie.prefix')
        );
    }

    /**
     * Returns the current user instance.
     */
    public function getUser(): ?UserModel
    {
        $this->issueUserState();

        if ($this->userState === self::STATE_LOGGED_IN) {
            return $this->user;
        }

        return null;
    }

    /**
     * Logs the given user in.
     * On success this must trigger the "login" Event.
     *
     * @param UserModel $user
     */
    public function login(UserModel $user): void
    {
        $this->user = $user;
        $this->_startLogin($user);
        $this->handleRememberMeToken();
        $this->_completeLogin($user);
    }

    /**
     * Logs a user in based on their ID.
     * On success this must trigger the "login" Event.
     *
     * @param int|string $userId
     */
    public function loginById($userId): void
    {
        $user = $this->userHandler->find($userId);
        if (empty($user)) {
            throw new RuntimeException(__('Auth.invalidUser'));
        }

        $this->login($user);
    }

    /**
     * Logs the current user out.
     * On success this must trigger the "logout" Event.
     */
    public function logout(): void
    {
        $this->issueUserState();

        if ($this->user === null) {
            return;
        }

        // Destroy the session data - but ensure a session is still
        // available for flash messages, etc.
        /** @var \wizarphics\wizarframework\Session $session */
        $session     = session();
        $sessionData = $session->get();

        /**
         * @var array $sessionData
         */
        if (isset($sessionData)) {
            foreach (array_keys($sessionData) as $key) {
                session()->remove($key);
            }
        }


        // Regenerate the session ID for a touch of added safety.
        session()->regenerate(true);

        // Take care of any remember-me functionality
        $this->RememberModel->remove($this->user);


        // Trigger logout event
        app()->triggerEvent(self::LOGOUT_EVENT, $this->user);

        $this->user      = null;
        $this->userState = self::STATE_ANONYMOUS;
    }

    /**
     * Generates a timing-attack safe remember-me token
     * and stores the necessary info in the db and a cookie.
     *
     * @see https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
     */
    protected function rememberUser(UserModel $user): void
    {
        $selector  = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(20));
        $expires   = $this->calcExpires();

        $rawToken = $selector . ':' . $validator;

        // Store it in the database.
        $this->RememberModel->remember(
            $user,
            $selector,
            $this->hashValidator($validator),
            $expires
        );

        $this->setRememberMeCookie($rawToken);
    }

    private function setRememberMeCookie(string $rawToken): void
    {
        /** @var \wizarphics\wizarframework\http\Response $response */
        $response = app('response');

        // Save it to the user's browser in a cookie.
        // Create the cookie
        $response->setCookie(
            $this->_cookieName,
            $rawToken,                                             // Value
            $this->_cookieExpiry,      // # Seconds until it expires
            env('cookie.domain'),
            env('cookie.path'),
            env('cookie.prefix'),
            env('cookie.secure'),                          // Only send over HTTPS?
            true                                                  // Hide from Javascript?
        );
    }

    /**
     * Hash remember-me validator
     */
    private function hashValidator(string $validator): string
    {
        return hash('sha256', $validator);
    }

    private function calcExpires(): string
    {
        $expireTime = time() + $this->_cookieExpiry;
        return (new DateTime())->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /**
     * Updates the user's last active date.
     */
    public function recordActiveDate(): void
    {
        if (!$this->user instanceof UserModel) {
            throw new InvalidArgumentException(
                __METHOD__ . '() requires logged in user before calling.'
            );
        }

        $this->user->last_active = date('Y-m-d H:i:s');
        $this->user->save();
    }

    private function refreshRememberToken(stdClass $token): void
    {
        // Update validator.
        $validator = bin2hex(random_bytes(20));

        $token->hashedValidator = $this->hashValidator($validator);
        $token->expires         = $this->calcExpires();

        $this->RememberModel->loadData($token);
        $this->RememberModel->save();

        $rawToken = $token->selector . ':' . $validator;

        $this->setRememberMeCookie($rawToken);
    }

    /**
     * @return mixed
     */
    public function get_cookieExpiry()
    {
        return $this->_cookieExpiry;
    }

    /**
     * @param mixed $_cookieExpiry 
     * @return self
     */
    public function set_cookieExpiry($_cookieExpiry): self
    {
        $_cookieExpiry = math_eval($_cookieExpiry);
        $this->_cookieExpiry = $_cookieExpiry;
        return $this;
    }
}
