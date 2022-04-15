<?php
namespace BladeCLI\Support;

use Illuminate\Container\Container;
use Illuminate\Console\Command as BaseCommand;

class Command extends BaseCommand
{
    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->laravel = new Container;
        parent::__construct();
    }
}
