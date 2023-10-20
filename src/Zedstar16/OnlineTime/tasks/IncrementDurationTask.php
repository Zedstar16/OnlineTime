<?php

namespace Zedstar16\OnlineTime\tasks;

use pocketmine\scheduler\Task;
use Zedstar16\OnlineTime\Loader;
use Zedstar16\OnlineTime\OnlineTime;

class IncrementDurationTask extends Task
{

    public function onRun(): void {
        $sessions = OnlineTime::getInstance()->getAllSessions();
        foreach ($sessions as $playerSession){
            $inactivityTimeout = Loader::getCfg()->get("inactivity-timeout", 60);
            $isActive = (time() - $playerSession->getLastActiveTimestamp()) <= $inactivityTimeout;
            if($isActive){
                $playerSession->incrementDuration();
            }
        }
    }
}