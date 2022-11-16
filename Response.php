<?php
/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: $file.created
 * 6/30/22, 9:18 PM
 * Last Modified at: 6/30/22, 9:18 PM
 * Time: 9:18
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace app\core;

class Response
{
    public function setStatusCode(int $code)
    {
        http_response_code($code);
    }

    public function redirect(string $url)
    {
        header('location:'.$url);
    }
}