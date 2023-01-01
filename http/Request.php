<?php
/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: $file.created
 * 6/30/22, 7:02 PM
 * Last Modified at: 6/30/22, 7:02 PM
 * Time: 7:2
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace wizarphics\wizarframework\http;

use Locale;
use wizarphics\wizarframework\files\FileCollection;
use wizarphics\wizarframework\files\FileUploaded;
use wizarphics\wizarframework\http\Cookie;
use wizarphics\wizarframework\interfaces\MessageInterface;
use wizarphics\wizarframework\interfaces\RequestInterface;
use wizarphics\wizarframework\traits\RequestTrait;

class Request extends Message implements MessageInterface, RequestInterface
{
    use RequestTrait;

    /**
     * @var array
     */
    private array $routeArgs;
    /**
     * File collection
     *
     * @var FileCollection|null
     */
    protected $files;

    /**
     * The default Locale this request
     * should operate under.
     *
     * @var string
     */
    protected $defaultLocale;

    /**
     * The current application location
     * @var string
     */
    protected $locale;


    /**
     * Stores the valid locale codes.
     *
     * @var array
     */
    protected $validLocales = [];

    /**
     * Class constructor.
     */
    public function __construct($body = 'php://input')
    {
        // Get our body from php://input
        if ($body == 'php://input') {
            $body = file_get_contents('php://input');
        }

        $this->body         = !empty($body) ? $body : null;
        $this->validLocales = explode(', ', env('SUPPORTED_LOCALES'));
        $this->populateHeaders();
        // $this->routeArgs = [];
        // $this->files = new FileCollection();
        $this->detectLocale();
    }

    /**
     * [Description for getPath]
     *
     * @return string|false
     * 
     * Created at: 11/24/2022, 2:25:48 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getPath(): string|false
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        if ($this->Method() == 'cli') {
            $path = $_SERVER['argv'][1] ?? '/';
        }
        $position = strpos($path, '?');
        if ($position === false) {

            return $path;
        }
        return substr($path, 0, $position);
    }

    /**
     * Test to see if a request contains the HTTP_X_REQUESTED_WITH header.
     */
    public function isAJAX(): bool
    {
        return $this->hasHeader('X-Requested-With') && strtolower($this->header('X-Requested-With')->getValue()) === 'xmlhttprequest';
    }

    /**
     * Attempts to detect if the current connection is secure through
     * a few different methods.
     */
    public function isSecure(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if ($this->hasHeader('X-Forwarded-Proto') && $this->header('X-Forwarded-Proto')->getValue() === 'https') {
            return true;
        }

        return $this->hasHeader('Front-End-Https') && !empty($this->header('Front-End-Https')->getValue()) && strtolower($this->header('Front-End-Https')->getValue()) !== 'off';
    }


    /**
     * [Description for Method]
     *
     * @return string
     * 
     * Created at: 11/24/2022, 2:26:38 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function Method(): string
    {
        if (is_cli()) {
            return 'cli';
        }
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Fetch an item from JSON input stream with fallback to $_REQUEST object. This is the simplest way
     * to grab data from the request object and can be used in lieu of the
     * other get* methods in most cases.
     *
     * @param array|string|null $index
     * @param int|null          $filter Filter constant
     * @param mixed             $flags
     *
     * @return mixed
     */
    public function getVar($index = null, $filter = null, $flags = null)
    {
        if (strpos($this->getHeaderLine('Content-Type'), 'application/json') !== false && $this->body !== null) {
            if ($index === null) {
                return $this->getJSON();
            }

            if (is_array($index)) {
                $output = [];

                foreach ($index as $key) {
                    $output[$key] = $this->getJsonVar($key, false, $filter, $flags);
                }

                return $output;
            }

            return $this->getJsonVar($index, false, $filter, $flags);
        }

        return $this->fetchGlobal('request', $index, $filter, $flags);
    }

    /**
     * A convenience method that grabs the raw input stream and decodes
     * the JSON into an array.
     *
     * If $assoc == true, then all objects in the response will be converted
     * to associative arrays.
     *
     * @param bool $assoc   Whether to return objects as associative arrays
     * @param int  $depth   How many levels deep to decode
     * @param int  $options Bitmask of options
     *
     * @see http://php.net/manual/en/function.json-decode.php
     *
     * @return mixed
     */
    public function getJSON(bool $assoc = false, int $depth = 512, int $options = 0)
    {
        return json_decode($this->body ?? '', $assoc, $depth, $options);
    }

    /**
     * Get a specific variable from a JSON input stream
     *
     * @param string         $index  The variable that you want which can use dot syntax for getting specific values.
     * @param bool           $assoc  If true, return the result as an associative array.
     * @param int|null       $filter Filter Constant
     * @param array|int|null $flags  Option
     *
     * @return mixed
     */
    public function getJsonVar(string $index, bool $assoc = false, ?int $filter = null, $flags = null)
    {
        $json = $this->getJSON(true);
        if (!is_array($json)) {
            return null;
        }
        $data = dot_array_search($index, $json);

        if ($data === null) {
            return null;
        }

        if (!is_array($data)) {
            $filter ??= FILTER_DEFAULT;
            $flags = is_array($flags) ? $flags : (is_numeric($flags) ? (int) $flags : 0);

            return filter_var($data, $filter, $flags);
        }

        if (!$assoc) {
            return json_decode(json_encode($data));
        }

        return $data;
    }

    /**
     * A convenience method that grabs the raw input stream(send method in PUT, PATCH, DELETE) and decodes
     * the String into an array.
     *
     * @return mixed
     */
    public function RawInput()
    {
        parse_str($this->body ?? '', $output);

        return $output;
    }

