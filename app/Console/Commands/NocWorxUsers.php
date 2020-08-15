<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

use App\Sources\NocWorx;

class NocWorxUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nocworx:users';

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
	$this->config = Config::get('sources.nocworx_oauth');
	$this->allUsers = $this->config['nocworx_all_users'];
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userData = NocWorx::callNocworx($this->config, $this->allUsers['endpoint'],$this->allUsers['data']);

        var_dump($userData);	
    }
}
