<?php

namespace wizarphics\wizarframework;

use wizarphics\wizarframework\http\Request;
use wizarphics\wizarframework\http\Response;
use wizarphics\wizarframework\interfaces\GeneratorInterface;
use wizarphics\wizarframework\traits\GeneratorTrait;


abstract class Generator extends Controller implements GeneratorInterface
{
    use GeneratorTrait;

    protected string $templateName = '';

    protected string $datef = 'm/d/y, H:i A';

    /**
     * @var array
     */
    protected array $defaultOptions = [];

    /**
     * @var string
     */
    protected string $baseDir = '';

    /**
     * 
     * @inheritDoc
     * 
     */
    public function __get($key): mixed
    {
        return $this->{$key};
    }

    abstract protected function setTemplateName(): self;


    /**
     * Method for creating a file
     *
     * @param Request $request
     * @param Response $response
     * @param null|string $name
     * @return bool Created at: 12/6/2022, 7:52:01 PM (Africa/Lagos)
     */
    public function create(Request $request, Response $response, ?string $name = null): bool
    {
        if (!$name) {
            $b = readline(ucwords($this->baseDir . ' Class Name:'));
        } elseif ($name == 'help') {
            exit();
        } else {
            $b = $name;
        }

        $filname = $this->getFileName($b);
        // d($filname);
        $className = $this->getClassName($filname);
        $namesPACE = $this->getNameSpace($filname);
        $validDir = $this->isValidDIr($filname);
        $toWriteFile = new File($this->getPath($filname));
        if ($toWriteFile->isWritable()) {
            // $toWriteFile = fopen($this->getPath($filname), 'w+');
            $temp = $this->getTemp($opts = [
                '{className}' => $className,
                '{nameSpace}' => $namesPACE
            ]);

            // dd(get_defined_vars());

            $write = $toWriteFile->openFile('w');

            if ($validDir && $write->valid()) {
                if ($write->fwrite($temp) !== false) {
                    $write->eof();
                    $this->log("File created: " . $this->getPath($filname));
                    return true;
                };
            }
        }

        $this->log("Failed to create file. ");
        return false;


        // dd(get_defined_vars());
    }

    public function getFileName(string $name)
    {
        return $name . '.php';
    }

    /**
     * 
     * @return array
     */
    public function getDefaultOptions(): array
    {
        return $this->defaultOptions;
    }

    /**
     * 
     * @param array $defaultOptions 
     * @return self
     */
    public function setDefaultOptions(array $defaultOptions): self
    {
        $this->defaultOptions = $defaultOptions;
        return $this;
    }

    /**
     */
    public function __construct()
    {
        $this->setDefaultOptions([
            '<@php' => '<?php',
            '@>' => '?>',
            '{User}' => get_current_user() ?? php_uname(),
            '{project}' => env('app.name', 'ProjectName') ?? 'Project',
            '{file.created}' => Date($this->datef, time()),
            '{file.createdTime}' => Date('H:i:s', time()),
            '{file.modified_At}' => Date($this->datef, time()),
            '{copy}' => date('Y', time())
        ]);
        $this->setTemplateName();
        $this->setBaseDir();
        $this->setDefaultNameSapce();
    }

    /**
     * 
     * @return string
     */
    public function getBaseDir(): string
    {
        return $this->baseDir;
    }

    /**
     * 
     * @param string $baseDir 
     * @return self
     */
    abstract public function setBaseDir(): self;

    /**
     * @param string $defaultNameSapce 
     * @return self
     */
    abstract public function setDefaultNameSapce(): self;
}
