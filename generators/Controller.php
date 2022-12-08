<?php

namespace wizarphics\wizarframework\generators;

use wizarphics\wizarframework\Application;
use wizarphics\wizarframework\File;
use wizarphics\wizarframework\Generator;

class Controller extends Generator
{



    /**
     * @return Generator
     */
    protected function setTemplateName(): Generator
    {
        $this->templateName = 'controller';
        return $this;
    }
    
    /**
     * 
     */
    public function __construct()
    {
        parent::__construct();
    }
	/**
	 * @return Generator
	 */
	public function setBaseDir(): Generator {
        $this->baseDir = 'controllers';
        return $this;
	}
	
	/**
	 * @return Generator
	 */
	public function setDefaultNameSapce(): Generator {
        $this->defaultNameSapce = (env('app.defaultNamespace')??'app\\').'controllers';
        return $this;
	}
}
