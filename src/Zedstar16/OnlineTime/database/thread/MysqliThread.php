<?php

declare(strict_types=1);

namespace Zedstar16\OnlineTime\database\thread;

use mysqli;
use mysqli_result;
use pmmp\thread\ThreadSafeArray;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\log\ThreadSafeLogger;

use Throwable;
use function gc_collect_cycles;
use function gc_enable;
use function gc_mem_caches;
use function igbinary_serialize;
use function igbinary_unserialize;

class MysqliThread extends DatabaseThread
{

    private string $db_host, $db_username, $db_password, $db_schema;
    private int $reconnect_sleep_time = 1;

    public function __construct(int $threadID, SleeperHandlerEntry $sleeperHandlerEntry, string $db_host, string $db_username, string $db_password, string $db_schema, ThreadSafeLogger $logger) {
        parent::__construct($threadID, $sleeperHandlerEntry, $logger);
        $this->threadID = $threadID;
        $this->actionQueue = new ThreadSafeArray();
        $this->actionResults = new ThreadSafeArray();
        $this->sleeperHandlerEntry = $sleeperHandlerEntry;
        $this->db_host = $db_host;
        $this->db_username = $db_username;
        $this->db_password = $db_password;
        $this->db_schema = $db_schema;
        $this->logger = $logger;
    }

    public function log(string $log) {
        $this->logger->info("[THREAD #{$this->getDBThreadID()}] ".$log);
    }

    public function attemptConnection($reconnect = false): ?mysqli {
        $error = true;
        $con = null;
        while ($error) {
            $connectstr = $reconnect ? "Reconnect" : "Connect";
            $this->log("Attempting $connectstr to DB");
            try {
                $con = @new mysqli($this->db_host, $this->db_username, $this->db_password, $this->db_schema);
            } catch (Throwable) {
            }
            $error = $con === null || $con->connect_error;
            $this->log(!$error ? "$connectstr Success" : "$connectstr Failed with error: " . ($con?->connect_error ?? "Unable to make connection to DB, retrying in $this->reconnect_sleep_time secs..."));
            if($error) {
                sleep($this->reconnect_sleep_time);
                if($this->reconnect_sleep_time < 120) {
                    $this->reconnect_sleep_time *= 2;
                }
            }
        }
        $this->reconnect_sleep_time = 1;
        return $con;
    }

    public function onRun(): void {
        $this->log("Started DB Thread");
        $con = $this->attemptConnection();
        $notifier = $this->sleeperHandlerEntry->createNotifier();
        while ($this->running) {
            if ($con->connect_error) {
                $this->log("Connection to DB Failed with error: " . $con->connect_error);
                $this->attemptConnection(true);
            }
            while (($queue = $this->actionQueue->shift()) !== null) {
                try {
                    $this->inUse = 1;
                    $queue = igbinary_unserialize($queue);
                    if (is_array($queue) && !empty($queue)) {
                        try {
                            if (!$con->ping()) {
                                $con->close();
                                $con = $this->attemptConnection(true);
                            }
                        }catch (Throwable $exception){
                            $con = $this->attemptConnection(true);
                        }
                        foreach ($queue as $input) {
                            $input = json_decode($input, true);
                            $queryType = $input["queryType"];
                            $query = $input["query"];
                            $queryResult = null;
                            $queryResult = $con->query($query);
                            if ($queryResult instanceof mysqli_result) {
                                if ($queryType === DatabaseThread::TYPE_QUERY_ALL) {
                                    $rows = [];
                                    while ($row = $queryResult->fetch_assoc()) {
                                        $rows[] = $row;
                                    }
                                    $queryResult = $rows;
                                } else {
                                    $queryResult = $queryResult->fetch_assoc();
                                }
                            }
                            if ($queryResult === false) {
                                $this->log("Query Failed: $query");
                            }
                            $result = json_encode([
                                "requestID" => $input["requestID"],
                                "result" => $queryResult
                            ]);
                            $this->actionResults[] = igbinary_serialize($result);
                        }
                        $notifier->wakeupSleeper();
                    } elseif ($queue === "garbage_collector") {
                        gc_enable();
                        gc_collect_cycles();
                        gc_mem_caches();
                    }
                    $this->inUse = 0;
                } catch (Throwable $error) {
                    $this->logger->logException($error);
                    $this->inUse = 0;
                }
            }
            $this->sleep();
        }
    }

}