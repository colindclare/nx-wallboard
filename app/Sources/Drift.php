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

    const SUPPORT_TAG = 2216153;
    const BOT = 2025865;

    protected $config;

    public function __construct()
    {
        $this->config = Config::get('sources.drift');
    }

    public function process() {

        $conversations = $this->getOpenConversations();

        $convo_counts = array();

        foreach($conversations as $conversation => $user){

            if($user === "NaN") {

                $user = Http::withToken(
                    $this->config['token']
                )->get(
                    $this->config['conversations']['gateway'] . $conversation
                )->throw()->json();

                if(isset($user['data']['participants'][0])) {
                    $user = $user['data']['participants'][0];
                } else {
                    $user = self::BOT;
                }

            }

            if(isset($convo_counts[$user])){
                $convo_counts[$user] += 1;
            } else {
                $convo_counts[$user] = 1;
            }

        }

        $this->processUsers($convo_counts);

        $this->updateTotals(count($conversations));

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

        $body = [
            'filters' => [
                [
                    'property' => 'status',
                    'operation' => 'EQ',
                    'value' => 'open'
                ],
                [
                    'property' => 'tags',
                    'operation' => 'EQ',
                    'value' => self::SUPPORT_TAG
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

        if (!isset($conversation_list['data'])){
            return array();
        }

        foreach($conversation_list['data'] as $conversation){
            $map[$conversation['conversationId']] = $conversation['metrics'][0];
        }

        return $map;

    }

    private function updateTotals($count) {

        try {

            DB::table(
                $this->config['conversations']['history_table']
            )->insert(
                [
                    'open' => $count
                ]
            );

        } catch (\PDOException $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

}
