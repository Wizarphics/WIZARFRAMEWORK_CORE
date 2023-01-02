<?php
/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: 18/11/22, 11:30 PM
 * Last Modified at: 18/11/22, 11:30 PM
 * Time: 11:30 PM
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace wizarphics\wizarframework;

use PhpToken;
use wizarphics\wizarframework\exception\PageExpiredException;
use wizarphics\wizarframework\http\Request;

class Csrf
{
    private static string $PREFIX;

    protected static string $token;

    public const tokenFieldName = 'csrf_field';

    /**
     * CSRF Token Name
     *
     * Token name for Cross Site Request Forgery protection.
     *
     * @var string
     */
    protected $tokenName = 'csrf_token_name';

    public function __construct(Request $request)
    {
        self::setKey();
        // session()->set($PREFIX, $this->_generate_Prefix());
        // self::generateHash();
        self::storeCsrf($request);
    }

    protected static function setKey()
    {
        $PREFIX = env('csrf_Prefix') . 'SharrrfCsrf';
        self::$PREFIX = $PREFIX;
    }

    protected function _generate_Prefix(): string
    {
        $token = bin2hex(random_bytes(32));
        return $token;
    }

    protected static function generateHash(Request $request): string
    {
        $_salt = hash('sha256', $request->header('host')->getValue());
        $hash = hash_hmac('sha256', $_salt, session(self::$PREFIX));
        return $hash;
    }

    protected static function storeCsrf($request)
    {
        $hash =  self::$token = self::generateHash($request);
        setcookie(self::$PREFIX, $hash, 7200);
    }

    protected static function verifyHash(Request $request): bool
    {
        // Protects POST, PUT, DELETE, PATCH
        $method           = strtoupper($request->Method());
        $methodsToProtect = ['POST', 'PUT', 'DELETE', 'PATCH'];
        if (!in_array($method, $methodsToProtect, true)) {
            return true;
        }

        $postedHash = self::getPostedToken($request);

        if ($postedHash == null) {
            return false;
        }
        $salt = hash('sha256', $request->header('host')->getValue());
        return self::calcHash($salt, $postedHash);
    }

    public static function verify(Request $request): bool
    {
        $verified = self::verifyHash($request);
        if (!$verified) {
            throw new PageExpiredException();
        }
        return $verified;
    }

    protected static function getPostedToken(Request $request): ?string
    {
        $body = $request->getBody();
        $token = $body[self::tokenFieldName] ?? null;
        return $token;
    }


    protected static function calcHash($salt, string $tokenPosted): bool
    {
        self::setKey();
        $cal = hash_hmac('sha256', $salt, session(self::$PREFIX));
        return hash_equals($cal, $tokenPosted);
    }

    /**
     * @return string
     */
    public function getPREFIX(): string
    {
        return $this->PREFIX;
    }

    /**
     * @param string $PREFIX 
     * @return self
     */
    public function setPREFIX(string $PREFIX): self
    {
        self::$PREFIX = $PREFIX;
        return $this;
    }


    /**
     * @return string
     */
    public static function getToken(): string
    {
        return self::$token;
    }

    /**
     * @param string $token 
     */
    public static function setToken(string $token)
    {
        self::$token = $token;
    }
}
