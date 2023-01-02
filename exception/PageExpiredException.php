<?php 

namespace wizarphics\wizarframework\exception;
class PageExpiredException extends \Exception{
    protected $message = 'Page Expired';
    protected $code = 419;
}