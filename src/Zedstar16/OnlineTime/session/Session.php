<?php

namespace Zedstar16\OnlineTime\session;

use pocketmine\player\Player;
use Zedstar16\OnlineTime\database\thread\DatabaseThreadHandler;

class Session
{


    private int $start_timestamp;

    private int $last_active_timestamp;

    private int $duration;

    public function __construct(){
        $this->start_timestamp = time();
        $this->last_active_timestamp = time();
        $this->duration = 0;
    }

    public function setLastActive() : void{
        $this->last_active_timestamp = time();
    }

    public function getLastActiveTimestamp() : int{
        return $this->last_active_timestamp;
    }

    public function getStartTimestamp() : int{
        return $this->start_timestamp;
    }

    public function incrementDuration() : void{
        $this->duration += 10;
    }
    public function getDuration() : int{
        return $this->duration;
    }


}