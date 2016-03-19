<?php

use Monolog\Handler\AbstractProcessingHandler;

class ScreenHandler extends AbstractProcessingHandler
{
    public function __construct($level)
    {
        parent::__construct($level);
    }

    protected function write(array $record)
    {
        echo $record['formatted'];
        
    }
}
