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

    protected $config;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->config = Config::get('sources.drift_conversations');
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $conversations = $this->getOpenConversations();
        $user_map = $this->getUserMap();

        foreach($conversations as $conversation){
            // Ignore open converstions that don't have a user, they were likely abandoned
            if(isset($user_map[$conversation])){
                if(isset($convs_per_user[$user_map[$conversation]])){
                    $convos_per_user[$user_map[$conversation]] += 1;
                } else {
                    $convos_per_user[$user_map[$conversation]] = 1;
                }
            }
        }

        echo print_r($convos_per_user,true);

        return 0;
    }

    private function getOpenConversations() {

        $response = Http::withToken(
            $this->config["token"]
        )->get($this->config["gateway"], [
            'statusId' => '1',
            'limit' => $this->config["chunk_size"]
        ]);

        $conversation_list = $response->json();

        foreach($conversation_list["data"] as $conversation){
            $open_conversations[] = $conversation["id"];
        }

        return $open_conversations;

    }

    private function getUserMap() {

        $body = [
            "filters" => [
                [
                    "property" => "lastAgentId",
                    "operation" => "HAS_PROPERTY"
                ],
                [
                    "property" => "updatedAt",
                    "operation" => "GT",
                    "value" => strtotime("-6hour")
                ]
            ],
            "metrics" => [
                "lastAgentId"
            ]
        ];

        $response = Http::withToken(
            $this->config["token"]
        )->withBody(
            json_encode($body),
            "application/json"
        )->post(
            $this->config["report_gateway"]
        );

        $conversation_list = $response->json();

        foreach($conversation_list["data"] as $conversation){
            $map[$conversation["conversationId"]] = $conversation["metrics"][0];
        }

        return $map;

    }
}
