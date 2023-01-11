<?php

namespace wizarphics\wizarframework\exception;
use PDOException;

class DatabaseException extends PDOException{
    const ER_ACCESS_DENIED_ERROR = 1045;
    const ER_BAD_DB_ERROR = 1049;
    public function __construct(PDOException $pDOException)
    {
        $message = $pDOException->getMessage();
        $code = $pDOException->getCode();
        parent::__construct(message: $message,code: $code);
        $this->code = hexdec($code);
    }
}