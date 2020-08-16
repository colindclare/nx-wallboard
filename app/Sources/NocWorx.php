<?php

namespace App\Sources;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\NocWorx\Lib\OAuth\Credentials;
use App\NocWorx\Lib\OAuth\Request;

class NocWorx {

    protected $config;
    protected $endpoints;

    public function __construct() {
	$this->config = Config::get('sources.nocworx');
	$this->endpoints = Config::get('sources.nocworx.endpoints');
    }

    public function callNocworx($endpoint, $data) {
	
	$credentials = new Credentials($this->config['public'],$this->config['private']);
        $request = new Request($this->config['gateway'], $credentials);
        $headers = ['Accept' => 'application/json'];    

        $response = $request->get($endpoint, $data, $headers);

        $result = json_decode($response, TRUE);
	
	return $result;

    }

    public function processUsers() {
	$uri = $this->endpoints['all_users']['uri'];
	$data = $this->endpoints['all_users']['data'];

	$users = $this->callNocworx($uri, $data);

	foreach ($users as $user) {
	    DB::table($this->config['user_table'])->updateOrInsert(
                [
                    'id' => $user['user_id'],
                ],
                [
                    'nickname' => $user['nickname'],
                    'email' => $user['email'],
		    'activity' => $user['activity'],
		    'login_date' => $user['login_date'],
		    'activity_date' => $user['activity_date']
                ]
            );
	}
    }

    public function getUser($uid) {
	$uri = $this->endpoints['all_users']['uri'].'/'.$uid;
	$data = [];

	$user = $this->callNocworx($uri, $data);
	print_r($user);

    }


}

