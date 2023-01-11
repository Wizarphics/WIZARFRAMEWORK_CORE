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

use RuntimeException;
use wizarphics\wizarframework\exception\NotFoundException;
use wizarphics\wizarframework\http\Request;
use wizarphics\wizarframework\http\Response;
use wizarphics\wizarframework\interfaces\ResponseInterface;

class Router
{
    public Request $request;
    public Response $response;
    protected array $routes = [];

    private string $method;

    private array $definedPlaceholder = [
        '(:num)' => '[0-9]+$',
        // '(:float)' => '/^\d+(\.\d{1,2})?/',
        // '(:any)' => '[\w]+$',
        '(:any)' => '[^\n]+$',
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
     * @return self
     * 
     * Created at: 11/24/2022, 2:36:59 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function get(string $path, callable|\closure|array $callback)
    {
        $this->create('get', $path, $callback);
        return $this;
    }

    /**
     * [Description for resolve]
     *
     * @return \Exception|array|string|void|ResponseInterface
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
        $path = urldecode($this->request->getPath());
        $method = $this->request->Method();

        $callback = $this->routes[$method][$path] ?? false;
        if ($callback === false) {
            $callback = $method == 'cli' ? $this->handleCliCallback($path) : $this->getCallback();

            if ($callback === false) {
                // return $this->renderOnlyView('_errors/_404', []);
                throw new NotFoundException('Route for "' . $path . '" not found.');
            }
        }


        $callbac = current($callback['route']);

        $args = [];
        if (array_key_exists('args', $callback)) {
            $args = $callback['args'];
            if (is_assoc($args)) {
                $args['request'] = $this->request;
                $args['response'] = $this->response;
            } else {
                array_push($args, $this->request, $this->response);
            }
            unset($callback['args']);
        }

        if (is_array($callbac)) {
            /**
             * @var Controller $controller
             */
            $controller = new $callbac[0]();
            Application::$app->controller = $controller;
            $controller->action = $callbac[1];
            $callbac[0] = $controller;

            foreach ($controller->getMiddlewares() as $middleware) {
                $response = $middleware->execute($this->request, $this->response);
                if ($response instanceof ResponseInterface)
                    return $response;
            }

            if (class_exists($controller::class)) {
                if (!method_exists($controller, $callbac[1])) {
                    throw new \BadMethodCallException($controller::class . ' does not have method ' . $callbac[1], 400);
                }
            } else {
                throw new \BadMethodCallException('Class ' . $controller::class . ' does not exist', 400);
            }

            if ($args !== []) {
                return $controller->{$callbac[1]}(...$args);
            } else {
                return $controller->{$callbac[1]}($this->request, $this->response);
            }
        } elseif (is_callable($callbac)) {
            return call_user_func($callbac, ...$args);
        } elseif (is_string($callbac)) {
            return Application::$app->view->renderView($callbac, $args);
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
        $path = urldecode($path ?? $this->request->getPath());
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


    protected function create(string|array $verb, string $from, $to, ?array $options = [])
    {
        $verbs = (array) $verb;
        foreach ($verbs as $verb) {
            $name = $options['name'] ?? $from;
            $this->routes[$verb][$name] = [
                'route' => [$from => $to],
            ];
            $this->method = $verb;
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
        $this->create('post', $path, $callback);
        return $this;
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
        $this->create('cli', $path, $callback);
        return $this;
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
        $this->create(['get', 'post'], $path, $callback);
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
        $this->create('delete', $path, $callback);
        return $this;
    }

    public function name($name)
    {
        $this->routes[$this->method][$name] = end($this->routes[$this->method]);
    }

    /**
     * Attempts to look up a route based on its destination.
     *
     * If a route exists:
     *
     *      'path/(:any)/(:any)' => 'Controller::method/$1/$2'
     *
     * This method allows you to know the Controller and method
     * and get the route that leads to it.
     *
     *      // Equals 'path/$param1/$param2'
     *      reverseRoute('Controller::method', $param1, $param2);
     *
     * @param string     $search    Named route or Controller::method
     * @param int|string ...$params One or more parameters to be passed to the route
     *
     * @return false|string
     */
    public function getRouteTo(string $search, ...$params)
    {
        // Named routes get higher priority.
        foreach ($this->routes as $collection) {
            if (array_key_exists($search, $collection)) {
                $route = $this->fillRouteParams(key($collection[$search]['route']), $params);

                return ($route);
            }
        }

        // If it's not a named route, then loop over
        // all routes to find a match.
        foreach ($this->routes as $collection) {
            foreach ($collection as $route) {
                $from = key($route['route']);
                $to   = $route['route'][$from];

                // ignore closures
                if (!is_string($to)) {
                    continue;
                }

                // Lose any namespace slash at beginning of strings
                // to ensure more consistent match.
                $to     = ltrim($to, '\\');
                $search = ltrim($search, '\\');

                // If there's any chance of a match, then it will
                // be with $search at the beginning of the $to string.
                if (strpos($to, $search) !== 0) {
                    continue;
                }

                // Ensure that the number of $params given here
                // matches the number of back-references in the route
                if (substr_count($to, '$') !== count($params)) {
                    continue;
                }

                $route = $this->fillRouteParams($from, $params);

                return ($route);
            }
        }

        // If we're still here, then we did not find a match.
        return false;
    }

    /**
     * Given a
     *
     * @throws RuntimeException
     */
    protected function fillRouteParams(string $from, ?array $params = null): string
    {
        // Find all of our back-references in the original route
        preg_match_all('/\(([^)]+)\)/', $from, $matches);

        if (empty($matches[0])) {
            return '/' . ltrim($from, '/');
        }

        // Build our resulting string, inserting the $params in
        // the appropriate places.
        foreach ($matches[0] as $index => $pattern) {
            if (!preg_match('#^' . $pattern . '$#u', $params[$index])) {
                throw new RuntimeException('A parameter does not match the expected type.');
            }

            // Ensure that the param we're inserting matches
            // the expected param type.
            $pos  = strpos($from, $pattern);
            $from = substr_replace($from, $params[$index], $pos, strlen($pattern));
        }

        return '/' . ltrim($from, '/');
    }
}
