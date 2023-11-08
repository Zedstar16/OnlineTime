<?php

namespace Zedstar16\OnlineTime\session;

class Session
{


    private int $startTimestamp;

    private int $lastActiveTimestamp;

    private float $duration;

    public function __construct(){
        $this->startTimestamp = time();
        $this->lastActiveTimestamp = time();
        $this->duration = 0;
    }

    public function setLastActive() : void{
        $this->lastActiveTimestamp = time();
    }

    public function getLastActiveTimestamp() : int{
        return $this->lastActiveTimestamp;
    }

    public function getStartTimestamp() : int{
        return $this->startTimestamp;
    }

    public function incrementDuration(float $amount) : void{
        $this->duration += $amount;
    }
    public function getDuration() : int{
        return (int)$this->duration;
    }


}