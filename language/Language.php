<?php

namespace wizarphics\wizarframework\language;

use MessageFormatter;

class Language
{
    /**
     * Holds the retrived language lines
     * from files for faster retrieval
     * on second use.
     * 
     * @var Array
     */

    private $languages = [];

    /**
     * The current locale the language class would work with.
     * 
     * @var string
     */
    private $locale;

    /**
     * True if the intl lib is loaded
     * 
     * @var bool
     */
    private $IntlSupport = false;

    /**
     * Holds the language files that
     * have been loaded to avoid loading more than once
     * 
     * @var array
     */
    private $retrievedFiles = [];

    /**
     * Class constructor.
     */
    public function __construct(string $locale)
    {
        $this->locale = $locale;

        if (class_exists(MessageFormatter::class)) {
            $this->IntlSupport = true;
        }
    }


    /**
     * The current locale the language class would work with.
     * 
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Sets the current locale the language class would work with.
     * 
     * @param string|null $locale the locale should use.
     * @return self
     */
    public function setLocale(?string $locale = null): self
    {
        if ($locale !== null) {
            $this->locale = $locale;
        }

        return $this;
    }

    public function getFormattedLine(string $line, array $args = [])
    {
        // Parse the $line if no file is specified
        if (strpos($line, '.') === false) {
            return $this->formatMessage($line, $args);
        }

        // Get the filename and the actual line alias.
        [$file, $parsedLine] = $this->parseLine($line, $this->locale);

        $output = $this->translateOutput($this->locale, $file, $parsedLine);

        if ($output === null && strpos($this->locale, '_')) {
            [$locale] = explode('-', $this->locale, 2);

            [$file, $parsedLine] = $this->parseLine($line, $locale);

            $output = $this->translateOutput($this->locale, $file, $parsedLine);
        }

        // If the output still remains null then we default to English(en)
        if ($output === null) {
            [$file, $parsedLine] = $this->parseLine($line, 'en');
            $output = $this->translateOutput('en', $file, $parsedLine);
        }

        $output ??= $line;

        return $this->formatMessage($output, $args);
    }


    /**
     * Get the line to be translated.
     *
     * @param string $locale
     * @param string $file
     * @param string $parsedLine
     * 
     * @return array|string|null
     * 
     * Created at: 12/28/2022, 11:57:57 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    private function translateOutput(string $locale, string $file, string $parsedLine): array|string|null
    {
        $output = $this->languages[$locale][$file][$parsedLine] ?? null;
        if ($output !== null) {
            return $output;
        }

        $parsedLineArr = explode('.', $parsedLine);

        foreach ($parsedLineArr as $parsedLinerRow) {
            $current = isset($current) ? $current : $this->languages[$locale][$file] ?? null;

            $output = $current[$parsedLinerRow] ?? null;
            if (is_array($output)) {
                $current = $output;
            }
        }

        if ($output !== null) {
            return $output;
        }

        $row = current($parsedLineArr);
        $key = substr($parsedLine, strlen($row) + 1);

        return $this->languages[$locale][$file][$key] ?? null;
    }

    protected function parseLine(string $line, string $locale): array
    {
        $file = substr($line, 0, strpos($line, '.'));
        $line = substr($line, strlen($file) + 1);

        if (!isset($this->languages[$locale][$file]) || !array_key_exists($line, $this->languages[$locale][$file])) {
            $this->loadLang($file, $locale);
        }

        return [$file, $line];
    }

    protected function formatMessage(array|string $message, array $args = [])
    {
        if (!$this->IntlSupport || $args === []) {
            return $message;
        }

        if (is_array($message)) {
            array_walk($message, fn ($value, $key, $args) => $message[$key] = $this->formatMessage($value, $args), $args);

            return $message;
        }

        return MessageFormatter::formatMessage($this->locale, $message, $args);
    }

    /**
     * Loads a language file in the current locale. If $return is true,
     * will return the file's contents, otherwise will merge with
     * the existing language lines.
     *
     * @return array|void
     */
    protected function loadLang(string $file, string $locale, bool $return = false)
    {
        if (!array_key_exists($locale, $this->retrievedFiles)) {
            $this->retrievedFiles[$locale] = [];
        }

        if (in_array($file, $this->retrievedFiles[$locale], true)) {
            // Don't load the same file twice.
            return [];
        }

        if (!array_key_exists($locale, $this->languages)) {
            $this->languages[$locale] = [];
        }

        if (!array_key_exists($file, $this->languages[$locale])) {
            $this->languages[$locale][$file] = [];
        }

        $path = "language/{$locale}/{$file}.php";
        $lang = $this->requireLang($path);

        if ($return) {
            return $lang;
        }

        $this->retrievedFiles[$locale][] = $file;

        // Merge our string
        $this->languages[$locale][$file] = $lang;
    }

    protected function requireLang(string $path): array
    {
        $files = [
            ROOT_DIR . DIRECTORY_SEPARATOR . $path,
            CORE_DIR . DIRECTORY_SEPARATOR . $path,
        ];
        $strings = [];
        array_walk($files, function ($file) use (&$strings) {
            if (is_file($file)) {
                $strings[] = require $file;
            }
        });

        if (isset($strings[1])) {
            $strings = array_replace_recursive(...$strings);
        } elseif (isset($strings[0])) {
            $strings = $strings[0];
        }

        return $strings;
    }
}
