<?php

namespace wizarphics\wizarframework\traits;

use wizarphics\wizarframework\Application;

trait GeneratorTrait
{
    protected bool $hasPrefix = false;
    protected string $defaultNameSapce;
    /**
     * Clean the template
     *
     * @param string $temp
     * @param array $options
     * 
     * @return string
     * 
     * Created at: 12/6/2022, 7:37:05 PM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    protected function getCleanTemp(string $temp, array $options): string
    {
        $opts = array_merge($options, $this->defaultOptions);
        $search = array_keys($opts);
        $replace = array_values($opts);
        return str_replace($search, $replace, $temp);
    }

    protected function log($message)
    {
        echo '[' . date('Y-m-d H:i:s') . '] - ' . $message . PHP_EOL;
    }

    public function getCleanName(string $rawName): string
    {
        return str_replace(['__cli__', '\\'], '/', $rawName);
    }

    public function getClassName(string $name)
    {
        // dd($_SERVER);
        $name = $this->getRealName($name);
        return ucwords(strchr($name, '.php', true));
    }

    public function getRealName(string $name)
    {
        $name = $this->getCleanName($name);
        if ($pos = strpos($name, '/')) {
            $nm = explode('/', $name);
            return $this->hasPrefix ? $this->getPrefix() . end($nm) : end($nm);
        } else {
            // d('Straight');
            // exit();
            return $this->hasPrefix ? $this->getPrefix().$name:$name;
        }
    }
    public function getPath(string $name)
    {
        $defaultPath = Application::$ROOT_DIR . '/' . $this->baseDir . '/';
        // dd(get_defined_vars());
        return $defaultPath . $this->getRealName($name);
    }

    public function isValidDIr($file): bool
    {
        $nm = $this->getCleanName($file);
        if (strpos($nm, '/')) {
            $dir = explode('/', $nm);
            $p = $this->getPath(strchr($nm, end($dir), true));
            if (is_dir($p)) {
                return true;
            } else if (mkdir($p, 0777, true)) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }


    /**
     * @inheritDoc
     */
    public function getTemp(?array $options = []): string
    {
        $tempLate = Application::$app->view->renderCustomView(CORE_DIR . '/generators/templates/' . $this->templateName . '.tpl');
        $tempLate = $this->getCleanTemp($tempLate, $options);
        return $tempLate ?? '';
    }

    /**
     * @todo Needs to be Implemented
     */
    public function getNameSpace($name)
    {
        $nm = $this->getCleanName($name);
        if (stripos($nm, '/')) {
            $nm = str_replace('/', '\\', $nm);
            $c_namespaces = explode('\\', $nm);
            unset($c_namespaces[count($c_namespaces) - 1]);
            $f_namespace = $this->getDefaultNameSapce() . '\\' . join('\\', $c_namespaces);
            return $f_namespace;
        } else {
            return $this->getDefaultNameSapce();
        };
    }

    /**
     * @return string
     */
    public function getDefaultNameSapce(): string
    {
        return $this->defaultNameSapce;
    }
}
