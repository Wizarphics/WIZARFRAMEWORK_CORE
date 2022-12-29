<?php

namespace wizarphics\wizarframework\interfaces;

/**
 * Expected behavior of an HTTP request
 */
interface RequestInterface
{
    /**
     * Gets the user's IP address.
     * Supplied by RequestTrait.
     *
     * @return string IP address
     */
    public function getIPAddress(): string;

    /**
     * Get the request method.
     */
    public function Method(): string;

    /**
     * Fetch an item from the $_SERVER array.
     * Supplied by RequestTrait.
     *
     * @param string $index  Index for item to be fetched from $_SERVER
     * @param null   $filter A filter name to be applied
     *
     * @return mixed
     */
    public function getServer($index = null, $filter = null);
}
