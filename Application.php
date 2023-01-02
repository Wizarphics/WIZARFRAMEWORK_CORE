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

use Throwable;
use wizarphics\wizarframework\db\Database;
use wizarphics\wizarframework\exception\NotFoundException;
use wizarphics\wizarframework\http\Request;
use wizarphics\wizarframework\http\Response;
use wizarphics\wizarframework\language\Language;

class Application
{
    const EVENT_BEFORE_REQUEST = 'beforeRequest';
    const EVENT_AFTER_REQUEST = 'afterRequest';

    protected string $appConfigNameSpace;
    protected string $appModelNameSpace;

    protected array $eventListeners = [];

    public static string $ROOT_DIR;
    public static string $CORE_DIR = (__DIR__) . DIRECTORY_SEPARATOR;

    public string $layout = 'main';

    public string $userClass;
    public static Application $app;
    public ?Controller $controller = null;
    public Request $request;
    public Router $router;
    public Response $response;
    public Database $db;
    public Session $session;
    public const VERSION = "1.0.5.03";
    public View $view;
    public Language $lang;

    public function __construct($rootPath, array $config)
    {
        $this->userClass = $config['userClass'];
        self::$ROOT_DIR = $rootPath;
        self::$app = $this;
        $this->request = new Request();
        $this->response = new Response();
        $this->session = new Session();
        $this->router = new Router($this->request, $this->response);
        $this->view = new View();
        $requestLocale = $this->request->getLocale();
        $locale = $config['locale'] ?? $requestLocale;
        $this->lang = new Language($locale);
        $this->db = Database::getInstance($config['db']);
        $this->layout = $config['layout'] ?? 'main';
        $this->appConfigNameSpace = $config['appConfigNameSpace'] ?? '\app\\configs\\';
        $this->appModelNameSpace = $config['appModelNameSpace'] ?? '\app\\models\\';
    }

    public function handleExceptions(Throwable $e)
    {
        log_message('error', [$e->getMessage(), $e->getTraceAsString()]);
        $code = $e->getCode();

        if (is_cli()) :
            echo $e->getCode();
            exit;
        else :
            $this->view->handleException($code, $e);
        endif;
    }

    public function run()
    {
        set_exception_handler([$this, 'handleExceptions']);
        $this->triggerEvent(self::EVENT_BEFORE_REQUEST);
        try {
            $response = $this->router->resolve();
            if ($response instanceof Response) {
                $response->send();
            } else {
                echo $response;
            }
        } catch (Throwable $e) {
            $code = is_numeric($code = $e->getCode()) ? (int) $code : 500;
            $this->response->setStatusCode($code, '', $e)->send();
            $this->handleExceptions($e);
        }
        $this->triggerEvent(self::EVENT_AFTER_REQUEST);
    }

    public function triggerEvent($eventName, ...$args)
    {
        $callbacks = $this->eventListeners[$eventName] ?? [];
        foreach ($callbacks as $callback) {
            call_user_func($callback, ...$args);
        }
    }

    public function on($eventName, $callback)
    {
        $this->eventListeners[$eventName][] = $callback;
    }

    /**
     * @return Controller
     */
    public function getController(): Controller
    {
        return $this->controller;
    }

    /**
     * @param Controller $controller
     */
    public function setController(Controller $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * [Description for getConfig]
     *
     * @param string $classname
     * @param From $from
     * @param null ...$constructorArg
     * 
     * @return object
     * 
     * @throws NotFoundException
     * 
     * Created at: 1/2/2023, 2:05:33 AM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getConfig(string $classname, From $from = From::any, ...$constructorArg): object
    {

        $appClass = $this->appConfigNameSpace . $classname;
        $coreClass = __NAMESPACE__ . '\\configs\\' . $classname;

        if ($from != From::any) {
            $chosen = $$from . 'Class';
            if (class_exists($chosen)) {
                return new $chosen(...$constructorArg);
            } else {
                throw new NotFoundException('No config class found for ' . $classname . ' was found in the for ' . $from, 400);
            }
        }

        if (class_exists($appClass)) {
            return new $appClass(...$constructorArg);
        }

        if (class_exists($coreClass)) {
            return new $coreClass(...$constructorArg);
        }

        throw new NotFoundException('No config class found for ' . $classname, 400);
    }

    /**
     * [Description for getModel]
     *
     * @return Model
     * 
     * Created at: 1/2/2023, 2:25:03 AM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getModel(string $classname, From $from = From::any, ...$constructorArg): Model
    {
        $appModel = $this->appModelNameSpace . $classname;
        $coreModel = __NAMESPACE__ . '\\models\\' . $classname;

        if ($from != From::any) {
            $chosen = $$from . 'Model';
            if (class_exists($chosen)) {
                return new $chosen(...$constructorArg);
            } else {
                throw new NotFoundException('No model class found for ' . $classname . ' was found in the for ' . $from, 400);
            }
        }

        if (class_exists($appModel)) {
            return new $appModel(...$constructorArg);
        }

        if (class_exists($coreModel)) {
            return new $coreModel(...$constructorArg);
        }

        throw new NotFoundException('No model class found for ' . $classname, 400);
    }
}


enum From: string
{
    case app = 'app';
    case core = 'core';
    case any = 'any';
    case both = 'both';
}
