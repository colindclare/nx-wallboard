<?php

namespace App\Sources;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Omni
{

    const DEPARTMENT = "maps";

    protected $config;

    public function __construct()
    {
        $this->config = Config::get('sources.omni');
    }

    public function process()
    {

        $users = $this->_getUsers();
        $queue = $this->_getQueue();

        DB::table(
            $this->config['users']['table']
        )->truncate();

        DB::table(
            $this->config['users']['table']
        )->insert($users);

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
            if (isset($data["dept"]) && $data["dept"] == self::DEPARTMENT) {
                $users[] =  [
                    "username" => $user,
                    "calls" => isset($data["calls"]) ? $data["calls"] : null,
                    "calls_limit" => isset($data["calls_limit"]) ? $data["calls_limit"] : null,
                    "calls_login" => isset($data["calls_login"]) ? $data["calls_login"] : null,
                    "cases" => isset($data["cases"]) ? $data["cases"] : null,
                    "cases" => isset($data["cases_data"]) ? count($data["cases_data"]) : 0,
                    "chats" => isset($data["chats_data"]) ? count($data["chats_data"]) : 0,
                    "chats_limit" => isset($data["chats_limit"]) ? $data["chats_limit"] : null,
                    "chats_login" => isset($data["chats_login"]) ? $data["chats_login"] : null,
                    "chats_status" => isset($data["chats_status"]) ? $data["chats_status"] : null,
                    "dept" => isset($data["dept"]) ? $data["dept"] : null,
                    "dept_team" => isset($data["dept_team"]) ? $data["dept_team"] : null,
                    "name" => isset($data["name"]) ? $data["name"] : $user,
                    "omni" => isset($data["omni"]) ? $data["omni"] : null,
                    "omni_phasing" => isset($data["omni_phasing"]) ? $data["omni_phasing"] : null,
                    "omni_time" => isset($data["omni_time"]) ? $data["omni_time"] : null,
                    "punched" => isset($data["punched"]) ? $data["punched"] : null,
                    "punched_eos" => isset($data["punched_eos"]) ? $data["punched_eos"] : null,
                    "punched_offshift" => isset($data["punched_offshift"]) ? $data["punched_offshift"] : null,
                    "punched_scheduled" => isset($data["punched_scheduled"]) ? $data["punched_scheduled"] : null,
                    "punched_scheduled_miss" => isset($data["punched_scheduled_miss"]) ? $data["punched_scheduled_miss"] : null,
                    "punched_shift" => isset($data["punched_shift"]) ? $data["punched_shift"] : null,
                    "shift" => isset($data["shift"]) ? $data["shift"] : null,
                    "supervisor" => isset($data["supervisor"]) ? $data["supervisor"] : null,
                    "title" => isset($data["title"]) ? $data["title"] : null
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
}
