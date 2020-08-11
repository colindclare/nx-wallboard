<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class GetData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'getdata';

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
        $config = Config::get('sources.drift_conversations');

        if($response->ok()){

            $convo_list = $response->getConversationList();

            foreach($convo_list["data"] as $convo){

                //DB::table($config['table'])->updateOrInsert(
                //[
                //'bot' => $convo["bot"]
                //],
                //['availability' => $convo["availability"]]
                //);

                //$convo_history[] = [
                //'availability' => $convo["availability"]
                //];
            }

            //DB::table($config['history_table'])->insert($convo_history);

        } else {
            //TODO
        }

        return 0;
    }

    private function getUserMapping() {

        $response = Http::withToken($config["token"])->get($config["gateway"], [
            'statusId' => '1',
            'statusId' => '1',
            'limit' => $config["chunk_size"]
        ]);

    }

    private function getConversationList() {

        $body = [
            "filters" => [
                [
                    "property" => "lastAgentId",
                    "operation" => "HAS_PROPERTY"
                ],
                [
                    "property" => "updatedAt",
                    "operation" => "GT",
                    "value" => "2020-08-10"
                ]
            ],
            "metrics" => [
                "lastAgentId"
            ]
        ];

        $response = Http::withToken(
            $config["token"]
        )->withBody(
            json_encode($body),
            "application/json"
        )->post(
            $config["report_gateway"]
        );

        echo print_r($response->json(),true);

    }
}
