<?php

use app\configs\Email as AppConfigsEmail;
use wizarphics\wizarframework\Application;
use wizarphics\wizarframework\auth\Authentication;
use wizarphics\wizarframework\configs\Email as ConfigsEmail;
use wizarphics\wizarframework\Csrf;
use wizarphics\wizarframework\email\Email;
use wizarphics\wizarframework\helpers\Escaper;
use wizarphics\wizarframework\helpers\form\Form;
use wizarphics\wizarframework\helpers\form\HiddenField;
use wizarphics\wizarframework\Model;
use wizarphics\wizarframework\Session;
use wizarphics\wizarframework\utilities\debugger\Debug;
use wizarphics\wizarframework\utilities\debugger\Functions;


/*
 * ---------------------------------------------------------------
 * ---------------------------------------------------------------
 * Create a more easier way of accessing the functions
 * ---------------------------------------------------------------
 * ---------------------------------------------------------------
 */

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        // Not found? Return the default value
        if ($value === false) {
            return $default;
        }

        // Handle any boolean values
        switch (strtolower($value)) {
            case 'true':
                return true;

            case 'false':
                return false;

            case 'empty':
                return '';

            case 'null':
                return null;
        }

        return $value;
    }
}

if (!function_exists('is_index')) {
    function is_index(array $array): bool
    {
        return  array_is_list($array);
    }
}



if (!function_exists('auth')) {
    function auth()
    {
        return new Authentication();
    }
}

/**
 * Determines if an array is associative.
 * @param  array  $array
 * @return bool
 */
function is_assoc(array $array)
{
    $keys = array_keys($array);

    return array_keys($keys) !== $keys;
}

if (!function_exists('session')) {
    /**
     * A convenience method for accessing the session instance,
     * or an item that has been set in the session.
     *
     * Examples:
     *    session()->set('foo', 'bar');
     *    $foo = session('bar');
     *
     * @param string $val
     *
     * @return mixed|Session|null
     */
    function session(?string $key = null)
    {
        $session = Application::$app->session;
        // Returning a single item?
        if (is_string($key)) {
            return $session->get($key);
        }

        return $session;
    }
}

if (!function_exists('getFlashMessage')) {
    function getFlashMessage($key)
    {
        $flash = session()->getFlash($key);
        $message = $flash['message'];
        return is_string($message) ? _($message) : $message;
    }
}

if (!function_exists('getFlashTime')) {
    function getFlashTime(array $flash, bool $asAgo = true)
    {
        return $asAgo ? time_ago($flash['time_set']) : $flash['time_set'];
    }
}

if (!function_exists('flash')) {
    function flash($key)
    {
        return (object)(session()->getFlash($key));
    }
}

if (!function_exists('csrfToken')) {
    function csrfToken()
    {
        $csrf = new Csrf(Application::$app->request);
        $token = $csrf->getToken();
        return $token;
    }
}


if (!function_exists('function_usable')) {
    /**
     * Function usable
     *
     * Executes a function_exists() check, and if the Suhosin PHP
     * extension is loaded - checks whether the function that is
     * checked might be disabled in there as well.
     *
     * This is useful as function_exists() will return FALSE for
     * functions disabled via the *disable_functions* php.ini
     * setting, but not for *suhosin.executor.func.blacklist* and
     * *suhosin.executor.disable_eval*. These settings will just
     * terminate script execution if a disabled function is executed.
     *
     * The above described behavior turned out to be a bug in Suhosin,
     * but even though a fix was committed for 0.9.34 on 2012-02-12,
     * that version is yet to be released. This function will therefore
     * be just temporary, but would probably be kept for a few years.
     *
     * @see   http://www.hardened-php.net/suhosin/
     *
     * @param string $functionName Function to check for
     *
     * @return bool TRUE if the function exists and is safe to call,
     *              FALSE otherwise.
     *
     * @codeCoverageIgnore This is too exotic
     */
    function function_usable(string $functionName): bool
    {
        static $_suhosin_func_blacklist;

        if (function_exists($functionName)) {
            if (!isset($_suhosin_func_blacklist)) {
                $_suhosin_func_blacklist = extension_loaded('suhosin') ? explode(',', trim(ini_get('suhosin.executor.func.blacklist'))) : [];
            }

            return !in_array($functionName, $_suhosin_func_blacklist, true);
        }

        return false;
    }
}

