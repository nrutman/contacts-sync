<?php

namespace Sync\Config;

use Symfony\Component\Yaml\Yaml;

class ConfigParser
{
    /**
     * Returns application configuration object as an array
     *
     * @return array
     */
    public static function getConfiguration(): array
    {
        return Yaml::parseFile(sprintf('%s/../../config/parameters.yml', __DIR__));
    }
}
