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

namespace app\core;

use app\core\exception\NotFoundException;

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
        $path = $this->request->getPath();
        $method = $this->request->Method();
        if ($path != '/') {
            $callback = $this->getCallback($path, $method);
        } else {
            $callback = $this->routes[$method][$path] ?? false;
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

    public function getCallback($path, $method)
    {
        $path = rtrim($path, '/');
        $pathArr = explode('/', $path);
        $routes = $this->routes[$method];
        $args = array();
        $callback = '';
        $placeholderC = 0;
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
                                            // echo 'Pattern Matched ' . $pathArr[$key];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $callback = $routes[$rkey];
        }
        // var_dump($args, $placeholderC);
        // exit;
        $callback['args'] = $args;
        if (empty($args)) {
            return false;
        } else {
            if (count($args) == $placeholderC) {
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
}
