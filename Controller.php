<?php
/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: $file.created
 * 6/30/22, 11:19 PM
 * Last Modified at: 6/30/22, 11:19 PM
 * Time: 11:19
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace wizarphics\wizarframework;

use wizarphics\wizarframework\middlewares\BaseMiddleware;

class Controller
{
    public string $layout = 'main';
    public string $action = '';    

    /**
     * @var \wizarphics\wizarframework\middlewares\BaseMiddleware[]
     */
    protected array $middlewares = [];

    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    public function render($view, $params = [])
    {
        return Application::$app->view->renderView($view, $params);
    }

    public function registerMiddleware(BaseMiddleware $middleware)
    {
        $this->middlewares[] = $middleware;
    }

	/**
	 * 
	 * @return array
	 */
	public function getMiddlewares(): array {
		return $this->middlewares;
	}
}
