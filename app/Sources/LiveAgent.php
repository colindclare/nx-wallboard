<?php

namespace App\Sources;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LiveAgent
{

    const DEPARTMENT = "maps";
    const OMNI_STATUS = "livechat";

    protected $config;

    public function __construct()
    {
        $this->config = Config::get('sources.liveagent');
    }

    public function process()
    {

        $users = $this->_getUsers();
        $queue = $this->_getQueue();

    }

    private function _getUsers()
    {
        $rawUsers = Http::withOptions(
            [
                'timeout' => 60
            ]
        )->get(
            $this->config['users']['gateway']
        )->throw()->json();

        $users = array();

        foreach ($rawUsers as $user => $data) {
            if (isset($data["dept"])
                && $data["dept"] == self::DEPARTMENT
                && isset($data["omni"])
                && $data["omni"] ==  self::OMNI_STATUS
            ) {
                $users[] =  [
                    "username" => $user,
                    "name" => $data["name"],
                    "status" => $data["chats_status"],
                    "chats" => isset($data["chats_data"])
                    ? count($data["chats_data"]) : 0
                ];
            }
        }

        return $users;
    }

    private function _getQueue() {
        $queue = Http::withOptions(
            [
                'timeout' => 60
            ]
        )->get(
            $this->config['queue']['gateway']
        )->throw()->json();

        return [
            "queue" => $queue["queue"],
            "active" => $queue["active"],
            "capacity" => $queue["limit"]
        ];

    }

    private function _processUsers(Array $convo_counts)
    {

        $userlist = Http::withOptions(
            [
                'timeout' => 60
            ]
        )->get(
            $this->config['users']['gateway']
        )->throw()->json();

        try {

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

            //DB::table(
                //$this->config['users']['history_table']
            //)->insert($users_history);

        } catch (\PDOException $e) {
            Log::error($e->getMessage());
            return false;
        }
    }
}