if (!function_exists('log_message')) {
    function log_message(string $key, string|array $message)
    {
        $dbg = new Debug();
        $dbf = new Functions();
        $dbf->writeLog(json_encode([$key => $message]));
        if ($key == 'error') {
            error_log(json_encode($message));
        }
    }
}

if (!function_exists('array_equality')) {
    function array_equality(array $array, array ...$arrays): bool
    {
        $diff = array_diff($array, ...$arrays);
        return count($diff) == 0;
    }
}

if (!function_exists('emailer')) {
    function emailer(array|null|ConfigsEmail $config = null)
    {
        $emailer = new Email($config);
        return $emailer;
    }
}

if (!function_exists('is_cli')) {
    /**
     * Check if PHP was invoked from the command line.
     *
     */
    function is_cli(): bool
    {
        if (in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
            return true;
        }

        return !isset($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['REQUEST_METHOD']);
    }
}


if (!function_exists('send_email')) {
    function send_email(string $to, string $subject, string $message, array|string|null $from = null, $debug = false, array|null|ConfigsEmail $config = null)
    {
        $config = $config ?? new AppConfigsEmail ?? new ConfigsEmail;
        $emailer = emailer($config);
        if ($from != null) {
            if (is_array($from)) {
                $fromEmail = $from['email'];
                $fromName = $from['name'] ?? '';
                $returnPath = $from['return'] ?? null;
                $emailer->setFrom($fromEmail, $fromName, $returnPath);
            } else {
                $emailer->setFrom($from);
            }
        }
        $emailer->setTo($to)
            ->setSubject($subject)
            ->setMessage($message);
        if ($emailer->send()) {
            return true;
        } else {
            if ($debug) {
                echo ($emailer->printDebugger());
            }
        }
    }
}

if (!function_exists('redirect')) {
    function redirect(string $to)
    {
        Application::$app->response->redirect($to);
    }
}

if (!function_exists('csrfField')) {
    function csrfField(): string
    {
        $token = csrfToken();
        $name = Csrf::tokenFieldName;

        $field = hiddenField($name, $token, ['autocomplete' => "off", "inputmode" => "none"]);
        return $field;
    }
}

/*
 * ---------------------------------------------------------------
 * FORM HELPERS
 * ---------------------------------------------------------------
 */

/**
 * Create a form opening tag
 */
if (!function_exists('form_begin')) {
    function form_begin($action, $method, $fieldAttributes = [])
    {
        $form = new Form;
        return $form::begin($action, $method, $fieldAttributes);
    }
}

if (!function_exists('form_begin_multipart')) {
    function form_begin_multipart($action, $method, $fieldAttributes = [])
    {
        $form = new Form;
        return $form::begin($action, $method, array_merge($fieldAttributes, ['enctype' => 'multipart/form-data']));
    }
}

if (!function_exists('form_close')) {
    function form_close()
    {
        $form = new Form;
        return $form::end();
    }
}


if (!function_exists('textField')) {
    function textField(Model $model, string $attribute, array $addtionalField = [])
    {
        $form = new Form;
        return $form->field($model, $attribute, addtionalField: $addtionalField);
    }
}

if (!function_exists('hiddenField')) {
    function hiddenField(string $attribute, ?string $value = '', ?array $fieldAttributes = [])
    {
        return new HiddenField($attribute, $value, $fieldAttributes);
    }
}

if (!function_exists('emailField')) {
    function emailField(Model $model, string $attribute, array $addtionalField = [])
    {
        $form = new Form;
        return $form->field($model, $attribute, addtionalField: $addtionalField)->emailField();
    }
}


if (!function_exists('passwordField')) {
    function passwordField(Model $model, string $attribute, array $addtionalField = [])
    {
        $form = new Form;
        return $form->field($model, $attribute, addtionalField: $addtionalField)->passwordField();
    }
}

if (!function_exists('fileField')) {
    function fileField(Model $model, string $attribute, array $addtionalField = [])
    {
        $form = new Form;
        return $form->field($model, $attribute, addtionalField: $addtionalField)->fileField();
    }
}

