<?php

namespace Zedstar16\OnlineTime\leaderboard;

use pocketmine\entity\Location;
use pocketmine\Server;
use pocketmine\world\Position;
use Zedstar16\OnlineTime\Loader;
use Zedstar16\OnlineTime\OnlineTime;

class LeaderboardManager
{
    /** @var LeaderboardEntity[] */
    private array $entities = [];

    public function __construct() {
        $this->entities = [];
    }


    public function initialiseEntities(): void {
        foreach (Loader::getInstance()->getLeaderboardCfg() as $lb) {
            $this->spawnLeaderboard($lb);
        }
    }

    public function updateLeaderboards(): void {
        foreach ($this->entities as $entity) {
            if ($entity !== null && !$entity->isClosed()) {
                $type = $entity->getType();
                $lookbackPeriod = $type === "all" ? -1 : (int)$type;
                OnlineTime::getInstance()->getProvider()->getTopTimes($lookbackPeriod, function ($result) use ($type, $entity) {
                    $lines[] = "§g§lTop Online Times " . (($type !== "all") ? "§7(§f{$type}d§7) " : "") . "§fLeaderboard§r";
                    foreach ($result as $index => $playerOt) {
                        $position = $index + 1;
                        $timeComponents = OnlineTime::getInstance()->calcTimeComponents($playerOt["total_duration"]);
                        $lines[] = "§7$position. §g$playerOt[username] §f$timeComponents[0]§7hrs §f$timeComponents[1]§7mins §7";
                    }
                    $entity?->setNameTag(implode("\n", $lines));
                });
            }
        }
    }

    public function removeEntity(string $key): void {
        if (isset($this->entities[$key])) {
            $entity = $this->entities[$key];
            if(!$entity->isClosed() && !$entity->isFlaggedForDespawn()){
                $entity->flagForDespawn();
            }
            unset($this->entities[$key]);
        }
    }

    public function spawnLeaderboard(array $cfg): void {
        if (isset($this->entities[serialize($cfg)])) {
            return;
        }
        $position = new Position($cfg["x"], $cfg["y"], $cfg["z"], Server::getInstance()->getWorldManager()->getWorldByName($cfg["world"]));
        if ($position->world === null) {
            return;
        }
        if (!$position->world->isChunkLoaded($position->getFloorX() >> 4, $position->getFloorZ() >> 4)) {
            $position->world->loadChunk($position->getFloorX() >> 4, $position->getFloorZ() >> 4);
        }
        $type = $cfg["type"];
        $location = Location::fromObject($position, $position->world);
        $entity = new LeaderboardEntity($location, null);
        $entity->spawnToAll();
        $rotations = explode(",", $type);
        if ($rotations > 1) {
            $entity->setType($rotations[0]);
            $entity->setRotationPeriods($rotations);
        } else $entity->setType($type);
        $this->entities[serialize($cfg)] = $entity;
    }

}