<?php

namespace Zedstar16\OnlineTime;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use Zedstar16\OnlineTime\commands\OnlineTimeCommand;
use Zedstar16\OnlineTime\database\thread\DatabaseThreadHandler;
use Zedstar16\OnlineTime\listener\EventListener;
use Zedstar16\OnlineTime\tasks\IncrementDurationTask;

class Loader extends PluginBase
{

    private static self $instance;
    private static ?Config $cfg;


    protected function onEnable(): void {
        self::$instance = $this;
        $this->saveResource("config.yml");
        self::$cfg = new Config($this->getDataFolder() . "config.yml");
        DatabaseThreadHandler::initialize();
        $ot = new OnlineTime();
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($ot), $this);
        $this->getServer()->getCommandMap()->register("ot", new OnlineTimeCommand("onlinetime", "View player online times", null, ["ot"]));
        $this->getScheduler()->scheduleRepeatingTask(new IncrementDurationTask(), 20);
    }

    public static function getInstance(): Loader {
        return self::$instance;
    }

    public static function getCfg(): ?Config {
        return self::$cfg;
    }

    protected function onDisable(): void {
        DatabaseThreadHandler::shutdown();
    }

}