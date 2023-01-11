<?php

namespace wizarphics\wizarframework\generators;

use wizarphics\wizarframework\Generator;

class Migration extends Generator
{

    protected bool $hasPrefix=true;
    protected string $fileTemp;

    protected function getPrefix(): string
    {
        $existingFiles = scandir(MIGRATION_PATH);
        $lastMigrationFile = end($existingFiles);
        $lastMigrationFilePrefix = strstr($lastMigrationFile, '_', true);
        $index = substr($lastMigrationFilePrefix, 1);
        $newIndex = str_pad((string)$index + 1, 4, "0", STR_PAD_LEFT);
        return 'm' . $newIndex . '_';
    }

    /**
     * @return self
     */
    protected function setTemplateName(): self
    {
        $this->templateName = 'migration';
        return $this;
    }
    /**
     * @return Generator
     */
    public function setDefaultNameSapce(): Generator
    {
        $this->defaultNameSapce = (env('app.defaultNamespace') ?? 'app\\') . 'migrations';
        return $this;
    }

    public function setBaseDir(): Generator
    {
        $this->baseDir = 'migrations';
        return $this;
    }
}
