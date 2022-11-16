<?php
/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: $file.created
 * 7/6/22, 8:00 AM
 * Last Modified at: 7/6/22, 8:00 AM
 * Time: 8:0
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace app\core;

class Session
{
    protected const FLASH_KEY='flash_messages';
    public function __construct()
    {
        session_start();
        $flashMessages= $_SESSION[self::FLASH_KEY]??[];
        foreach ($flashMessages as $key => &$flashMessage) {
            //mark to be removed
            $flashMessage['remove']=true;
        }
        $_SESSION[self::FLASH_KEY]=$flashMessages;
    }

    public function setFlash($key, $message)
    {
        $_SESSION[self::FLASH_KEY][$key]=[
            'remove'=>false,
            'value'=>$message
        ];
    }

    public function getFlash($key)
    {
        return $_SESSION[self::FLASH_KEY][$key]['value']??false;
    }

    public function set(string $key, $value)
    {
        $_SESSION[$key]=$value;
    }

    public function get($key)
    {
        return $_SESSION[$key]??false;
    }

    public function remove($key)
    {
        unset($_SESSION[$key]);
    }

    public function __destruct()
    {
        // iterate over marked to be removed
        $flashMessages= $_SESSION[self::FLASH_KEY]??[];
        foreach ($flashMessages as $key => &$flashMessage) {
            if ($flashMessage['remove']){
                unset($flashMessages[$key]);
            }
        }

        $_SESSION[self::FLASH_KEY]=$flashMessages;
    }
}