<?php

namespace wizarphics\wizarframework\generators;

use wizarphics\wizarframework\Application;
use wizarphics\wizarframework\Controller;
use wizarphics\wizarframework\File;
use wizarphics\wizarframework\files\FileCollection;
use wizarphics\wizarframework\Request;
use wizarphics\wizarframework\Response;

class Migration extends Controller
{

    protected string $fileTemp;
    public function create(Request $request, Response $response, ?string $name=null)
    {
        if (!$name) {
            readline_add_history($b = readline('Migration Class Name:'));
        } else {
            $b = $name;
        }
        $b = trim($b);
        if ($b && $b != null) {
            $prefix = $this->getPrefix();
            $className = $prefix . str_replace([' ', '-'], '', ucwords(strtolower($b)));
            $fileName = $className.'.php';

            $fileTemp = $this->getTemp();
            $temp = str_replace(
                [
                    '<@php',
                    // '{namespace}',
                    '{className}',
                ],[
                    '<?php',
                    // $nameSpace,
                    $className,
                ],$fileTemp
            );

            $newMigrationFile = fopen(MIGRATION_PATH.$fileName, 'a');
            if ($newMigrationFile == false){
                $this->log("Failed to create file.");
                return;
            }
            fputs($newMigrationFile, $temp);
            fclose($newMigrationFile);

            // if (preg_match('/create_table/i', $b)) {
            //     $this->fileTemp = 'create_tableMigrationTemp';
            // } elseif (preg_match('/add_column/i', $b)) {
            //     $this->fileTemp = 'add_columnMigrationTemp';
            // } elseif (preg_match('/update_column/i',  $b)||preg_match('/modify/i', $b)) {
            //     $this->fileTemp = 'modifyMigrationTemp';
            // } elseif (preg_match('/drop_table/i',  $b)) {
            //     $this->fileTemp = 'drop_tableMigrationTemp';
            // } elseif (preg_match('/rename_table/i',  $b)) {
            //     $this->fileTemp = 'renameTableMigrationTemp';
            // }elseif (preg_match('/seed_table/i', $b)) {
            //     $this->fileTemp = 'seedTableMigrationTemp';
            // }
            // else {
            //     $this->fileTemp = 'generalMigrationTemp';
            // };
            // echo 'File with name ' . $fileName . ' has been Created Successfully';

            $this->log("File created: " . MIGRATION_PATH . $fileName);
            return true;
        }
    }

    protected function getPrefix(): string
    {
        $existingFiles = scandir(MIGRATION_PATH);
        $lastMigrationFile = end($existingFiles);
        $lastMigrationFilePrefix = strstr($lastMigrationFile, '_', true);
        $index = substr($lastMigrationFilePrefix, 1);
        $newIndex = str_pad((string)$index + 1, 4, "0", STR_PAD_LEFT);
        return 'm' . $newIndex . '_';
    }

    protected function getTemp(): string
    {
        $tempLate = Application::$app->view->renderCustomView(__DIR__ . '/templates/migration.tpl');
        return $tempLate ?? '';
    }

    protected function log($message)
    {
        echo '[' . date('Y-m-d H:i:s') . '] - ' . $message . PHP_EOL;
    }
}
