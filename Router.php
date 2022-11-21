<?php
/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: $file.created
 * 6/30/22, 6:30 PM
 * Last Modified at: 6/30/22, 6:30 PM
 * Time: 6:30
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace wizarphics\wizarframework;

use wizarphics\wizarframework\exception\NotFoundException;

class Router
{
    public Request $request;
    public Response $response;
    protected array $routes = [];
    private array $definedPlaceholder = [
        '(:num)' => '/^[0-9]+$/',
        '(:float)' => '/^\d+(\.\d{1,2})?/',
        '(:any)' => '/^[\w]+$/',
        '(:alphaL)' => '/^[a-z]+$/',
        '(:alphaU)' => '/^[A-Z]+$/',
    ];

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }


    public function get($path, $callback)
    {
        $this->routes['get'][$path] = $callback;
    }

    public function resolve()
    {
        if (empty($this->routes)) {
            throw new NotFoundException('No route has been defined');
        }
        $path = $this->request->getPath();
        $method = $this->request->Method();
        if ($path == '/') {
            $callback = $this->routes[$method][$path] ?? false;
        } else {
            if ($method == 'cli') {
                $callback = $this->handleCliCallback($path);
            } else {
                $callback = $this->getCallback($path, $method);
            }
        }


        if ($callback === false) {
            // return $this->renderOnlyView('_errors/_404', []);
            throw new NotFoundException();
        }
        if (is_string($callback)) {
            return Application::$app->view->renderView($callback);
        }
        if (is_array($callback)) {
            /**
             * @var Controller $controller
             */
            $controller = new $callback[0]();
            Application::$app->controller = $controller;
            $controller->action = $callback[1];
            $callback[0] = $controller;

            foreach ($controller->getMiddlewares() as $middleware) {
                $middleware->execute();
            }

            if (array_key_exists('args', $callback)) {
                $args = $callback['args'];
                array_push($args, $this->request, $this->response);
                unset($callback['args']);

                return call_user_func_array($callback, $args);
            } else {
                return call_user_func($callback, $this->request, $this->response);
            }
        }
    }

    public function handleCliCallback(string $path)
    {
        $path = rtrim($path, '/');
        $pathArr = explode('/', $path);
        $routes = $this->routes['cli'] ?? false;
        if ($routes === false) {
            return false;
        }
        $passArg = $_SERVER['argv'];
        unset($passArg[0]);
        $args = array();
        $callback = '';
        $placeholderC = 0;
        $selecteRoute = '';
        foreach ($routes as $rkey => $value) {
            if ($rkey == '/') continue;
            $routeArr = explode('/', $rkey);
            if (count($routeArr) == count($passArg)) {
                if ($path == $rkey) {
                    return $value;
                } else {
                    if (array_equality($routeArr, $passArg)) {
                        return $value;
                    } else {
                        $i = 0;
                        foreach ($routeArr as $key => $value) {
                            $i++;
                            echo $i.PHP_EOL, $value.PHP_EOL, $passArg[$key].PHP_EOL;
                            if (array_key_exists($value, $this->definedPlaceholder)) {
                                foreach ($this->definedPlaceholder as $pkey => $placeholder) {
                                    if ($value == $pkey) {
                                        $placeholderC++;
                                        if (preg_match($placeholder, $passArg[$key])) {
                                            $args[] = $passArg[$key];
                                            $selecteRoute = $rkey;
                                            echo 'Pattern Matched ' . $placeholder . ' = ' . $pathArr[$key] . PHP_EOL;
                                        }
                                    } else {
                                        // echo 'Pattern gone through 1 not matched.' . $value . ' ' . $passArg[$key] . PHP_EOL;
                                    }
                                }
                            } else {
                                foreach ($this->definedPlaceholder as $placeholder) {
                                    if ($value == $placeholder) {
                                        $placeholderC++;
                                        if (preg_match($placeholder, $passArg[$key])) {
                                            array_push($args, $passArg[$key]);
                                            $selecteRoute = $rkey;
                                            echo 'Pattern Matched ' . $passArg[$key] . PHP_EOL;
                                        }
                                    } else {
                                        // echo 'Pattern gone through 2 not matched.' . $value . ' ' . $placeholder . ' ' . $key . ' ' . $value . PHP_EOL;
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                dd($routeArr, $passArg);
                echo count($routeArr) . PHP_EOL, count($passArg);
                exit;
            }
        }
        exit;
    }
    public function getCallback($path, $method)
    {
        $path = rtrim($path, '/');
        $pathArr = explode('/', $path);
        $routes = $this->routes[$method] ?? false;
        if ($routes === false) {
            return false;
        }
        $args = array();
        $callback = '';
        $placeholderC = 0;
        $selecteRoute = '';
        foreach ($routes as $rkey => $value) {
            if ($rkey == '/') continue;
            if ($path == $rkey) {
                return $value;
            } else {
                $routeArr = explode('/', $rkey);
                if (count($routeArr) == count($pathArr)) {
                    if ($routeArr === $pathArr) {
                        return $value;
                    } else {
                        foreach ($routeArr as $key => $value) {
                            if (array_key_exists($value, $this->definedPlaceholder)) {
                                foreach ($this->definedPlaceholder as $pkey => $placeholder) {
                                    if ($value == $pkey) {
                                        $placeholderC++;
                                        if (preg_match($placeholder, $pathArr[$key])) {
                                            $args[] = $pathArr[$key];
                                            $selecteRoute = $rkey;
                                            // echo 'Pattern Matched ' . $placeholder . ' = ' . $pathArr[$key] . '<br>';
                                        }
                                    }
                                }
                            } else {
                                foreach ($this->definedPlaceholder as $placeholder) {
                                    if ($value == $placeholder) {
                                        $placeholderC++;
                                        if (preg_match($placeholder, $pathArr[$key])) {
                                            array_push($args, $pathArr[$key]);
                                            $selecteRoute = $rkey;
                                            // echo 'Pattern Matched ' . $pathArr[$key];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($selecteRoute == null)
            return false;
        $callback = $routes[$selecteRoute];
        $callback['args'] = $args;
        // dd($callback);
        if (empty($args)) {
            return false;
        } else {
            if (count($callback['args']) == $placeholderC) {
                return $callback;
            } else {
                return false;
            }
        }
    }

    public function post(string $path, $callback)
    {
        $this->routes['post'][$path] = $callback;
    }

    public function cli(string $path, $callback)
    {
        $this->routes['cli'][$path] = $callback;
    }
}
