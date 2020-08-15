<?php

namespace App\Sources;

use App\NocWorx\Lib\OAuth\Credentials;
use App\NocWorx\Lib\OAuth\Request;

class NocWorx {

    public static function callNocworx($config, $endpoint, $data) {
	
	$credentials = new Credentials($config['public'],$config['private']);
        $request = new Request($config['gateway'], $credentials);
        $headers = ['Accept' => 'application/json'];    

        $response = $request->get($endpoint, $data, $headers);

        $result = json_decode($response, TRUE);
	
	return $result;

    }

}

