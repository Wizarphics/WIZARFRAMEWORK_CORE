<?php
/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: 16/11/22, 4:20 PM
 * Last Modified at: 16/11/22, 4:20 PM
 * Time: 4:20
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace wizarphics\wizarframework;

use RuntimeException;
use Throwable;
use wizarphics\wizarframework\exception\NotFoundException;
use wizarphics\wizarframework\http\Response;

class View
{
    public string $title = '';


    /**
     * Holds the sections and their data.
     *
     * @var array
     */
    protected $sections = [];

    /**
     * The name of the current section being rendered,
     * if any.
     *
     * @var array<string>
     */
    protected $sectionStack = [];

    public function renderView($view, $params = [])
    {
        $viewContent = $this->renderOnlyView($view, $params);
        $layoutContent = $this->layoutContent();

        return str_replace('{{content}}', $viewContent, $layoutContent);
    }

    protected function layoutContent(?string $layout = null)
    {
        $controllerlayout = Application::$app->layout;
        if (Application::$app->controller) {
            $controllerlayout = Application::$app->controller->layout;
        }
        $layout ??= $controllerlayout;
        ob_start();
        include_once VIEWPATH . "layouts/" . $this->getRealPath($layout);
        return ob_get_clean();
    }

    protected function renderOnlyView($view, $params): bool|string
    {
        foreach ($params as $key => $value) {
            $$key = $value;
        }
        ob_start();
        $file = VIEWPATH . $this->getRealPath($view);
        if (!file_exists($file)) {
            throw new NotFoundException("$file not found");
        }
        include_once VIEWPATH . $this->getRealPath($view);
        return ob_get_clean();
    }

    public function renderContent($viewContent)
    {
        $layoutContent = $this->layoutContent();
        return str_replace('{{content}}', $viewContent, $layoutContent);
    }


    protected function getRealPath(string $view): string
    {
        $fileExt                     = pathinfo($view, PATHINFO_EXTENSION);
        $realPath                    = empty($fileExt) ? $view . '.php' : $view; // allow Views as .html, .tpl, etc
        return $realPath;
    }

    public function renderCustomView(string $view, array $params = [])
    {
        foreach ($params as $key => $value) {
            $$key = $value;
        }
        ob_start();
        include_once $this->getRealPath($view);
        return ob_get_clean();
    }

    public function renderViewComponent(string $view, string $layout, array $params = []): string
    {
        $viewContent = $this->renderView($view, $params);
        $layoutContent = $this->layoutContent($layout);

        return str_replace('{{content}}', $viewContent, $layoutContent);
    }

    /**
     * Starts holds content for a section within the layout.
     *
     * @param string $name Section name
     */
    public function section(string $name)
    {
        $this->sectionStack[] = $name;

        ob_start();
    }

    /**
     * Captures the last section
     *
     * @throws RuntimeException
     */
    public function endSection()
    {
        $contents = ob_get_clean();

        if ($this->sectionStack === []) {
            throw new RuntimeException('View themes, no current section.');
        }

        $section = array_pop($this->sectionStack);

        // Ensure an array exists so we can store multiple entries for this.
        if (!array_key_exists($section, $this->sections)) {
            $this->sections[$section] = [];
        }

        $this->sections[$section][] = $contents;
    }

    /**
     * Renders a section's contents.
     */
    public function yieldSection(string $sectionName)
    {
        if (!isset($this->sections[$sectionName])) {
            echo '';

            return;
        }

        foreach ($this->sections[$sectionName] as $key => $contents) {
            echo $contents;
            unset($this->sections[$sectionName][$key]);
        }
    }

    /**
     * Used within layout views to include additional views.
     *
     * @param bool $saveData
     */
    public function include(string $view, array $params = []): string
    {
        return $this->renderOnlyView($view, $params);
    }
}
