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
    /**
     * [Description for __construct]
     *
     * 
     * Created at: 11/24/2022, 2:31:11 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
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

    /**
     * [Description for setFlash]
     *
     * @param string $key
     * @param mixed $message
     * 
     * @return void
     * 
     * Created at: 11/24/2022, 2:52:39 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function setFlash(string $key, mixed $message): void
    {
        $to_set = [
            'time_set' => date('Y-m-d H:i:s'),
            'message' => $message,
            'remove' => false,
        ];
        $_SESSION[self::FLASH_KEY][$key] = $to_set;
    }

    /**
     * [Description for hasFlash]
     *
     * @param string $key
     * 
     * @return bool
     * 
     * Created at: 11/24/2022, 2:53:56 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function hasFlash(string $key): bool
    {
        return isset($_SESSION[self::FLASH_KEY][$key]);
    }
    /**
     * [Description for getFlash]
     *
     * @param string $key
     * 
     * @return mixed|false
     * 
     * Created at: 11/24/2022, 2:54:22 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getFlash(string $key): mixed
    {
        if (!isset($_SESSION[self::FLASH_KEY][$key])) {
            return false;
        }
        $flashM = $_SESSION[self::FLASH_KEY][$key];
        $flashR = $_SESSION[self::FLASH_KEY][$key]['remove'];
        if ($flashR) {
            unset($_SESSION[self::FLASH_KEY][$key]);
        }
        return $flashM;
    }
    /**
     * [Description for set]
     *
     * @param string $key
     * @param mixed $value
     * 
     * @return void
     * 
     * Created at: 11/24/2022, 2:54:54 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics
     */
    public function set(string $key, $value): void
    {
        $to_set = [
            'time_set' => date('Y-m-d H:i:s'),
            'message' => $value,
        ];
        $_SESSION[$key] = $to_set;
    }

    /**
     * [Description for get]
     *
     * @param mixed $key
     * 
     * @return string|false
     * 
     * Created at: 11/24/2022, 2:55:02 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function get($key = '*'): string|false|array
    {
        if ($key == "*") {
            $toReturn = [];
            $_exclude = [self::FLASH_KEY];
            $session_keys = array_keys($_SESSION);
            foreach ($session_keys as $key) {
                if (!in_array($key, $_exclude, true))
                    $toReturn[$key] = $_SESSION[$key];
            }

            return  $toReturn;
        }

        return $_SESSION[$key] ?? false;
    }

    public function regenerate(bool $destroy = false)
    {
        session_regenerate_id($destroy);
    }

    public function getValue($key)
    {
        return $_SESSION[$key]['message'] ?? false;
    }

    /**
     * [Description for remove]
     *
     * @param mixed $key
     * 
     * @return void
     * 
     * Created at: 11/24/2022, 2:55:15 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function remove($key): void
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
