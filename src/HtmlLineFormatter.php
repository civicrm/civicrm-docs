<?php

use Monolog\Formatter\NormalizerFormatter;

class HtmlLineFormatter extends NormalizerFormatter
{
    protected $logLevels = array(
        'DEBUG' => '#cccccc',
        'INFO' => '#468847',
        'NOTICE' => '#3a87ad',
        'WARNING' => '#c09853',
        'ERROR' => '#f0ad4e',
        'CRITICAL' => '#FF7708',
        'ALERT' => '#C12A19',
        'EMERGENCY' => '#000000',
    );

    public function __construct(){
        parent::__construct();
    }
    
    public function format(array $record)
    {
        return  "<p>[{$record['datetime']->format(parent::SIMPLE_DATE)}] <span style='font-weight: bold; color:{$this->logLevels[$record['level_name']]};'>{$record['level_name']}</span>: {$record['message']}</p>";
    }
}
