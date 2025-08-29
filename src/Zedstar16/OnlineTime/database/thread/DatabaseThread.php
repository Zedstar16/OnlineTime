<?php

declare(strict_types=1);

namespace Zedstar16\OnlineTime\database\thread;

use pmmp\thread\Thread as ThreadAlias;
use pmmp\thread\ThreadSafeArray;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\log\ThreadSafeLogger;
use pocketmine\thread\Thread;
use Zedstar16\OnlineTime\database\thread\message\ThreadMessage;
use function igbinary_serialize;
use function igbinary_unserialize;

abstract class DatabaseThread extends Thread
{

    public int $busy_score = 0;
    protected int $inUse = 0;

    protected ThreadSafeArray $actionQueue;
    protected ThreadSafeArray $actionResults;
    protected ThreadSafeLogger $logger;
    protected bool $running;


    protected SleeperHandlerEntry $sleeperHandlerEntry;

    protected int $threadID;

    public const TYPE_QUERY_SINGLE = 0;
    public const TYPE_QUERY_ALL = 1;
    public const TYPE_EXEC = 2;

    public function __construct(int $threadID, SleeperHandlerEntry $sleeperHandlerEntry, ThreadSafeLogger $logger) {
        $this->threadID = $threadID;
        $this->actionQueue = new ThreadSafeArray();
        $this->actionResults = new ThreadSafeArray();
        $this->sleeperHandlerEntry = $sleeperHandlerEntry;
        $this->logger = $logger;
    }

    public function getDBThreadID(): int {
        return $this->threadID;
    }

    public function isInUse(): bool {
        return $this->inUse === 1;
    }

    public function start(int $options = ThreadAlias::INHERIT_NONE): bool {
        $this->running = true;
        return parent::start($options);
    }

    public function sleep(): void {
        $this->synchronized(function (): void {
            if ($this->running) {
                $this->wait();
            }
        });
    }

    public function stop(): void {
        $this->running = false;
        $this->synchronized(function (): void {
            $this->notify();
        });
    }

    public function queue(ThreadMessage $message): void {
        $this->synchronized(function () use ($message): void {
            $this->actionQueue[] = igbinary_serialize($message);
            ++$this->busy_score;
            $this->notifyOne();
        });
    }

    public function triggerGarbageCollector(): void {
        $this->synchronized(function (): void {
            $this->actionQueue[] = igbinary_serialize(new ThreadMessage("", ThreadMessage::TYPE_GC_COLLECT));
            $this->notifyOne();
        });
    }

    abstract public function onRun(): void;

    public function collectActionResults(): void {
        while (($result = $this->actionResults->shift()) !== null) {
            DatabaseThreadHandler::sendResult(igbinary_unserialize($result));
            --$this->busy_score;
        }
    }
}