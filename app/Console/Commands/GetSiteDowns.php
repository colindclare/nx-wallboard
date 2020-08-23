<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Sources\NocWorx;
class GetSiteDowns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nocworx:site-down';

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
	    $nocworx = new NocWorx();
	    $nocworx->processSiteDowns();
    }
}
