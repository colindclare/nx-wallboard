<?php

declare(ticks=1);

namespace App\Sources;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

use PAMI\Client\Impl\ClientImpl as Client;
use PAMI\Message\Action\QueueStatusAction;
use PAMI\Message\Action\EventsAction;

class PBX
{

    const QUEUES = [
        "T1" => 101,
        "T2" => 102,
        "ESG" => 103
    ];

    const QUEUE_STATUS = [
        "AVAILABLE" => 1,
        "IN_CALL" => 2,
        "BUSY" => 3,
        "INVALID" => 4,
        "UNAVAILABLE" => 5,
        "RINGING" => 6,
        "RING_IN_USE" => 7,
        "ON_HOLD" => 8
    ];

    protected $config;
    protected $clientOptions;
    protected $client;

    public function __construct()
    {
        $this->config = Config::get('sources.pbx');

        $this->clientOptions = array(
            'host' => $this->config['host'],
            'scheme' => 'tcp://',
            'port' => $this->config['port'],
            'username' => $this->config['username'],
            'secret' => $this->config['secret'],
            'connect_timeout' => 6000,
            'read_timeout' => 6000
        );

        $this->startClient();

    }

    private function startClient() {

        $this->client = new Client($this->clientOptions);
        $this->client->open();
        //$this->client->setLogger(Log::getLogger());

        $this->client->send(
            new EventsAction(array('call','agent'))
        );

        $this->client->registerEventListener(array($this,'processEvent'));

    }

    public function process() {

        try {

            $this->client->send(new EventsAction());

            $users = array();
            $queues = array();
            $entries = array();

            foreach(self::QUEUES as $key => $value){

                $response = $this->client->send(new QueueStatusAction($value));

                foreach($response->getEvents() as $event){

                    switch (get_class($event)) {

                    case "PAMI\Message\Event\QueueParamsEvent":
                        $queues[] = [
                            "queue" => $event->getQueue(),
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
                            "queue" => $event->getQueue(),
                            "name" => $event->getMemberName(),
                            "membership" => $event->getMembership(),
                            "calls_taken" => $event->getCallsTaken(),
                            "status" => $event->getStatus(),
                            "paused" => $event->getPaused()
                        ];
                        break;

                    case "PAMI\Message\Event\QueueEntryEvent":
                        $entries[] = [
                            "channel" => $event->getChannel(),
                            "queue" => $event->getQueue(),
                            "position" => $event->getPosition(),
                            "calleridnum" => $event->getCallerIDNum(),
                            "calleridname" => $event->getCallerIDName(),
                            "call_time" => DB::raw('now() - INTERVAL '.$event->getWait().' SECOND')
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
                $this->config['queue_entries_table']
            )->truncate();

            DB::table(
                $this->config['queue_entries_table']
            )->insert($entries);

            DB::table(
                $this->config['queues_table']
            )->truncate();

            DB::table(
                $this->config['queues_table']
            )->insert($queues);

            $this->client->close();

        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        }

    }

    public function runDaemon() {

        register_tick_function(array(&$this,'callProcessFunc'),true);

        while(true){
            usleep(1000);
        }

        $this->client->close();

        return;

    }

    public function callProcessFunc() {

        try {

            $this->client->process();

        } catch(\PAMI\Client\Exception\ClientException $e){

            Log::error("PBX Daemon: Recovering from ".$e->getMessage());
            sleep(1);
            $this->client->close();
            $this->startClient();
        }

    }

    public function processEvent($event){

        try {
            switch ($event->getName()) {

                case "AgentConnect":
                    if(in_array($event->getQueue(), SELF::QUEUES)){
                        echo "Agent ".$event->getMemberName()." started call\n";
                        DB::table(
                            $this->config['queue_users_table']
                        )->where(
                            "name", $event->getMemberName()
                        )->update([
                            "status" => SELF::QUEUE_STATUS["IN_CALL"]
                        ]);
                    }
                    break;

                case "AgentComplete":
                    if(in_array($event->getQueue(), SELF::QUEUES)){
                        echo "Agent ".$event->getMemberName()." finished call\n";
                        DB::table(
                            $this->config['queue_users_table']
                        )->where(
                            "name", $event->getMemberName()
                        )->update([
                            "status" => SELF::QUEUE_STATUS["AVAILABLE"]
                        ]);
                    }
                    break;

                case "QueueCallerJoin":
                    if(in_array($event->getQueue(), SELF::QUEUES)){
                        echo "Caller ".$event->getCallerIDNum()." is calling\n";
                        DB::table(
                            $this->config['queue_entries_table']
                        )->insert([
                            "channel" => $event->getChannel(),
                            "queue" => $event->getQueue(),
                            "position" => $event->getPosition(),
                            "calleridnum" => $event->getCallerIDNum(),
                            "calleridname" => $event->getCallerIDName(),
                            "call_time" => NULL
                        ]);
                    }
                    break;

                case "QueueCallerAbandon":
                case "QueueCallerLeave":
                    if(in_array($event->getQueue(), SELF::QUEUES)){
                        echo "Caller ".$event->getCallerIDNum()." left queue\n";
                        DB::table(
                            $this->config['queue_entries_table']
                        )->where(
                            "channel", $event->getChannel()
                        )->delete();
                    }
                    break;

                case "QueueMemberAdded":
                    if(in_array($event->getQueue(), SELF::QUEUES)){
                        echo "Agent ".$event->getMemberName()."logged in\n";
                        DB::table(
                            $this->config['queue_users_table']
                        )->insert([
                            "queue" => $event->getQueue(),
                            "name" => $event->getMemberName(),
                            "membership" => $event->getMembership(),
                            "calls_taken" => $event->getCallsTaken(),
                            "status" => SELF::QUEUE_STATUS["AVAILABLE"],
                            "paused" => $event->getPaused()
                        ]);
                    }
                    break;

                case "QueueMemberRemoved":
                    if(in_array($event->getQueue(), SELF::QUEUES)){
                        echo "Agent ".$event->getMemberName()." logged out\n";
                        DB::table(
                            $this->config['queue_users_table']
                        )->where(
                            "queue", $event->getQueue(),
                        )->where(
                            "name", $event->getMemberName()
                        )->delete();
                    }
                    break;

                case "QueueMemberPause":
                    if(in_array($event->getQueue(), SELF::QUEUES)){
                        echo "Agent ".$event->getMemberName()." Paused: ".$event->getPaused()."\n";
                        DB::table(
                            $this->config['queue_users_table']
                        )->where(
                            "name", $event->getMemberName()
                        )->update([
                            "paused" => $event->getPaused()
                        ]);
                    }
                    break;

                case "AgentCalled":
                    if(in_array($event->getQueue(), SELF::QUEUES)){
                        echo "Agent ".$event->getMemberName()." Ringing\n";
                        DB::table(
                            $this->config['queue_users_table']
                        )->where(
                            "name", $event->getMemberName()
                        )->update([
                            "status" => SELF::QUEUE_STATUS["RINGING"],
                        ]);
                    }
                    break;

                case "AgentRingNoAnswer":
                    if(in_array($event->getQueue(), SELF::QUEUES)){
                        echo "Agent ".$event->getMemberName()." Not Ringing\n";
                        DB::table(
                            $this->config['queue_users_table']
                        )->where(
                            "name", $event->getMemberName()
                        )->update([
                            "status" => SELF::QUEUE_STATUS["AVAILABLE"],
                        ]);
                    }
                    break;

                default:
                    break;

            }
        } catch(\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
