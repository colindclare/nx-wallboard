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
	$paginate = true;

        $response = $request->get($endpoint, $data, $headers);

        $result = json_decode($response, TRUE);

	if (!count($result) < 250 && array_key_exists('pageIndex', $data)) {
	    $data['pageIndex']++;
	    while($paginate) {
		    
		$nextPage = json_decode($request->get($endpoint, $data, $headers), TRUE);	

		if (!count($nextPage) < 250) {
		    $data['pageIndex']++;
		    $result = array_merge($result, $nextPage);
		} else {
		    $paginate = false;
		}
	    }    
	}
	
	return $result;

    }

    public function processUsers() {

	$users = $this->getAllUsers();

	$supportUsers = $this->getSupportUsers($users);
	
	foreach ($supportUsers as $user) {
	    DB::table($this->config['user_table'])->updateOrInsert(
                [
                    'id' => $user['id'],
                ],
                [
                    'nickname' => $user['nickname'],
                    'email' => $user['email'],
		    'activity' => $user['activity'],
		    'login_date' => $user['login_date'],
		    'activity_date' => $user['activity_date'],
		    'role' => $user['role']
                ]
            );
	}
    }

    public function processUserActivity() {
	
	$users = $this->getAllUsers();

	foreach ($users as $user) {
		DB::table($this->config['user_table'])
			->where('id', $user['id'])
			->update(
                [
                    'activity' => $user['activity'],
                    'login_date' => $user['login_date'],
                    'activity_date' => $user['activity_date'],
                ]
            );
        }
    }

    
    public function processTicketQueue() {
	$tickets['support'] = $this->getAllTickets('support');
	$tickets['migrations'] = $this->getAllTickets('migrations');

	$ticketTotals['support'] = $this->countTickets($tickets['support'], $this->config['escalations']['support']);
	$ticketTotals['migrations'] = $this->countTickets($tickets['migrations'], $this->config['escalations']['migrations']);

        foreach ($ticketTotals['support'] as $total) {
            DB::table($this->config['ticket_table'])->updateOrInsert(
                [
                    'id' => $total['id'],
                ],
                [
		    'total' => $total['total'],	
		    'noirt' => $total['noirt'],	
                    'escalation' => $total['escalation']
                ]
            );
	}	
        foreach ($ticketTotals['migrations'] as $total) {
            DB::table($this->config['migrations_table'])->updateOrInsert(
                [
                    'id' => $total['id'],
                ],
                [
		    'total' => $total['total'],	
		    'noirt' => $total['noirt'],	
                    'escalation' => $total['escalation']
                ]
            );
	}	
    }

    public function processSiteDowns() {
	$tickets = $this->getSiteDownTickets();
	DB::table($this->config['site_down_table'])->truncate();

	foreach ($tickets as $ticket) {
	    DB::table($this->config['site_down_table'])->updateOrInsert(
		[
		    'id' => $ticket['id']
		],
		[
		    'mask' => $ticket['mask'],
		    'ticket_link' => 'https://nocworx.nexcess.net/ticket/'.$ticket['id'],
		    'client_wait' => $ticket['client_wait_date']
		]
	    );

	}
    }

    private function getAllUsers() {
	$uri = $this->endpoints['all_users']['uri'];
	$data = $this->endpoints['all_users']['data'];

	return $users = $this->callNocworx($uri, $data);

    }

    private function getUser($uid) {
	$uri = $this->endpoints['all_users']['uri'].'/'.$uid;
	$data = [];

	return $user = $this->callNocworx($uri, $data);

    }

    private function getSupportUsers($users) {

	$supportUsers = [];
	$supportRoleIds = [
		2 => 'Level 1 Techs',
		12 => 'Level 2 Techs',
		16 => 'ESG',
		27 => 'Support Leader'
	];

	foreach ($users as $user) {
	    $user_data = $this->getUser($user['user_id']);
	    $user_roles = $user_data['roles'];
	    $supportRolesHeld = [];
	    $finalData = [];
	    
	    foreach ($user_roles as $role) {
		if (array_key_exists($role['role_id'], $supportRoleIds)) {
		    array_push($supportRolesHeld, $role['role_id']);
		}
	    }

	   

	    if (count($supportRolesHeld) > 0) {
		rsort($supportRolesHeld);
	        $finalData = [
	            'id' => $user['user_id'],
                    'nickname' => $user['nickname'],
                    'email' => $user['email'],
                    'activity' => $user['activity'],
                    'login_date' => $user['login_date'],
                    'activity_date' => $user['activity_date'],
                    'role' => $supportRoleIds[$supportRolesHeld[0]]
	        ];

	        $supportUsers[] = $finalData;
	    }
	}

	return $supportUsers;
    }

    private function getAllTickets($endpoint) {
	$uri = $this->endpoints[$endpoint]['uri'];
	$data = $this->endpoints[$endpoint]['data'];

	return $tix = $this->callNocworx($uri, $data);
    }

    private function countTickets($queue, $escalations) {
	// Instantiate arrays
	foreach ($escalations as $esc) {
	    $ticketCounts[$esc['name']] = 0;
	    $noIRTCounts[$esc['name']] = 0;
	}

        foreach ($queue as $ticket) { 
            if (array_key_exists('escalation', $ticket)) { 
		$escId = $ticket['escalation']['id'];
		$escalation = $escalations[$escId]['name'];
            } else { 
                $escalation = 'Unescalated';
            } 

            // Determine if IRT'd and increase non-IRT count per esclation and total non-IRT
            if ($ticket['num_posts_staff'] == 0) { 
                $noIRTCounts[$escalation]++;
                $noIRTCounts['Total']++;
            } 

            $ticketCounts[$escalation]++;
            $ticketCounts['Total']++;
        } 

        foreach ($escalations as $id => $esc) { 
            $perEscalation = [ 
                    'id' => $esc['id'],
                    'total' => $ticketCounts[$esc['name']],
                    'noirt' => $noIRTCounts[$esc['name']],
                    'escalation' => $esc['name']
            ];

        $ticketTotals[] = $perEscalation;
        } 

        return $ticketTotals;
    }

    private function getSiteDownTickets() {
	$uri = $this->endpoints['site_down_tickets']['uri'];
	$data = $this->endpoints['site_down_tickets']['data'];

	return $tix = $this->callNocworx($uri, $data);
    }
}

