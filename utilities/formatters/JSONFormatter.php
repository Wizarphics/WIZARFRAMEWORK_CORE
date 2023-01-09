<?php

namespace wizarphics\wizarframework\utilities\formatters;
use RuntimeException;
use wizarphics\wizarframework\interfaces\FormatterInterface;

/**
 * JSON data formatter
 */
class JSONFormatter implements FormatterInterface
{
    /**
     * Takes the given data and formats it.
     *
     * @param mixed $data
     *
     * @return false|string (JSON string | false)
     */
    public function format($data)
    {
        $options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        $options = $options | JSON_PARTIAL_OUTPUT_ON_ERROR;

        $options = ENVIRONMENT === 'production' ? $options : $options | JSON_PRETTY_PRINT;

        $result = json_encode($data, $options, 512);

        if (! in_array(json_last_error(), [JSON_ERROR_NONE, JSON_ERROR_RECURSION], true)) {
            throw new RuntimeException(__('Failed to parse json string, error: "{0}".', [json_last_error_msg()]));
        }
        return $result;
    }
}