    /**
     * [Description for getBody]
     *
     * @return array
     * 
     * Created at: 11/24/2022, 2:27:34 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getBody(): array
    {
        return $this->getMethodBody($this->Method());
    }

    /**
     * Fetch an item from GET data.
     *
     * @param array|string|null $index  Index for item to fetch from $_GET.
     * @param int|null          $filter A filter name to apply.
     * @param mixed|null        $flags
     *
     * @return mixed
     */
    public function getData($index = null, $filter = null, $flags = null)
    {
        return $this->fetchGlobal('get', $index, $filter, $flags);
    }

    /**
     * Fetch an item from POST.
     *
     * @param array|string|null $index  Index for item to fetch from $_POST.
     * @param int|null          $filter A filter name to apply
     * @param mixed             $flags
     *
     * @return mixed
     */
    public function postData($index = null, $filter = null, $flags = null)
    {
        return $this->fetchGlobal('post', $index, $filter, $flags);
    }

    /**
     * Fetch an item from POST data with fallback to GET.
     *
     * @param array|string|null $index  Index for item to fetch from $_POST or $_GET
     * @param int|null          $filter A filter name to apply
     * @param mixed             $flags
     *
     * @return mixed
     */
    public function data($index = null, $filter = null, $flags = null)
    {
        if ($index === null) {
            return array_merge($this->getData($index, $filter, $flags), $this->postData($index, $filter, $flags));
        }
        // Use $_POST directly here, since filter_has_var only
        // checks the initial POST data, not anything that might
        // have been added since.
        return isset($_POST[$index]) ? $this->postData($index, $filter, $flags) : (isset($_GET[$index]) ? $this->getData($index, $filter, $flags) : $this->postData($index, $filter, $flags));
    }

    /**
     * Fetch an item from the COOKIE array.
     *
     * @param array|string|null $index  Index for item to be fetched from $_COOKIE
     * @param int|null          $filter A filter name to be applied
     * @param mixed             $flags
     *
     * @return mixed
     */
    public function cookieData($index = null, $filter = null, $flags = null)
    {
        return $this->fetchGlobal('cookie', $index, $filter, $flags);
    }

    public function getMethodBody($method = 'get')
    {
        $body = [];
        if ($method === 'get') {
            foreach ($_GET as $key => $value) {
                $body[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        if ($method === 'post') {
            foreach ($_POST as $key => $value) {
                if (is_array($value)) {
                    $body[$key] = [];
                    foreach ($value as $vKey => $Kvalue) {
                        $body[$key][$vKey] = filter_var($Kvalue, FILTER_SANITIZE_SPECIAL_CHARS);
                    }
                } else {
                    $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
                }
            }
        }

        return $body;
    }

    /**
     * [Description for getFiles]
     *
     * @return array|null
     * 
     * Created at: 11/24/2022, 2:28:04 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getFiles(): array|null
    {
        if ($this->files === null) {
            $this->files = new FileCollection();
        }

        return $this->files->all(); //return all uploaded files
    }


    /**
     * Verify if a file exist, by the name of the input field used to upload it, in the collection
     * of uploaded files and if is have been uploaded with multiple option.
     *
     * @param string $fileID
     * 
     * @return array|null
     * 
     * Created at: 11/24/2022, 2:29:18 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getFileMultiple(string $fileID): array|null
    {
        if ($this->files === null) {
            $this->files = new FileCollection();
        }

        return $this->files->getFileMultiple($fileID);
    }

    /**
     * Retrieves a single file by the name of the input field used
     * to upload it.
     *
     * @param string $fileID
     * 
     * @return FileUploaded|null
     * 
     * Created at: 11/24/2022, 2:28:40 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getFile(string $fileID): FileUploaded|null
    {
        if ($this->files === null) {
            $this->files = new FileCollection();
        }

        return $this->files->getFile($fileID);
    }

    /**
     * [Description for isGet]
     *
     * @return bool
     * 
     * Created at: 11/24/2022, 2:30:00 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function isGet(): bool
    {
        return $this->Method() === 'get';
    }

    /**
     * [Description for isPost]
     *
     * @return bool
     * 
     * Created at: 11/24/2022, 2:30:05 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function isPost(): bool
    {
        return $this->Method() === 'post';
    }

    /**
     * [Description for getRouteArgs]
     *
     * @return array
     * 
     * Created at: 11/24/2022, 2:30:19 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function getRouteArgs(): array
    {
        return $this->routeArgs;
    }

    /**
     * [Description for setRouteArgs]
     *
     * @param array $routeArgs
     * 
     * @return self
     * 
     * Created at: 11/24/2022, 2:30:28 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    public function setRouteArgs(array $routeArgs): self
    {
        $this->routeArgs = $routeArgs;
        return $this;
    }

    /**
     * Handles setting up the locale, perhaps auto-detecting through
     * content negotiation.
     *
     * @param array|object $config
     */
    public function detectLocale()
    {
        $this->locale = $this->defaultLocale = env('app.locale');
        if ($this->locale == null) {
            $this->locale = $this->defaultLocale = env('defaultLocale');
        }
        $this->setLocale(in_array($this->locale, $this->validLocales) ? $this->locale : $this->defaultLocale);
    }

    /**
     * Sets the locale string for this request.
     *
     * @return Request
     */
    public function setLocale(string $locale)
    {
        // If it's not a valid locale, set it
        // to the default locale for the site.
        if (!in_array($locale, $this->validLocales, true)) {
            $locale = $this->defaultLocale;
        }

        $this->locale = $locale;
        Locale::setDefault($locale);

        return $this;
    }

    /**
     * Gets the current locale, with a fallback to the default
     * locale if none is set.
     */
    public function getLocale(): string
    {
        return $this->locale ?? $this->defaultLocale;
    }

    /**
     * Returns the default locale as set in Config\App.php
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }
}
