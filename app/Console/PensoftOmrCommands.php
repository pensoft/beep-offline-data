<?php

namespace App\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App;

class PensoftOmrCommands extends Command
{
    protected \Psr\Log\LoggerInterface $log;
    protected bool                     $force;

    public function __construct()
    {
        parent::__construct();

        $this->log = Log::channel('PensoftOMRCommands');
    }

    public function setAttributesFromOptionsAndArguments()
    {
        if ($this->argument('force')) {
            $this->force = true;
        }
    }

    public function writeOutput($message, $style = 'info')
    {
        $this->$style($message);

        $this->writeLog($message, $style);
    }

    public function writeLog($message, $style = 'info')
    {
        $this->log->{$style}($message);
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLog(): \Psr\Log\LoggerInterface
    {
        return $this->log;
    }
}
