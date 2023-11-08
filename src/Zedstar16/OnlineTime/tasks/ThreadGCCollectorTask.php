<?php

namespace Zedstar16\OnlineTime\tasks;

use pocketmine\scheduler\Task;
use Zedstar16\OnlineTime\database\thread\DatabaseThreadHandler;

class ThreadGCCollectorTask extends Task
{

    public function onRun(): void {
        DatabaseThreadHandler::triggerGarbageCollector();
    }
}