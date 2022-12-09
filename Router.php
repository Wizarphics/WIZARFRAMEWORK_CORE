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
        '(:num)' => '[0-9]+$',
        // '(:float)' => '/^\d+(\.\d{1,2})?/',
        '(:any)' => '[\w]+$',
        '(:alpha)' => '[a-zA-Z]+$',
        '(:alphaL)' => '[a-z]+$',
        '(:alphaU)' => '[A-Z]+$',
        '(:hex)' => '^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3}])$'
    ];

    /**
     * [Description for __construct]
     *
     * @param Request $request
     * @param Response $response
     * 
     * Created at: 11/24/2022, 2:21:00 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }



    /**
     * [Description for get]
     *
     * @param string $path
     * @param callable|\closure|array $callback
     * 
     * @return [type]
     * 
     * Created at: 11/24/2022, 2:36:59 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function get(string $path, callable|\closure|array $callback)
    {
        $this->routes['get'][$path] = $callback;
    }

    /**
     * [Description for resolve]
     *
     * @return \Exception|array|string|void
     * 
     * Created at: 11/24/2022, 1:07:04 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function resolve()
    {
        if (empty($this->routes)) {
            throw new NotFoundException('No route has been defined');
        }
        $path = $this->request->getPath();
        $method = $this->request->Method();

        $callback = $this->routes[$method][$path] ?? false;
        if ($callback === false) {
            $callback = $method == 'cli' ? $this->handleCliCallback($path) : $this->getCallback();

            if ($callback === false) {
                // return $this->renderOnlyView('_errors/_404', []);
                throw new NotFoundException;
            }
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
                if (is_assoc($args)) {
                    $args['request'] = $this->request;
                    $args['response'] = $this->response;
                } else {
                    array_push($args, $this->request, $this->response);
                }
                unset($callback['args']);

                return call_user_func_array($callback, $args);
            } else {
                return call_user_func($callback, $this->request, $this->response);
            }
        } elseif (is_callable($callback)) {
            return call_user_func($callback);
        } elseif (is_string($callback)) {
            return Application::$app->view->renderView($callback);
        } else {
            throw new \BadMethodCallException;
        }
    }


    /**
     * [Description for getCallback]
     *
     * @param string|null $path
     * 
     * @return array|bool
     * 
     * Created at: 11/24/2022, 1:08:43 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getCallback(?string $path = null): array|bool
    {
        $path = $path ?? $this->request->getPath();
        $method = $this->request->Method();
        // Trim all slashes
        $url = trim($path, '/');

        //Get all routes for current request
        $routes = $this->routes[$method] ?? [];

        $routeParams = false;
        // print '<pre>';
        // var_dump($routes);
        // print '</pre>';
        // exit;
        //Start iterating over registered routes
        foreach ($routes as $route => $callback) {
            // Trim all slashes
            $route = trim($route, '/');

            // Replace defined placeholders
            $route = str_replace(array_keys($this->definedPlaceholder), array_values($this->definedPlaceholder), $route);
            $routeNames = [];

            if (!$route) {
                continue;
            }

            // Find all route names from route and save in $routeNames
            if (preg_match_all('/\{(\w+)(:[^}]+)?}/', $route, $matches)) {
                $routeNames = $matches[1];
            }

            // Convert route name into regex pattern
            $routeRegrex = "@^" . preg_replace_callback('/\{\w+(:([^}]+))?}/', fn ($m) => isset($m[2]) ? "({$m[2]})" : '(\w+)', $route) . "$@";

            // Test and match current route against $routeRegex
            if (preg_match_all($routeRegrex, $url, $valueMatches)) {
                $values = [];
                for ($i = 1; $i < count($valueMatches); $i++) {
                    $values[] = $valueMatches[$i][0];
                }
                $routeParams = array_combine($routeNames, $values);


                $callback['args'] = $routeParams;
                $this->request->setRouteArgs($routeParams);
                return $callback;
            }
        }

        return false;

        // throw new NotFoundException();
    }

    /**
     * [Description for handleCliCallback]
     *
     * @param string $path
     * 
     * @return array|bool
     * 
     * Created at: 11/24/2022, 1:09:33 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function handleCliCallback(string $path)
    {
        $routeArgs = $_SERVER['argv'];
        unset($routeArgs[0]);
        $mergePath = array_unique(array_merge($routeArgs, [$path]));
        $newPath = str_replace('\\', '__cli__', join('/', $mergePath));
        $callback = $this->getCallback($newPath);
        return $callback;
        // \dd(get_defined_vars());
    }


    /**
     * [Description for getOldCallback]
     *
     * @param mixed $path
     * @param mixed $method
     * 
     * @return array|bool
     * 
     * Created at: 11/24/2022, 1:09:51 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     * @deprecated 
     */
    public function getOldCallback($path, $method)
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

    /**
     * [Description for post]
     *
     * @param string $path
     * @param callable|\closure|array $callback
     * 
     * 
     * Created at: 11/24/2022, 1:10:27 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function post(string $path, callable|\closure|array $callback)
    {
        $this->routes['post'][$path] = $callback;
    }


    /**
     * [Description for cli]
     *
     * @param string $path
     * @param callable|\closure|array $callback
     * 
     * 
     * Created at: 11/24/2022, 1:13:43 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function cli(string $path, callable|\closure|array $callback)
    {
        $this->routes['cli'][$path] = $callback;
    }

    /**
     * [Description for getPost]
     *
     * @param string $path
     * 
     * 
     * Created at: 11/24/2022, 1:14:08 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getPost(string $path, callable|\closure|array $callback)
    {
        $this->routes['get'][$path] = $callback;
        $this->routes['post'][$path] = $callback;
    }

    /**
     * [Description for delete]
     *
     * @param string $path
     * @param callable|\closure|array $callback
     * 
     * 
     * Created at: 11/24/2022, 2:20:08 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function delete(string $path, callable|\closure|array $callback)
    {
        $this->routes['delete'][$path] = $callback;
    }
}
