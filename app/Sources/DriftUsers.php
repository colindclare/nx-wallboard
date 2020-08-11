<?php

namespace App\Sources;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;

class DriftUsers
{
    public static function process(Array $config, Response $response) {

        $userlist = $response->json();

        foreach($userlist["data"] as $user){

            DB::table($config['table'])->updateOrInsert(
                [
                    'id' => $user["id"],
                    'orgId' => $user["orgId"],
                    'name' => $user["name"],
                    'alias' => $user["alias"],
                    'email' => $user["email"],
                    'role' => $user["role"],
                    'verified' => $user["verified"],
                    'bot' => $user["bot"]
                ],
                ['availability' => $user["availability"]]
            );

            $users_history[] = [
                'id' => $user["id"],
                'availability' => $user["availability"]
            ];

        }

        DB::table($config['history_table'])->insert($users_history);

        return true;
    }

}
