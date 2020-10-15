<?php

declare(ticks=1);

namespace App\Sources;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use PAMI\Client\Impl\ClientImpl as Client;
use PAMI\Message\Action\QueueStatusAction;
use PAMI\Message\Action\EventsAction;
use PAMI\Message\Action\PingAction;

class PBX
{

    const QUEUES = [
        "T1" => 101,
        "T2" => 102,
        "ESG" => 103
    ];

    const PING_INTERVAL = 300;
    const MAX_RECONNECT_ATTEMPTS = 4;

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
            'connect_timeout' => 60,
            'read_timeout' => 6000
        );

        if (!$this->_startClient()) {
            return false;
        }

    }

    private function _startClient($attempts = 0)
    {

        if ($attempts >= self::MAX_RECONNECT_ATTEMPTS) {
            Log::error("PBX Client Start giving up after ".$attempts." attempts.");
            return false;
        }

        try {

            if (!is_null($this->client)) {
                $this->client->close();
            }

            $this->client = new Client($this->clientOptions);
            $this->client->open();
            //$this->client->setLogger(Log::getLogger());

            $this->client->send(
                new EventsAction(array('call','agent'))
            );

            $this->client->registerEventListener(array($this,'processEvent'));
        } catch (\Throwable $e) {

            Log::error("PBX Client Start encountered error. Starting attempt ".++$attempts.": ".get_class($e)." ".$e->getMessage());

            if (!$this->_startClient($attempts)) {
                return false;
            }

        }

    }

    public function process()
    {

        try {

            $users = array();
            $queues = array();
            $entries = array();

            $sync_id = Str::random(8);

            $this->client->send(new EventsAction());

            foreach (SELF::QUEUES as $key => $value) {

                $response = $this->client->send(new QueueStatusAction($value));

                foreach ($response->getEvents() as $event) {

                    switch ($event->getName()) {

                    case "QueueParams":
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

                    case "QueueMember":

                        if ($event->getStatus() != SELF::QUEUE_STATUS["IN_CALL"]) {
                            $call_start = 'NULL';
                        } else {
                            $call_start = 'case when call_start is NULL then now() else call_start end';
                        }

                        DB::table(
                            $this->config['queue_users_table']
                        )->updateOrInsert(
                            [
                                "queue" => $event->getQueue(),
                                "name" => $event->getMemberName()
                            ],
                            [
                                "membership" => $event->getMembership(),
                                "calls_taken" => $event->getCallsTaken(),
                                "status" => $event->getStatus(),
                                "paused" => $event->getPaused(),
                                "sync_id" => $sync_id,
                                "call_start" => DB::raw($call_start)
                            ]
                        );
                        break;

                    case "QueueEntry":
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
            )->where('sync_id', '!=', $sync_id)->delete();

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
            Log::error("PBX Cron: ".get_class($e)." ".$e->getMessage());
        }

        $this->_checkDaemon();

    }

    private function _checkDaemon()
    {

        exec('pgrep -f "php ('.base_path('artisan').'|artisan) pbx:daemon"', $pids);

        if (empty($pids)) {
            exec('nohup php '.base_path('artisan').' pbx:daemon > /dev/null &', $pids);
        }

    }

    public function runDaemon()
    {

        register_tick_function(array(&$this, 'callProcessFunc'), true);

        $ping_timer = 0;

        while (true) {

            sleep(1);

            $ping_timer+=1;

            if ($ping_timer >= self::PING_INTERVAL) {

                echo date("H:i:s") ." PING\n";

                try {
                    $response = $this->client->send(
                        new PingAction()
                    );

                    if (!$response->isSuccess()) {
                        throw new \PAMI\Client\Exception\ClientException();
                    }

                } catch(\PAMI\Client\Exception\ClientException $e){

                    Log::error("PBX Daemon - Ping: Recovering from ".get_class($e)." ".$e->getMessage());
                    if (!$this->_startClient()) {
                        return false;
                    }

                }

                $ping_timer = 0;
            }

        }

        $this->client->close();

        return;

    }

    public function callProcessFunc()
    {

        try {

            $this->client->process();

        } catch(\Throwable $e){

            Log::error("PBX Daemon: Recovering from ".get_class($e)." ".$e->getMessage());
            if (!$this->_startClient()) {
                return false;
            }
        }

    }

    public function processEvent($event)
    {

        try {
            switch ($event->getName()) {

            case "AgentConnect":
                if (in_array($event->getQueue(), SELF::QUEUES)) {
                    echo "Agent ".$event->getMemberName()." started call\n";
                    Log::channel('pbx')->debug($event->getRawContent());
                    DB::table(
                        $this->config['queue_users_table']
                    )->where(
                        "name", $event->getMemberName()
                    )->update(
                        [
                            "status" => SELF::QUEUE_STATUS["IN_CALL"],
                            "call_start" => DB::raw('now()')
                        ]
                    );
                }
                break;

            case "AgentComplete":
                if (in_array($event->getQueue(), SELF::QUEUES)) {
                    echo "Agent ".$event->getMemberName()." finished call\n";
                    Log::channel('pbx')->debug($event->getRawContent());
                    DB::table(
                        $this->config['queue_users_table']
                    )->where(
                        "name", $event->getMemberName()
                    )->update(
                        [
                            "status" => SELF::QUEUE_STATUS["AVAILABLE"],
                            "call_start" => null
                        ]
                    );
                }
                break;

            case "QueueCallerJoin":
                if (in_array($event->getQueue(), SELF::QUEUES)) {
                    echo "Caller ".$event->getCallerIDNum()." is calling\n";
                    Log::channel('pbx')->debug($event->getRawContent());
                    DB::table(
                        $this->config['queue_entries_table']
                    )->updateOrInsert(
                        [
                            "channel" => $event->getChannel()
                        ],
                        [
                            "queue" => $event->getQueue(),
                            "position" => $event->getPosition(),
                            "calleridnum" => $event->getCallerIDNum(),
                            "calleridname" => $event->getCallerIDName(),
                            "call_time" => null
                        ]
                    );
                }
                break;

            case "QueueCallerAbandon":
            case "QueueCallerLeave":
                if (in_array($event->getQueue(), SELF::QUEUES)) {
                    echo "Caller ".$event->getCallerIDNum()." left queue\n";
                    Log::channel('pbx')->debug($event->getRawContent());
                    DB::table(
                        $this->config['queue_entries_table']
                    )->where(
                        "channel", $event->getChannel()
                    )->delete();
                }
                break;

            case "QueueMemberAdded":
                if (in_array($event->getQueue(), SELF::QUEUES)) {
                    echo "Agent ".$event->getMemberName()." logged in\n";
                    Log::channel('pbx')->debug($event->getRawContent());
                    DB::table(
                        $this->config['queue_users_table']
                    )->updateOrInsert(
                        [
                            "queue" => $event->getQueue(),
                            "name" => $event->getMemberName()
                        ],
                        [
                            "membership" => $event->getMembership(),
                            "calls_taken" => $event->getCallsTaken(),
                            "status" => SELF::QUEUE_STATUS["AVAILABLE"],
                            "paused" => $event->getPaused()
                        ]
                    );
                }
                break;

            case "QueueMemberRemoved":
                if (in_array($event->getQueue(), SELF::QUEUES)) {
                    echo "Agent ".$event->getMemberName()." logged out\n";
                    Log::channel('pbx')->debug($event->getRawContent());
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
                if (in_array($event->getQueue(), SELF::QUEUES)) {
                    echo "Agent ".$event->getMemberName()." Paused: ".$event->getPaused()."\n";
                    Log::channel('pbx')->debug($event->getRawContent());
                    DB::table(
                        $this->config['queue_users_table']
                    )->where(
                        "name", $event->getMemberName()
                    )->update(
                        [
                            "paused" => $event->getPaused()
                        ]
                    );
                }
                break;

            case "AgentCalled":
                if (in_array($event->getQueue(), SELF::QUEUES)) {
                    echo "Agent ".$event->getMemberName()." Ringing\n";
                    Log::channel('pbx')->debug($event->getRawContent());
                    DB::table(
                        $this->config['queue_users_table']
                    )->where(
                        "name", $event->getMemberName()
                    )->update(
                        [
                            "status" => SELF::QUEUE_STATUS["RINGING"],
                        ]
                    );
                }
                break;

            case "AgentRingNoAnswer":
                if (in_array($event->getQueue(), SELF::QUEUES)) {
                    echo "Agent ".$event->getMemberName()." Not Ringing\n";
                    Log::channel('pbx')->debug($event->getRawContent());
                    DB::table(
                        $this->config['queue_users_table']
                    )->where(
                        "name", $event->getMemberName()
                    )->update(
                        [
                            "status" => SELF::QUEUE_STATUS["AVAILABLE"],
                        ]
                    );
                }
                break;

            default:
                break;

            }
        } catch(\Throwable $e) {
            Log::error("PBX Daemon: ".get_class($e)." ".$e->getMessage());
        }
    }
}