if (!function_exists('colorField')) {
    function colorField(Model $model, string $attribute, array $addtionalField = [])
    {
        $form = new Form;
        return $form->field($model, $attribute, addtionalField: $addtionalField)->colorField();
    }
}

if (!function_exists('dateTimeField')) {
    function dateTimeField(Model $model, string $attribute, array $addtionalField = [])
    {
        $form = new Form;
        return $form->field($model, $attribute, addtionalField: $addtionalField)->dateTime();
    }
}

if (!function_exists('dateField')) {
    function dateField(Model $model, string $attribute, array $addtionalField = [])
    {
        $form = new Form;
        return $form->field($model, $attribute, addtionalField: $addtionalField)->date();
    }
}

if (!function_exists('timeField')) {
    function timeField(Model $model, string $attribute, array $addtionalField = [])
    {
        $form = new Form;
        return $form->field($model, $attribute, addtionalField: $addtionalField)->time();
    }
}

if (!function_exists('searchField')) {
    function searchField(Model $model, string $attribute, array $addtionalField = [])
    {
        $form = new Form;
        return $form->field($model, $attribute, addtionalField: $addtionalField)->search();
    }
}

if (!function_exists('numberField')) {
    function numberField(Model $model, string $attribute, array $addtionalField = [])
    {
        $form = new Form;
        return $form->field($model, $attribute, addtionalField: $addtionalField)->numberField();
    }
}


if (!function_exists('selectField')) {
    function selectField(Model $model, string $attribute, array $options = [],)
    {
        $form = new Form;
        return $form->select($model, $attribute, $options);
    }
}


if (!function_exists('multipleSelectField')) {
    function multipleSelectField(Model $model, string $attribute, array $options = [],)
    {
        $form = new Form;
        return $form->select($model, $attribute, $options)->multiple();
    }
}


if (!function_exists('textAreaField')) {
    function textAreaField(Model $model, string $attribute, array $fieldAttributes = [],)
    {
        $form = new Form;
        return $form->textArea($model, $attribute);
    }
}


if (!function_exists('checkBoxField')) {
    function checkBoxField(Model $model, string $attribute, string $chedkId = '')
    {
        $form = new Form;
        return $form->checkbox($model, $attribute, $chedkId);
    }
}


if (!function_exists('input_submit')) {
    function input_submit(Model $model, string $attribute, array $fieldAttributes = [])
    {
        $form = new Form;
        return $form->input_submit($model, $attribute, $fieldAttributes);
    }
}



if (!function_exists('input_button')) {
    function input_button(Model $model, string $attribute, array $fieldAttributes = [])
    {
        $form = new Form;
        return $form->input_button($model, $attribute, $fieldAttributes);
    }
}


if (!function_exists('buttonField')) {
    function buttonField(Model $model, string $attribute, array $fieldAttributes = [])
    {
        $form = new Form;
        return $form->button($model, $attribute, $fieldAttributes);
    }
}

if (!function_exists('submit_button')) {
    function submit_button(Model $model, string $attribute, array $fieldAttributes = [])
    {
        $form = new Form;
        return $form->submit_btn($model, $attribute, $fieldAttributes);
    }
}

if (!function_exists('time_ago')) {
    function time_ago(string $date)
    {
        $seconds  = strtotime(date('Y-m-d H:i:s')) - strtotime($date);

        $years = floor($seconds / YEAR);
        $months = floor($seconds / MONTH);
        $day = floor($seconds / DAY);
        $hours = floor($seconds / HOUR);
        $mins = floor(($seconds - ($hours * HOUR)) / MINUTE);
        $secs = floor($seconds % MINUTE);

        if ($seconds < SECOND)
            $time = 'Just Now';
        else if ($seconds < MINUTE)
            $time = $secs . " " . $secs <= 1 ? "second" : "seconds" . " ago";
        else if ($seconds < HOUR)
            $time = $mins . " " . $mins <= 1 ? "min" : "mins" . " ago";
        else if ($seconds < DAY)
            $time = $hours . " " . $hours <= 1 ? "hour" : "hours" . " ago";
        else if ($seconds < MONTH)
            $time = $day . " " . $day <= 1 ? "day" : "days" . " ago";
        else if ($seconds < YEAR)
            $time =  $months . " " . $months <= 1 ? "month" : "months" . " ago";
        else
            $time = $years . " " . $years < 1 ? "year" : "years" . " ago";
        return $time;
    }
}

