<?php

namespace App\Sources;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Adp
{
    public function __construct() {
	$this->config=Config::get('sources.adp');
	$this->endpoints=Config::get('sources.adp.endpoints');
    }

    public function processSchedules($shift = NULL) {
	$gateway = $this->config['gateway'];
	$gatewayAuth = $gateway.$this->endpoints['auth']['uri'];
	$gatewaySchedule = $gateway.$this->endpoints['schedules']['uri'];

	$authParams = $this->endpoints['auth']['data'];
	
	if ($shift == 'third') {
	    $scheduleParams = $this->endpoints['schedules']['data']['third'];
	} else {
	    $scheduleParams = $this->endpoints['schedules']['data']['normal'];
	}

	$token = $this->getAdpToken($gatewayAuth, $authParams);
	$schedules = $this->getAdpSchedules($gatewaySchedule, $scheduleParams, $token['token']);

	foreach ($schedules as $user) {
            DB::table($this->config['schedule_table'])->updateOrInsert(
                [
                    'id' => $user['id'],
                ],
                [
                    'name' => $user['name'],
                    'start_time' => $user['start_time'],
                    'end_time' => $user['end_time'],
                    'email' => $user['email'],
                    'title' => $user['title'],
                    'type' => $user['type']
                ]
            );
        }

    }

    private function getAdpToken($uri, $params) {
	$request = Http::asForm()->post($uri, [
	    'username' => $params['username'],
	    'password' => $params['password']
	]);

	return $request->json();

    }

    private function getAdpSchedules($gateway, $data, $token) {
	$schedules = [];

	$mappsSchedules = Http::withToken($token)->get($gateway, $data)->json();

	foreach ($mappsSchedules['data'] as $user) {
	    if (is_array($user['person']['user']) && $user['person']['user']['dept'] == 'managed-applications') {
	        preg_match('/support tech|ssaii?/',$user['person']['user']['title'],$matches);
	    } else {
		$matches = [];
	    }

	    if (count($matches) > 0) {
		    $userData =  [
			'id' => $user['person']['id'],
			'name' => $user['person']['first_name'].' '.$user['person']['last_name'],
			'start_time' => $user['start_date'].' '.$user['start_time'],
			'end_time' => $user['end_date'].' '.$user['end_time'],
			'email' => $user['person']['email'], 
			'title' => $user['person']['user']['title'],
			'type' => $user['type']
		    ];

	    	    $schedules[] = $userData;
	    } else {
		continue;
	    }


	}

	return $schedules;

    }
}
