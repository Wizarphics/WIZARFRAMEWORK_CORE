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

namespace wizarphics\wizarframework;

class Session
{
    protected const FLASH_KEY = 'flash_messages';
    public function __construct()
    {
        // if (!session_status() == PHP_SESSION_ACTIVE) {
        session_start();
        // }
        $flashMessages = $_SESSION[self::FLASH_KEY] ?? [];
        foreach ($flashMessages as $key => &$flashMessage) {
            //mark to be removed
            $flashMessage['remove'] = true;
        }
        $_SESSION[self::FLASH_KEY] = $flashMessages;
    }

    public function setFlash($key, $message)
    {
        $_SESSION[self::FLASH_KEY][$key] = [
            'remove' => false,
            'value' => $message
        ];
    }

    public function hasFlash(string $key)
    {
        return isset($_SESSION[self::FLASH_KEY][$key]);
    }
    public function getFlash(string $key)
    {
        if(!isset($_SESSION[self::FLASH_KEY][$key])){
            return false;
        }
        $flashM = $_SESSION[self::FLASH_KEY][$key]['value'];
        $flashR = $_SESSION[self::FLASH_KEY][$key]['remove'];
        if ($flashR) {
            unset($_SESSION[self::FLASH_KEY][$key]);
        }
        return $flashM;
    }

    public function set(string $key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function get($key)
    {
        return $_SESSION[$key] ?? false;
    }

    public function remove($key)
    {
        unset($_SESSION[$key]);
    }

    // public function __destruct()
    // {
    //     // iterate over marked to be removed
    //     $flashMessages= $_SESSION[self::FLASH_KEY]??[];
    //     foreach ($flashMessages as $key => &$flashMessage) {
    //         if ($flashMessage['remove']){
    //             unset($flashMessages[$key]);
    //         }
    //     }

    //     $_SESSION[self::FLASH_KEY]=$flashMessages;
    // }
}
