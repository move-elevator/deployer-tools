<?php

namespace MoveElevator\FeatureIndex\Service;

class ConfigReader
{

    public array $config;

    /**
     * @return mixed
     */
    public function initConfig()
    {
        $strJsonFileContents = file_get_contents('.fbd/index.json');
        $this->config = \json_decode($strJsonFileContents, true);
        return $this->config;
    }
}
