<?php

namespace wizarphics\wizarframework\auth;

use RuntimeException;
use wizarphics\wizarframework\auth\authenticators\SessionAuth;
use wizarphics\wizarframework\auth\authenticators\TokenAuth;
use wizarphics\wizarframework\interfaces\AuthenticationInterface;
use wizarphics\wizarframework\UserModel;

class Authentication
{
    /**
     * Instantiated Authenticator objects,
     * stored by Authenticator alias.
     *
     * @var array<string, AuthenticationInterface> [Authenticator_alias => Authenticator_instance]
     */
    protected array $instances = [];

    protected UserModel $userHandler;

    protected string $defaultAuthenticator = 'web';
    protected array $authenticators;

    public function __construct(?array $authenticators = null)
    {
        $authenticators ??= [];
        $this->authenticators = array_merge([
            'web' => SessionAuth::class,
            'token' => TokenAuth::class
        ], $authenticators);
    }

    /**
     * Returns an instance of the specified Authenticator.
     *
     * You can pass 'default' as the Authenticator and it
     * will return an instance of the first Authenticator specified
     * in the Auth config file.
     *
     * @param string|null $alias Authenticator alias
     *
     * @throws RuntimeException
     */
    public function guard(?string $alias = null): AuthenticationInterface
    {
        // Determine actual Authenticator alias
        $alias ??= $this->defaultAuthenticator;

        // Return the cached instance if we have it
        if (!empty($this->instances[$alias])) {
            return $this->instances[$alias];
        }

        // Otherwise, try to create a new instance.
        if (!array_key_exists($alias, $this->authenticators)) {
            throw new RuntimeException(__('Auth.unknownAuthenticator', [$alias]));
        }

        $className = $this->authenticators[$alias];

        assert($this->userHandler !== null, 'You must set $this->userHandler.');

        $this->instances[$alias] = new $className($this->userHandler);

        return $this->instances[$alias];
    }

    /**
     * @param UserModel $userHandler 
     * @return self
     */
    public function setUserHandler(UserModel $userHandler): self
    {
        $this->userHandler = $userHandler;
        return $this;
    }
}
