<?php
declare(strict_types=1);

namespace wizarphics\wizarframework;

use ErrorException;
use Throwable;
use wizarphics\wizarframework\exception\NotFoundException;
use wizarphics\wizarframework\http\Request;
use wizarphics\wizarframework\http\Response;
use wizarphics\wizarframework\interfaces\RequestInterface;
use wizarphics\wizarframework\interfaces\ResponseInterface;
use wizarphics\wizarframework\traits\ApiResponseTrait;

class Exception
{
    use ApiResponseTrait;

    /**
     * Nesting level of the output buffering mechanism
     *
     * @var int
     */
    public $ob_level;
    protected RequestInterface|Request $request;
    protected ResponseInterface|Response $response;
    protected string $viewPath;
    protected array $codesToIgnore=[404];
    
    
    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param string $viewPath
     */
    public function __construct(RequestInterface $request, ResponseInterface $response, string $viewPath) {
        $this->ob_level = ob_get_level();
        $this->viewPath = $viewPath;
    	$this->request = $request;
    	$this->response = $response;
    	$this->viewPath = $viewPath;
    }

    /**
     * Responsible for setting handlers for
     * application error, exception and shutdown
     * 
     * @return void
     */
    public function setUp():void
    {
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Handles application exception
     * 
     * @param Throwable $exception
     * 
     * @return void
     */
    public function handleException(Throwable $exception)
    {
        [$statusCode, $exitCode]=$this->assertCode($exception);
        if (LOG_EXCEPTION == true && !in_array($statusCode, $this->codesToIgnore, true)) {
            log_message('critical', __("{message}\nin {exceptionFile} on line {exceptionLine}.\n{trace}", [
                'message' => $exception->getMessage(),
                'exceptionFile' => clean_path($exception->getFile()),
                'exceptionLine' => $exception->getLine(),
                'trace' => self::printTrace($exception->getTrace()),
            ]));
        }

        if(! is_cli()){
            try {
                $this->response->setStatusCode($statusCode);
            } catch (Throwable $e) {
                // Workaround for invalid status code.
                $statusCode = 500;
                $this->response->setStatusCode($statusCode);
            }
        }

        if(!headers_sent()){
            Header(sprintf('HTTP/%s %s %s', $this->request->getProtocolVersion(), $this->response->getStatusCode(), $this->response->getReason()), true, $statusCode);
        }

        if (strpos($this->request->getHeaderLine('accept'), 'text/html') === false) {
            $this->respond(ENVIRONMENT === 'development' ? $this->fecthVariables($exception, $statusCode) : '', $statusCode)->send();

            exit($exitCode);
        }

        $this->renderView($exception, $statusCode);

        exit($exitCode);
    }

    protected function assertView(Throwable $thrownException, string $viewPath): string
    {
        // If the app is in a production environment don't debug exception
        if (str_ireplace(['off', 'none', 'no', 'false', 'null'], '', ini_get('display_errors'))) {
            $view = '_exceptions.php';
        }

        // 404 Errors
        if($thrownException instanceof NotFoundException){
            return '_404.php';
        }

        // Allow for custom views based upon the status code
        if (is_file($viewPath . '_' . $thrownException->getCode() . '.php')) {
            return '_' . $thrownException->getCode() . '.php';
        }

        return $view;
    }

    protected function renderView(Throwable $exception, $statusCode)
    {
        $viewPath = $this->viewPath;
        $defaultPath = rtrim(VIEWPATH, '\\/') . DIRECTORY_SEPARATOR.'errors'.DIRECTORY_SEPARATOR;
        if(is_cli()){
            return;
        }

        $view = $this->assertView($exception, $viewPath);
        $defaultView = $this->assertView($exception, $defaultPath);

        if(is_file($viewPath.$view)){
            $viewFile = $viewPath.$view;
        }elseif(is_file($defaultPath.$defaultView)){
            $viewFile = $defaultPath.$defaultView;
        }

        if(!isset($viewFile)){
            print "The error view file is not found. Unable to render exception trace.";
            exit(1);
        }

        if(ob_get_level() > $this->ob_level+1){
            ob_end_clean();
        }

        echo(function()use($exception, $statusCode, $viewFile):string{
            $data = $this->fecthVariables($exception, $statusCode);
            extract($data, EXTR_SKIP);

            ob_start();
            include $viewFile;
            return ob_get_clean();
        })();
    }

    protected function fecthVariables(Throwable $throwable, int $statusCode):array
    {
        $trace = $throwable->getTrace();
        return [
            'title' => $throwable->getMessage(),
            'type' => get_class($throwable),
            'code' => $statusCode,
            'message' => $throwable->getMessage(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => $trace,
        ];
    }

    /**
     * Impose the Http status code and exit code for the request
     * 
     * @param Throwable $exception
     * @return array<int, int>
     */
    protected function assertCode(Throwable $exception):array
    {
        $statusCode = abs($exception->getCode());
        if ($statusCode < 100 || $statusCode > 599){
            $exitStatus = $statusCode + EXIT__AUTO_MIN;

            if($exitStatus > EXIT__AUTO_MAX){
                $exitStatus = EXIT_ERROR;
            }

            $statusCode = 500;
        }else{
            $exitStatus = EXIT_ERROR;
        }

        return [$statusCode, $exitStatus];
    }

    /**
     * Prints trace back as string
     * @param array $backtrace
     * @return string
     */
    private function printTrace(array $backtrace): string
    {
        $backtraces = [];

        foreach ($backtrace as $index => $trace) {
            $frame = $trace + ['file' => '[internal function]', 'line' => '', 'class' => '', 'type' => '', 'args' => []];

            if ($frame['file'] !== '[internal function]') {
                $frame['file'] = sprintf('%s(%s)', $frame['file'], $frame['line']);
            }

            unset($frame['line']);
            $idx = $index;
            $idx = str_pad((string) ++$idx, 2, ' ', STR_PAD_LEFT);

            $args = implode(', ', array_map(static function ($value): string {
                switch (true) {
                    case is_object($value):
                        return sprintf('Object(%s)', get_class($value));

                    case is_array($value):
                        return $value !== [] ? '[...]' : '[]';

                    case $value === null:
                        return 'null';

                    case is_resource($value):
                        return sprintf('resource (%s)', get_resource_type($value));

                    case is_string($value):
                        return var_export(clean_path($value), true);

                    default:
                        return var_export($value, true);
                }
            }, $frame['args']));

            $backtraces[] = sprintf(
                '%s %s: %s%s%s(%s)',
                $idx,
                clean_path($frame['file']),
                $frame['class'],
                $frame['type'],
                $frame['function'],
                $args
            );
        }

        return implode("\n", $backtraces);
    }

    /**
     * Creates a syntax-highlighted version of a PHP file.
     *
     * @return bool|string
     */
    public static function highlightFile(string $file, int $lineNumber, int $lines = 15)
    {
        if (empty($file) || ! is_readable($file)) {
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

    /**
     * Describes memory usage in real-world units. Intended for use
     * with memory_get_usage, etc.
     */
    public static function describeMemory(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . 'B';
        }

        if ($bytes < 1_048_576) {
            return round($bytes / 1024, 2) . 'KB';
        }

        return round($bytes / 1_048_576, 2) . 'MB';
    }

    /**
     * Handles application error
     * 
     * @return void
     */
    public function handleError(int $severity, string $message, ?string $file = null, ?int $line = null)
    {
        if (! (error_reporting() & $severity)) {
            return;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * Handles application shutdown
     * 
     * @return void
     */
    public function handleShutdown(){
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        ['type' => $type, 'message' => $message, 'file' => $file, 'line' => $line] = $error;

        if (in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
            $this->handleException(new ErrorException($message, 0, $type, $file, $line));
        }
    }
}