<?php

namespace wizarphics\wizarframework\interfaces;

use wizarphics\wizarframework\http\Request;
use wizarphics\wizarframework\http\Response;

interface GeneratorInterface
{
    /**
     * Method for creating a file
     *
     * @param Request $request
     * @param Response $response
     * @param string|null $name
     * 
     * @return bool
     * 
     * Created at: 12/6/2022, 7:52:01 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function create(Request $request, Response $response, ?string $name = null): bool;
    /**
     * Method for getting a file template
     *
     * @param array $options
     * 
     * @return String
     * 
     * Created at: 12/6/2022, 7:45:48 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getTemp(?array $options = []): string;

    /**
     * Magic __Get method
     *
     * @param mixed $key
     * 
     * @return mixed
     * 
     * Created at: 12/6/2022, 7:56:11 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function __get($key): mixed;

    /**
     * [Description for getCleanName]
     *
     * @param string $rawName
     * 
     * @return string
     * 
     * Created at: 12/6/2022, 9:51:39 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getCleanName(string $rawName): string;

}
