<?php

namespace Zedstar16\OnlineTime;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use Zedstar16\OnlineTime\commands\OnlineTimeCommand;
use Zedstar16\OnlineTime\database\thread\DatabaseThreadHandler;
use Zedstar16\OnlineTime\leaderboard\LeaderboardManager;
use Zedstar16\OnlineTime\listener\EventListener;
use Zedstar16\OnlineTime\tasks\IncrementDurationTask;
use Zedstar16\OnlineTime\tasks\UpdateLeaderboardsTask;

class Loader extends PluginBase
{

    private static self $instance;
    private static ?Config $cfg;
    private static LeaderboardManager $leaderboardManager;
    private array $leaderboardConfig = [];

    protected function onEnable(): void {
        self::$instance = $this;
        $this->saveResource("config.yml");
        $this->saveResource("leaderboards.yml");
        self::$cfg = new Config($this->getDataFolder() . "config.yml");
        self::$leaderboardManager = new LeaderboardManager();
        $leaderboadConfigFilePath = $this->getDataFolder() . "leaderboards.yml";
        $this->leaderboardConfig = yaml_parse_file($leaderboadConfigFilePath);
        DatabaseThreadHandler::initialize();
        $ot = new OnlineTime();
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($ot), $this);
        $this->getServer()->getCommandMap()->register("ot", new OnlineTimeCommand("onlinetime", "View player online times", null, ["ot"]));
        $this->getScheduler()->scheduleRepeatingTask(new IncrementDurationTask(), 20);
        $this->getScheduler()->scheduleRepeatingTask(new UpdateLeaderboardsTask(), self::$cfg->get("leaderboard-update-interval", 60) * 20);
        self::$leaderboardManager->initialiseEntities();
    }

    public static function getInstance(): Loader {
        return self::$instance;
    }

    public static function getCfg(): ?Config {
        return self::$cfg;
    }

    public static function getLeaderboardManager(): LeaderboardManager {
        return self::$leaderboardManager;
    }

    public function getLeaderboardCfg(): array {
        return $this->leaderboardConfig;
    }

    public function setLeaderboardCfg(array $cfg): void {
        $this->leaderboardConfig = $cfg;
    }

    protected function onDisable(): void {
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            OnlineTime::getInstance()->removeSession($player);
        }
        DatabaseThreadHandler::shutdown();
        yaml_emit_file($this->getDataFolder() . "leaderboards.yml", $this->leaderboardConfig);
    }

}