if (!function_exists('esc')) {
    /**
     * Performs simple auto-escaping of data for security reasons.
     * Might consider making this more complex at a later date.
     *
     * If $data is a string, then it simply escapes and returns it.
     * If $data is an array, then it loops over it, escaping each
     * 'value' of the key/value pairs.
     *
     * @param array|string $data
     * @phpstan-param 'html'|'js'|'css'|'url'|'attr'|'raw' $context
     * @param string|null $encoding Current encoding for escaping.
     *                              If not UTF-8, we convert strings from this encoding
     *                              pre-escaping and back to this encoding post-escaping.
     *
     * @return array|string
     *
     * @throws InvalidArgumentException
     */
    function esc($data, string $context = 'html', ?string $encoding = null)
    {
        if (is_array($data)) {
            foreach ($data as &$value) {
                $value = esc($value, $context);
            }
        }

        if (is_string($data)) {
            $context = strtolower($context);

            // Provide a way to NOT escape data since
            // this could be called automatically by
            // the View library.
            if ($context === 'raw') {
                return $data;
            }

            if (!in_array($context, ['html', 'js', 'css', 'url', 'attr'], true)) {
                throw new InvalidArgumentException('Invalid escape context provided.');
            }

            $method = $context === 'attr' ? 'escapeHtmlAttr' : 'escape' . ucfirst($context);

            static $escaper;
            if (!$escaper) {
                $escaper = new Escaper($encoding);
            }

            if ($encoding && $escaper->getEncoding() !== $encoding) {
                $escaper = new Escaper($encoding);
            }

            $data = $escaper->{$method}($data);
        }

        return $data;
    }
}


// CodeIgniter Array Helpers

if (!function_exists('dot_array_search')) {
    /**
     * Searches an array through dot syntax. Supports
     * wildcard searches, like foo.*.bar
     *
     * @return array|bool|int|object|string|null
     */
    function dot_array_search(string $index, array $array)
    {
        // See https://regex101.com/r/44Ipql/1
        $segments = preg_split(
            '/(?<!\\\\)\./',
            rtrim($index, '* '),
            0,
            PREG_SPLIT_NO_EMPTY
        );

        $segments = array_map(static fn ($key) => str_replace('\.', '.', $key), $segments);

        return _array_search_dot($segments, $array);
    }
}

if (!function_exists('_array_search_dot')) {
    /**
     * Used by `dot_array_search` to recursively search the
     * array with wildcards.
     *
     * @internal This should not be used on its own.
     *
     * @return mixed
     */
    function _array_search_dot(array $indexes, array $array)
    {
        // If index is empty, returns null.
        if ($indexes === []) {
            return null;
        }

        // Grab the current index
        $currentIndex = array_shift($indexes);

        if (!isset($array[$currentIndex]) && $currentIndex !== '*') {
            return null;
        }

        // Handle Wildcard (*)
        if ($currentIndex === '*') {
            $answer = [];

            foreach ($array as $value) {
                if (!is_array($value)) {
                    return null;
                }

                $answer[] = _array_search_dot($indexes, $value);
            }

            $answer = array_filter($answer, static fn ($value) => $value !== null);

            if ($answer !== []) {
                if (count($answer) === 1) {
                    // If array only has one element, we return that element for BC.
                    return current($answer);
                }

                return $answer;
            }

            return null;
        }

        // If this is the last index, make sure to return it now,
        // and not try to recurse through things.
        if (empty($indexes)) {
            return $array[$currentIndex];
        }

        // Do we need to recursively search this value?
        if (is_array($array[$currentIndex]) && $array[$currentIndex] !== []) {
            return _array_search_dot($indexes, $array[$currentIndex]);
        }

        // Otherwise, not found.
        return null;
    }
}

