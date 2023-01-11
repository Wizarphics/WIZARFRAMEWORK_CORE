<?php

namespace wizarphics\wizarframework\interfaces;

/**
 * Formatter interface
 */
interface FormatterInterface
{
    /**
     * Takes the given data and formats it.
     *
     * @param array|string $data
     *
     * @return bool|string
     */
    public function format($data);
}
