<?php

namespace App\Sources;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

use PAMI\Client\Impl\ClientImpl as PamiClient;
use PAMI\Message\Action\QueueStatusAction;

class PBX
{

    protected $config;

    public function __construct()
    {
        $this->config = Config::get('sources.pbx');

    }

    public function process() {

        $pamiClientOptions = array(
            'host' => $this->config['host'],
            'scheme' => 'tcp://',
            'port' => $this->config['port'],
            'username' => $this->config['username'],
            'secret' => $this->config['secret'],
            'connect_timeout' => 10000,
            'read_timeout' => 10000
        );

        try {
            $pamiClient = new PamiClient($pamiClientOptions);

            // Open the connection
            $pamiClient->open();

            $response = $pamiClient->send(new QueueStatusAction());

            echo print_r($response,true);

            // Close the connection
            $pamiClient->close();

        } catch (\Throwable $e) {
            echo print_r($e->getMessage(),true);
        }

    }

}
