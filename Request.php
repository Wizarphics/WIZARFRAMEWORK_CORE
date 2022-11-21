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
     * File collection
     *
     * @var FileCollection|null
     */
    protected $files;
    public function getPath()
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

    public function Method()
    {
        if (is_cli()) {
            return 'cli';
        }
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public function getBody()
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


    public function getFiles()
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
     * @return array|null
     */
    public function getFileMultiple(string $fileID)
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
     * @return FileUploaded|null
     */
    public function getFile(string $fileID)
    {
        if ($this->files === null) {
            $this->files = new FileCollection();
        }

        return $this->files->getFile($fileID);
    }

    public function isGet()
    {
        return $this->Method() === 'get';
    }

    public function isPost()
    {
        return $this->Method() === 'post';
    }
}
