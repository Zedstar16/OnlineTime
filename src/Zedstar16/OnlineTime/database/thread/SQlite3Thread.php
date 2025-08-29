<?php

declare(strict_types=1);

namespace Zedstar16\OnlineTime\database\thread;

use pmmp\thread\ThreadSafeArray;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\log\ThreadSafeLogger;
use SQLite3;
use SQLite3Result;
use Throwable;
use Zedstar16\OnlineTime\database\thread\message\ThreadMessage;
use Zedstar16\OnlineTime\database\thread\message\ThreadResponse;
use function gc_collect_cycles;
use function gc_enable;
use function gc_mem_caches;
use function igbinary_serialize;
use function igbinary_unserialize;

class SQlite3Thread extends DatabaseThread {

    private string $db_path;

    public function __construct(int $threadID, SleeperHandlerEntry $sleeperHandlerEntry, $db_path, ThreadSafeLogger $logger) {
        parent::__construct($threadID, $sleeperHandlerEntry, $logger);
        $this->threadID = $threadID;
        $this->actionQueue = new ThreadSafeArray();
        $this->actionResults = new ThreadSafeArray();
        $this->sleeperHandlerEntry = $sleeperHandlerEntry;
        $this->db_path = $db_path;
        $this->logger = $logger;
    }

    public function onRun(): void {
        $con = new SQLite3($this->db_path);
        $this->logger->info("Established connection to SQlite database");
        $notifier = $this->sleeperHandlerEntry->createNotifier();
        while ($this->running) {
            while (($queue = $this->actionQueue->shift()) !== null) {
                try {
                    $this->inUse = 1;
                    /** @var ThreadMessage $message */
                    $message = igbinary_unserialize($queue);
                    if ($message->getQueryType() === ThreadMessage::TYPE_GC_COLLECT) {
                        gc_enable();
                        gc_collect_cycles();
                        gc_mem_caches();
                        $this->inUse = 0;
                        continue;
                    }
                    $result = null;
                    $query = $message->getQuery();
                    $queryType = $message->getQueryType();
                    if ($queryType === ThreadMessage::TYPE_EXEC) {
                        $result = $con->exec($query);
                    } else {
                        $queryResult = $con->query($query);
                        if ($queryResult instanceof SQLite3Result) {
                            if ($queryType === ThreadMessage::TYPE_QUERY_SINGLE) {
                                $result = $queryResult->fetchArray(SQLITE3_ASSOC);
                            } elseif ($queryType === ThreadMessage::TYPE_QUERY_ALL) {
                                $rows = [];
                                while ($row = $queryResult->fetchArray(SQLITE3_ASSOC)) {
                                    $rows[] = $row;
                                }
                                $result = $rows;
                            }
                        }
                    }
                    $response = new ThreadResponse($message->getRequestID(), $result);
                    $this->actionResults[] = igbinary_serialize($response);
                    $notifier->wakeupSleeper();
                } catch (Throwable $error) {
                    $this->logger->logException($error);
                }
                $this->inUse = 0;
            }
            $this->sleep();
        }
    }
}