<?php

namespace wizarphics\wizarframework\http;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use RuntimeException;
use Traversable;

/**
 * The CookieHolder object represents an immutable collection of `Cookie` value objects.
 *
 * @implements IteratorAggregate<string, Cookie>
 */
class CookieHolder implements Countable, IteratorAggregate
{
    /**
     * The cookie collection.
     *
     * @var array<string, Cookie>
     */
    protected $cookies = [];

    /**
     * Creates a CookieHolder from an array of `Set-Cookie` headers.
     *
     * @param string[] $headers
     *
     * @return static
     *
     * @throws RuntimeException
     */
    public static function fromCookieHeaders(array $headers, bool $raw = false)
    {
        /**
         * @var Cookie[] $cookies
         */
        $cookies = array_filter(array_map(static function (string $header) use ($raw) {
            try {
                return Cookie::fromHeaderString($header, $raw);
            } catch (RuntimeException $e) {
                log_message('error', (string) $e);

                return false;
            }
        }, $headers));

        return new static($cookies);
    }

    /**
     * @param Cookie[] $cookies
     *
     * @throws RuntimeException
     */
    final public function __construct(array $cookies)
    {
        $this->validateCookies($cookies);

        foreach ($cookies as $cookie) {
            $this->cookies[$cookie->getId()] = $cookie;
        }
    }

    /**
     * Checks if a `Cookie` object identified by name and
     * prefix is present in the collection.
     */
    public function has(string $name, string $prefix = '', ?string $value = null): bool
    {
        $name = $prefix . $name;

        foreach ($this->cookies as $cookie) {
            if ($cookie->getPrefixedName() !== $name) {
                continue;
            }

            if ($value === null) {
                return true; // for BC
            }

            return $cookie->getValue() === $value;
        }

        return false;
    }

    /**
     * Retrieves an instance of `Cookie` identified by a name and prefix.
     * This throws an exception if not found.
     *
     * @throws RuntimeException
     */
    public function get(string $name, string $prefix = ''): Cookie
    {
        $name = $prefix . $name;

        foreach ($this->cookies as $cookie) {
            if ($cookie->getPrefixedName() === $name) {
                return $cookie;
            }
        }

        throw new RuntimeException(__('Cookie.unknownCookieInstance', [$name, $prefix]));
    }

    /**
     * Store a new cookie and return a new collection. The original collection
     * is left unchanged.
     *
     * @return static
     */
    public function put(Cookie $cookie)
    {
        $store = clone $this;

        $store->cookies[$cookie->getId()] = $cookie;

        return $store;
    }

    /**
     * Removes a cookie from a collection and returns an updated collection.
     * The original collection is left unchanged.
     *
     * Removing a cookie from the store **DOES NOT** delete it from the browser.
     * If you intend to delete a cookie *from the browser*, you must put an empty
     * value cookie with the same name to the store.
     *
     * @return static
     */
    public function remove(string $name, string $prefix = '')
    {
        $default = Cookie::setDefaults();

        $id = implode(';', [$prefix . $name, $default['path'], $default['domain']]);

        $store = clone $this;

        foreach (array_keys($store->cookies) as $index) {
            if ($index === $id) {
                unset($store->cookies[$index]);
            }
        }

        return $store;
    }

    /**
     * Dispatches all cookies in store.
     *
     * @deprecated Response should dispatch cookies.
     */
    public function dispatch(): void
    {
        foreach ($this->cookies as $cookie) {
            $name    = $cookie->getPrefixedName();
            $value   = $cookie->getValue();
            $options = $cookie->getOptions();

            if ($cookie->isRaw()) {
                setrawcookie($name, $value, $options);
            } else {
                setcookie($name, $value, $options);
            }
        }

        $this->clear();
    }

    /**
     * Returns all cookie instances in store.
     *
     * @return array<string, Cookie>
     */
    public function display(): array
    {
        return $this->cookies;
    }

    /**
     * Clears the cookie collection.
     */
    public function clear(): void
    {
        $this->cookies = [];
    }

    /**
     * Gets the Cookie count in this collection.
     */
    public function count(): int
    {
        return count($this->cookies);
    }

    /**
     * Gets the iterator for the cookie collection.
     *
     * @return Traversable<string, Cookie>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->cookies);
    }

    /**
     * Validates all cookies passed to be instances of Cookie.
     *
     * @throws RuntimeException
     */
    protected function validateCookies(array $cookies): void
    {
        foreach ($cookies as $index => $cookie) {
            $type = is_object($cookie) ? get_class($cookie) : gettype($cookie);

            if (!$cookie instanceof Cookie) {
                throw new RuntimeException(__('Cookie.invalidCookieInstance', [static::class, Cookie::class, $type, $index]));
            }
        }
    }
}
