<?php

namespace wizarphics\wizarframework\auth;

use app\models\User;
use wizarphics\wizarframework\UserModel;
use RuntimeException;
use wizarphics\wizarframework\interfaces\AuthenticationInterface;

/**
 * @method Result    attempt(array $credentials, bool $remember = false): AuthResult
 * @method Result    check(array $credentials)
 * @method User|null getUser()
 * @method bool      loggedIn()
 * @method bool      login(User $user)
 * @method void      loginById($userId)
 * @method bool      logout()
 * @method void      recordActiveDate()
 */
class Auth
{
    protected Authentication $authenticate;

    /**
     * The Authenticator alias to use for this request.
     */
    protected ?string $alias = null;

    protected ?UserModel $userHandler = null;

    public function __construct(Authentication $authenticate)
    {
        $this->authenticate = $authenticate->setUserHandler($this->getUserHandler());
    }

    /**
     * Sets the Authenticator alias that should be used for this request.
     *
     * @return $this
     */
    public function guard(?string $alias = null): self
    {
        if (!empty($alias)) {
            $this->alias = $alias;
        }

        return $this;
    }

    /**
     * Returns the current authentication class.
     */
    public function whichGuard(): AuthenticationInterface
    {
        return $this->authenticate
            ->guard($this->alias);
    }

    /**
     * Returns the current user, if logged in.
     */
    public function user(): UserModel|User|null
    {
        return $this->whichGuard()->loggedIn()
            ? $this->whichGuard()->getUser()
            : null;
    }

    /**
     * Returns the current user's id, if logged in.
     *
     * @return int|string|null
     */
    public function id()
    {
        return ($user = $this->user())
            ? $user->id
            : null;
    }

    public function authenticate(array $credentials,  bool $remember = false): AuthResult
    {
        return $this->authenticate
            ->guard($this->alias)
            ->attempt($credentials, $remember);
    }

    /**
     * Returns the Model that is responsible for getting users.
     *
     * @throws RuntimeException
     */
    public function getUserHandler(): UserModel|User
    {
        if ($this->userHandler !== null) {
            return $this->userHandler;
        }


        if (!property_exists(app(), 'userClass')) {
            throw new RuntimeException(__('Auth.unknownUserProvider'));
        }
        $className          = app()->userClass;

        $this->userHandler = new $className();

        return $this->userHandler;
    }

    /**
     * Provide magic function-access to Authenticators to save use
     * from repeating code here, and to allow them have their
     * own, additional, features on top of the required ones,
     * like "remember-me" functionality.
     *
     * @param string[] $args
     *
     * @throws RuntimeException
     */
    public function __call(string $method, array $args)
    {
        $authenticate = $this->authenticate->guard($this->alias);

        if (method_exists($authenticate, $method)) {
            return $authenticate->{$method}(...$args);
        }
    }
}
