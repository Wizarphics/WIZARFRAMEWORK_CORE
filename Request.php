<?php
/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: $file.created
 * 6/30/22, 7:02 PM
 * Last Modified at: 6/30/22, 7:02 PM
 * Time: 7:2
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace app\core;

class Request
{
    public function getPath()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position === false) {

            return $path;
        }
        return substr($path, 0, $position);
    }

    public function Method()
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public function getBody()
    {
        $body = [];
        if ($this->Method()==='get'){
            foreach ($_GET as $key=>$value) {
                $body[$key]=filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS );
            }
        }
        if ($this->Method()==='post'){
            foreach ($_POST as $key=>$value) {
                $body[$key]=filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS );
            }
        }

        return $body;
    }

    public function isGet()
    {
        return $this->Method()==='get';
    }

    public function isPost()
    {
        return $this->Method()==='post';
    }
}