<?php

use app\configs\Email as AppConfigsEmail;
use wizarphics\wizarframework\Application;
use wizarphics\wizarframework\configs\Email as ConfigsEmail;
use wizarphics\wizarframework\Csrf;
use wizarphics\wizarframework\email\Email;
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
    function session(?string $val = null)
    {
        $session = Application::$app->session;

        // Returning a single item?
        if (is_string($val)) {
            return $session->get($val);
        }

        return $session;
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
    function log_message(string $key, string $message)
    {
        $dbg = new Debug();
        $dbf = new Functions();
        $dbf->writeLog(json_encode([$key => $message]));
        if ($key == 'error') {
            error_log($message);
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
