<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Sources\Adp;
class AdpSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adp:schedules {schedule=NULL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
	$adp = new Adp();
	$schedule = $this->argument('schedule');
	$adp->processSchedules($schedule);
    }
}