if (!function_exists('array_deep_search')) {
    /**
     * Returns the value of an element at a key in an array of uncertain depth.
     *
     * @param mixed $key
     *
     * @return mixed|null
     */
    function array_deep_search($key, array $array)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach ($array as $value) {
            if (is_array($value) && ($result = array_deep_search($key, $value))) {
                return $result;
            }
        }

        return null;
    }
}

if (!function_exists('array_sort_by_multiple_keys')) {
    /**
     * Sorts a multidimensional array by its elements values. The array
     * columns to be used for sorting are passed as an associative
     * array of key names and sorting flags.
     *
     * Both arrays of objects and arrays of array can be sorted.
     *
     * Example:
     *     array_sort_by_multiple_keys($players, [
     *         'team.hierarchy' => SORT_ASC,
     *         'position'       => SORT_ASC,
     *         'name'           => SORT_STRING,
     *     ]);
     *
     * The '.' dot operator in the column name indicates a deeper array or
     * object level. In principle, any number of sublevels could be used,
     * as long as the level and column exist in every array element.
     *
     * For information on multi-level array sorting, refer to Example #3 here:
     * https://www.php.net/manual/de/function.array-multisort.php
     *
     * @param array $array       the reference of the array to be sorted
     * @param array $sortColumns an associative array of columns to sort
     *                           after and their sorting flags
     */
    function array_sort_by_multiple_keys(array &$array, array $sortColumns): bool
    {
        // Check if there really are columns to sort after
        if (empty($sortColumns) || empty($array)) {
            return false;
        }

        // Group sorting indexes and data
        $tempArray = [];

        foreach ($sortColumns as $key => $sortFlag) {
            // Get sorting values
            $carry = $array;

            // The '.' operator separates nested elements
            foreach (explode('.', $key) as $keySegment) {
                // Loop elements if they are objects
                if (is_object(reset($carry))) {
                    // Extract the object attribute
                    foreach ($carry as $index => $object) {
                        $carry[$index] = $object->{$keySegment};
                    }

                    continue;
                }

                // Extract the target column if elements are arrays
                $carry = array_column($carry, $keySegment);
            }

            // Store the collected sorting parameters
            $tempArray[] = $carry;
            $tempArray[] = $sortFlag;
        }

        // Append the array as reference
        $tempArray[] = &$array;

        // Pass sorting arrays and flags as an argument list.
        return array_multisort(...$tempArray);
    }
}

if (!function_exists('array_flatten_with_dots')) {
    /**
     * Flatten a multidimensional array using dots as separators.
     *
     * @param iterable $array The multi-dimensional array
     * @param string   $id    Something to initially prepend to the flattened keys
     *
     * @return array The flattened array
     */
    function array_flatten_with_dots(iterable $array, string $id = ''): array
    {
        $flattened = [];

        foreach ($array as $key => $value) {
            $newKey = $id . $key;

            if (is_array($value) && $value !== []) {
                $flattened = array_merge($flattened, array_flatten_with_dots($value, $newKey . '.'));
            } else {
                $flattened[$newKey] = $value;
            }
        }

        return $flattened;
    }
}


if (!function_exists('route_to')) {
    /**
     * Given a controller/method string and any params,
     * will attempt to build the relative URL to the
     * matching route.
     *
     * NOTE: This requires the controller/method to
     * have a route defined in the routes file.
     *
     * @param string     $method    Named route or Controller::method
     * @param int|string ...$params One or more parameters to be passed to the route
     *
     * @return false|string
     */
    function route_to(string $method, ...$params)
    {
        return Application::$app->router->getRouteTo($method, ...$params);
    }
}

if (!function_exists('__')) {
    /**
     * A convenience method to translate a string or array of them and format
     * the result with the intl extension's MessageFormatter.
     *
     * @return string
     */
    function __(string $line, array $args = [], ?string $locale = null): string
    {
        $language = Application::$app->lang;
        // Get active locale
        $activeLocale = $language->getLocale();

        if ($locale && $locale !== $activeLocale) {
            $language->setLocale($locale);
        }

        $line = $language->getFormattedLine($line, $args);

        if ($locale && $locale !== $activeLocale) {
            // Reset to active locale
            $language->setLocale($activeLocale);
        }

        return $line;
    }
}
