<?php

namespace App\Sources;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Drift
{

    protected $config;

    public function __construct()
    {
        $this->config = Config::get('sources.drift');
    }

    public function process() {

        $conversations = $this->getOpenConversations();

        $user_map = $this->getUserMap();

        $convo_counts = array();

        foreach($conversations as $conversation){
            // Ignore open converstions that don't have a user, they were likely abandoned
            if(isset($user_map[$conversation])){
                if(isset($convs_per_user[$user_map[$conversation]])){
                    $convo_counts[$user_map[$conversation]] += 1;
                } else {
                    $convo_counts[$user_map[$conversation]] = 1;
                }
            }
        }

        $this->processUsers($convo_counts);

        $this->updateTotals();

    }

    private function processUsers(Array $convo_counts) {

        $userlist = Http::withToken(
            $this->config['token']
        )->get(
            $this->config['users']['gateway']
        )->throw()->json();

        try {

            foreach($userlist['data'] as $user){

                if(!isset($convo_counts[$user['id']])){
                    $convo_counts[$user['id']] = 0;
                }

                DB::table(
                    $this->config['users']['table']
                )->updateOrInsert(
                    [
                        'id' => $user['id'],
                    ],
                    [
                        'name' => $user['name'],
                        'orgId' => $user['orgId'],
                        'alias' => $user['alias'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'verified' => $user['verified'],
                        'bot' => $user['bot'],
                        'availability' => $user['availability'],
                        'conversations' => $convo_counts[$user['id']]
                    ]
                );

                $users_history[] = [
                    'id' => $user['id'],
                    'availability' => $user['availability']
                ];

            }

            DB::table(
                $this->config['users']['history_table']
            )->insert($users_history);

        } catch (\PDOException $e) {
            Log::error($e->getMessage());
            return false;
        }


        return true;
    }

    private function getOpenConversations() {

        $conversation_list = Http::withToken(
            $this->config['token']
        )->get($this->config['conversations']['gateway'], [
            'statusId' => '1',
            'limit' => $this->config['conversations']['chunk_size']
        ])->throw()->json();

        foreach($conversation_list['data'] as $conversation){
            $open_conversations[] = $conversation['id'];
        }

        return $open_conversations;

    }

    private function getUserMap() {

        $body = [
            'filters' => [
                [
                    'property' => 'lastAgentId',
                    'operation' => 'HAS_PROPERTY'
                ],
                [
                    'property' => 'updatedAt',
                    'operation' => 'GT',
                    'value' => strtotime('-6hour')
                ]
            ],
            'metrics' => [
                'lastAgentId'
            ]
        ];

        $conversation_list = Http::withToken(
            $this->config['token']
        )->withBody(
            json_encode($body),
            'application/json'
        )->post(
            $this->config['conversations']['report_gateway']
        )->throw()->json();

        foreach($conversation_list['data'] as $conversation){
            $map[$conversation['conversationId']] = $conversation['metrics'][0];
        }

        return $map;

    }

    private function updateTotals() {

        $stats = Http::withToken(
            $this->config['token']
        )->get(
            $this->config['stats']['gateway']
        )->throw()->json();

        try {

            DB::table(
                $this->config['stats']['history_table']
            )->insert(
                [
                    'open' => $stats['conversationCount']['OPEN'],
                    'pending' => $stats['conversationCount']['PENDING']
                ]
            );

        } catch (\PDOException $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

}
