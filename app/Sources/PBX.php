<?php

namespace App\Sources;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

use PAMI\Client\Impl\ClientImpl as PamiClient;
use PAMI\Message\Action\QueueStatusAction;

class PBX
{

    const QUEUES = [
        "T1" => 101,
        "T2" => 102,
        "ESG" => 103
    ];

    const QUEUE_STATUS = [
        1 => "Available",
        2 => "In Call",
        3 => "Busy",
        4 => "Invalid",
        5 => "Unavailable",
        6 => "Ringing",
        7 => "Ring In Use",
        8 => "On Hold"
    ];

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
            'connect_timeout' => 60,
            'read_timeout' => 60
        );

        try {

            $pamiClient = new PamiClient($pamiClientOptions);
            $pamiClient->open();

            $users = array();
            $queues = array();

            foreach(self::QUEUES as $key => $value){

                $response = $pamiClient->send(new QueueStatusAction($value));

                foreach($response->getEvents() as $event){

                    switch (get_class($event)) {

                        case "PAMI\Message\Event\QueueParamsEvent":
                            $queues[] = [
                                "queue" => $key,
                                "max" => $event->getMax(),
                                "calls" => $event->getCalls(),
                                "hold_time" => $event->getHoldTime(),
                                "completed" => $event->getCompleted(),
                                "abandoned" => $event->getAbandoned(),
                                "service_level" => $event->getServiceLevel(),
                                "service_level_perf" => $event->getServiceLevelPerf()
                            ];
                            break;

                        case "PAMI\Message\Event\QueueMemberEvent":
                            $users[] = [
                                "queue" => $key,
                                "name" => $event->getMemberName(),
                                "membership" => $event->getMembership(),
                                "calls_taken" => $event->getCallsTaken(),
                                "status" => self::QUEUE_STATUS[$event->getStatus()],
                                "paused" => $event->getPaused()
                            ];
                            break;

                        default:
                            break;
                    }

                }

            }

            DB::table(
                $this->config['queue_users_table']
            )->truncate();

            DB::table(
                $this->config['queue_users_table']
            )->insert($users);

            DB::table(
                $this->config['queue_users_table']
            )->truncate();

            DB::table(
                $this->config['queues_table']
            )->insert($queues);

            $pamiClient->close();

        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        }

    }

}
