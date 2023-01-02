<?php

namespace wizarphics\wizarframework\auth;

use RuntimeException;
use wizarphics\wizarframework\UserModel;

class Password
{
    
    /**
     * --------------------------------------------------------------------
     * Encryption Algorithm to use
     * --------------------------------------------------------------------
     * Valid values are
     * - PASSWORD_DEFAULT (default)
     * - PASSWORD_BCRYPT
     * - PASSWORD_ARGON2I  - As of PHP 7.2 only if compiled with support for it
     * - PASSWORD_ARGON2ID - As of PHP 7.3 only if compiled with support for it
     *
     * If you choose to use any ARGON algorithm, then you might want to
     * uncomment the "ARGON2i/D Algorithm" options to suit your needs
     */
    public string $hashAlgorithm = PASSWORD_DEFAULT;

    /**
     * --------------------------------------------------------------------
     * ARGON2i/D Algorithm options
     * --------------------------------------------------------------------
     * The ARGON2I method of encryption allows you to define the "memory_cost",
     * the "time_cost" and the number of "threads", whenever a password hash is
     * created.
     * This defaults to a value of 10 which is an acceptable number.
     * However, depending on the security needs of your application
     * and the power of your hardware, you might want to increase the
     * cost. This makes the hashing process takes longer.
     */
    public int $hashMemoryCost = 2048;  // PASSWORD_ARGON2_DEFAULT_MEMORY_COST;

    public int $hashTimeCost = 4;       // PASSWORD_ARGON2_DEFAULT_TIME_COST;
    public int $hashThreads  = 4;        // PASSWORD_ARGON2_DEFAULT_THREADS;

    /**
     * --------------------------------------------------------------------
     * Password Hashing Cost
     * --------------------------------------------------------------------
     * The BCRYPT method of encryption allows you to define the "cost"
     * or number of iterations made, whenever a password hash is created.
     * This defaults to a value of 10 which is an acceptable number.
     * However, depending on the security needs of your application
     * and the power of your hardware, you might want to increase the
     * cost. This makes the hashing process takes longer.
     *
     * Valid range is between 4 - 31.
     */
    public int $hashCost = 10;
    /**
     * Hash a password.
     *
     * @return false|string|null
     */
    public function hashPassword(string $password)
    {
        if ((defined('PASSWORD_ARGON2I') && $this->hashAlgorithm === PASSWORD_ARGON2I)
            || (defined('PASSWORD_ARGON2ID') && $this->hashAlgorithm === PASSWORD_ARGON2ID)
        ) {
            $hashOptions = [
                'memory_cost' => $this->hashMemoryCost,
                'time_cost'   => $this->hashTimeCost,
                'threads'     => $this->hashThreads,
            ];
        } else {
            $hashOptions = [
                'cost' => $this->hashCost,
            ];
        }

        return password_hash(
            base64_encode(
                hash('sha384', $password, true)
            ),
            $this->hashAlgorithm,
            $hashOptions
        );
    }

    /**
     * Verifies a password against a previously hashed password.
     *
     * @param string $password The password we're checking
     * @param string $hash     The previously hashed password
     */
    public function verify(string $password, string $hash): bool
    {
        return password_verify(base64_encode(
            hash('sha384', $password, true)
        ), $hash);
    }

    /**
     * Checks to see if a password should be rehashed.
     */
    public function needsRehash(string $hashedPassword): bool
    {
        return password_needs_rehash($hashedPassword, $this->hashAlgorithm);
    }

    /**
     * Checks a password against all of the Validators specified
     * in `$passwordValidators` setting in Config\Auth.php.
     *
     * @throws RuntimeException
     */
    public function check(string $password, ?UserModel $user = null): AuthResult
    {
        if (null === $user) {
            throw new RuntimeException("No user supplied");
        }

        $password = trim($password);

        if (empty($password)) {
            return new AuthResult([
                'success' => false,
                'reason'  => __('Auth.errorPasswordEmpty'),
            ]);
        }

        return new AuthResult([
            'success' => true,
        ]);
    }
}
