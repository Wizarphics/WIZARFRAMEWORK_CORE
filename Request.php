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

namespace wizarphics\wizarframework;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Sage;
use wizarphics\wizarframework\files\FileCollection;
use wizarphics\wizarframework\files\FileUploaded;

class Request
{

    /**
     * @var array
     */
    private array $routeArgs;
    /**
     * File collection
     *
     * @var FileCollection|null
     */
    protected $files;
    /**
     * [Description for getPath]
     *
     * @return string|false
     * 
     * Created at: 11/24/2022, 2:25:48 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getPath():string|false
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        if($this->Method() == 'cli'){
            $path = $_SERVER['argv'][1]??'/';
        }
        $position = strpos($path, '?');
        if ($position === false) {

            return $path;
        }
        return substr($path, 0, $position);
    }

    /**
     * [Description for Method]
     *
     * @return string
     * 
     * Created at: 11/24/2022, 2:26:38 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function Method():string
    {
        if (is_cli()) {
            return 'cli';
        }
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    /**
     * [Description for getBody]
     *
     * @return array
     * 
     * Created at: 11/24/2022, 2:27:34 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getBody():array
    {
        $body = [];
        if ($this->Method() === 'get') {
            foreach ($_GET as $key => $value) {
                $body[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        if ($this->Method() === 'post') {
            foreach ($_POST as $key => $value) {
                if (is_array($value)) {
                    $body[$key] = [];
                    foreach ($value as $vKey => $Kvalue) {
                        $body[$key][$vKey] = filter_var($Kvalue, FILTER_SANITIZE_SPECIAL_CHARS);
                    }
                } else {
                    $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
                }
            }
        }

        return $body;
    }


    /**
     * [Description for getFiles]
     *
     * @return array|null
     * 
     * Created at: 11/24/2022, 2:28:04 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getFiles():array|null
    {
        if ($this->files === null) {
            $this->files = new FileCollection();
        }

        return $this->files->all(); //return all uploaded files
    }


    /**
     * Verify if a file exist, by the name of the input field used to upload it, in the collection
     * of uploaded files and if is have been uploaded with multiple option.
     *
     * @param string $fileID
     * 
     * @return array|null
     * 
     * Created at: 11/24/2022, 2:29:18 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getFileMultiple(string $fileID):array|null
    {
        if ($this->files === null) {
            $this->files = new FileCollection();
        }

        return $this->files->getFileMultiple($fileID);
    }

    /**
     * Retrieves a single file by the name of the input field used
     * to upload it.
     *
     * @param string $fileID
     * 
     * @return FileUploaded|null
     * 
     * Created at: 11/24/2022, 2:28:40 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getFile(string $fileID):FileUploaded|null
    {
        if ($this->files === null) {
            $this->files = new FileCollection();
        }

        return $this->files->getFile($fileID);
    }

    /**
     * [Description for isGet]
     *
     * @return bool
     * 
     * Created at: 11/24/2022, 2:30:00 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function isGet():bool
    {
        return $this->Method() === 'get';
    }

    /**
     * [Description for isPost]
     *
     * @return bool
     * 
     * Created at: 11/24/2022, 2:30:05 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function isPost():bool
    {
        return $this->Method() === 'post';
    }

	/**
	 * [Description for getRouteArgs]
	 *
	 * @return array
	 * 
	 * Created at: 11/24/2022, 2:30:19 PM (Africa/Lagos)
	 * @author     Wizarphics <wizarphics@gmail.com> 
	 * @see       {@link https://wizarphics.com} 
	 * @copyright Wizarphics 
	 */
	public function getRouteArgs(): array {
		return $this->routeArgs;
	}

	/**
	 * [Description for setRouteArgs]
	 *
	 * @param array $routeArgs
	 * 
	 * @return self
	 * 
	 * Created at: 11/24/2022, 2:30:28 PM (Africa/Lagos)
	 * @author     Wizarphics <wizarphics@gmail.com> 
	 * @see       {@link https://wizarphics.com} 
	 * @copyright Wizarphics 
	 */
	public function setRouteArgs(array $routeArgs): self {
		$this->routeArgs = $routeArgs;
		return $this;
	}
}
