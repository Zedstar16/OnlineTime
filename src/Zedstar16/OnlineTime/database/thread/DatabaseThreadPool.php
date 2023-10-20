<?php

declare(strict_types=1);

namespace Zedstar16\OnlineTime\database\thread;

use pocketmine\Server;
use pocketmine\snooze\SleeperHandlerEntry;
use UnderflowException;
use pocketmine\thread\Thread;

use function assert;
use function count;
use function spl_object_id;

class DatabaseThreadPool
{

    private SleeperHandlerEntry $sleeperHandlerEntry;
    /** @var array<int, MysqliThread|SQlite3Thread> */
    private array $workers = [];

    public function __construct() {
        $this->sleeperHandlerEntry = Server::getInstance()->getTickSleeper()->addNotifier(function (): void {
            foreach ($this->workers as $thread) {
                $this->collectThread($thread);
            }
        });
    }

    public function getSleeperEntry(): SleeperHandlerEntry {
        return $this->sleeperHandlerEntry;
    }


    public function addWorker(Thread $thread): void {
        $this->workers[spl_object_id($thread)] = $thread;
    }

    public function start(): void {
        if (count($this->workers) === 0) {
            throw new UnderflowException("Cannot start an empty pool of workers");
        }
        foreach ($this->workers as $thread) {
            $thread->start();
        }
    }

    public function getLeastBusyWorker(): DatabaseThread {
        $best = null;
        $best_score = INF;
        foreach ($this->workers as $thread) {
            $score = $thread->busy_score;
            if ($score < $best_score) {
                $best_score = $score;
                $best = $thread;
                if ($score === 0) {
                    break;
                }
            }
        }
        assert($best !== null);
        return $best;
    }

    private function collectThread(DatabaseThread $thread): void {
        $thread->collectActionResults();
    }

    public function triggerGarbageCollector(): void {
        foreach ($this->workers as $thread) {
            $thread->triggerGarbageCollector();
        }
    }

    public function shutdown(): void {
        foreach ($this->workers as $thread) {
            if ($thread->isInUse()) {
                Server::getInstance()->getLogger()->info("Waiting for database threads to execute final queries");
            }
            while ($thread->isInUse()) {
            }
            $thread->stop();
            $thread->join();
        }
        $this->workers = [];
    }
}