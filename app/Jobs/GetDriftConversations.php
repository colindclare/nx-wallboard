<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class GetDriftUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $config = Config::get('sources.drift_users');

        $response = Http::withToken($config["token"])->get($config["gateway"]);

        if($response->ok()){
            \App\Sources\DriftUsers::process($config, $response);
        } else {
            //TODO
        }

    }

}
