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

    public function handleException(string|int $code, Throwable $exception)
    {
        /**
         * @var Response $response
         */
        $response = app('response');
        $trace = $exception->getTrace();
        if (file_exists(ERROR_PATH . '_' . $code . '.php'))
            $response->setBody($this->renderCustomView(ERROR_PATH . '_' . $code, [
                'exception' => $exception
            ]))->send();
        else
            $response->setBody(
                $this->renderCustomView(ERROR_PATH . '_exceptions', [
                    'exception' => $exception,
                    'title'   => get_class($exception),
                    'type'    => get_class($exception),
                    'code'    => $code,
                    'message' => $exception->getMessage(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine(),
                    'trace'   => $trace,
                ])
            )->send();
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

    /**
     * Creates a syntax-highlighted version of a PHP file.
     *
     * @return bool|string
     */
    public static function highlightFile(string $file, int $lineNumber, int $lines = 15)
    {
        if (empty($file) || !is_readable($file)) {
            return false;
        }

        // Set our highlight colors:
        if (function_exists('ini_set')) {
            ini_set('highlight.comment', '#767a7e; font-style: italic');
            ini_set('highlight.default', '#c7c7c7');
            ini_set('highlight.html', '#06B');
            ini_set('highlight.keyword', '#f1ce61;');
            ini_set('highlight.string', '#869d6a');
        }

        try {
            $source = file_get_contents($file);
        } catch (Throwable $e) {
            return false;
        }

        $source = str_replace(["\r\n", "\r"], "\n", $source);
        $source = explode("\n", highlight_string($source, true));
        $source = str_replace('<br />', "\n", $source[1]);
        $source = explode("\n", str_replace("\r\n", "\n", $source));

        // Get just the part to show
        $start = max($lineNumber - (int) round($lines / 2), 0);

        // Get just the lines we need to display, while keeping line numbers...
        $source = array_splice($source, $start, $lines, true);

        // Used to format the line number in the source
        $format = '% ' . strlen((string) ($start + $lines)) . 'd';

        $out = '';
        // Because the highlighting may have an uneven number
        // of open and close span tags on one line, we need
        // to ensure we can close them all to get the lines
        // showing correctly.
        $spans = 1;

        foreach ($source as $n => $row) {
            $spans += substr_count($row, '<span') - substr_count($row, '</span');
            $row = str_replace(["\r", "\n"], ['', ''], $row);

            if (($n + $start + 1) === $lineNumber) {
                preg_match_all('#<[^>]+>#', $row, $tags);

                $out .= sprintf(
                    "<span class='line highlight'><span class='number'>{$format}</span> %s\n</span>%s",
                    $n + $start + 1,
                    strip_tags($row),
                    implode('', $tags[0])
                );
            } else {
                $out .= sprintf('<span class="line"><span class="number">' . $format . '</span> %s', $n + $start + 1, $row) . "\n";
            }
        }

        if ($spans > 0) {
            $out .= str_repeat('</span>', $spans);
        }

        return '<pre class="language-php"><code>' . $out . '</code></pre>';
    }
}
