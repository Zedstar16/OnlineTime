<?php

namespace Zedstar16\OnlineTime\tasks;

use pocketmine\scheduler\Task;
use Zedstar16\OnlineTime\Loader;
use Zedstar16\OnlineTime\OnlineTime;

class IncrementDurationTask extends Task
{
    private float $lastTickTimestamp;

    public function __construct(){
        $this->lastTickTimestamp = microtime(true);
    }

    public function onRun(): void {
        $sessions = OnlineTime::getInstance()->getAllSessions();
        // Purpose of this is to compensate for servers that may be running below 20tps
        $toAdd = microtime(true) - $this->lastTickTimestamp;
        foreach ($sessions as $playerSession){
            $inactivityTimeout = Loader::getCfg()->get("inactivity-timeout", 60);
            $isActive = (time() - $playerSession->getLastActiveTimestamp()) <= $inactivityTimeout;
            if($isActive){
                $playerSession->incrementDuration($toAdd);
            }
        }
        $this->lastTickTimestamp = microtime(true);
    }
}