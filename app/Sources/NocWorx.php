<?php

namespace App\Sources;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
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

    public function processTicketQueue() {
	$tickets['support'] = $this->getAllTickets('support');
	$tickets['migrations'] = $this->getAllTickets('migrations');
	$tickets['support_hdm'] = $this->getAllTickets('support_hdm');

	$ticketTotals['support'] = $this->countTickets($tickets['support'], $this->config['escalations']['support']);
	$ticketTotals['migrations'] = $this->countTickets($tickets['migrations'], $this->config['escalations']['migrations']);

	$hdmWpCount = count($tickets['support_hdm']);
	$ticketTotals['support'][7]['total'] = $ticketTotals['support'][7]['total'] + $hdmWpCount;

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
		$ticketEscId = $ticket['escalation']['id'];
		if (array_key_exists($ticketEscId, $escalations)) {
		    $escalation = $escalations[$ticketEscId]['name'];
		} else {
		    Log::error("Unidentified escalation found: $ticketEscId, ".$ticket['escalation']['identity']);
		    $escalation = 'Unescalated';
		}
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

