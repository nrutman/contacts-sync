<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;

class ConfigureSyncCommand extends Command
{
    /** @var string */
    public static $defaultName = 'sync:configure';
}