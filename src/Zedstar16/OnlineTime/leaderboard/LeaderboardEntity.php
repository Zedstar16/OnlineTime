<?php

namespace Zedstar16\OnlineTime\leaderboard;

use pocketmine\entity\Location;
use pocketmine\entity\Zombie;
use pocketmine\nbt\tag\CompoundTag;

class LeaderboardEntity extends Zombie
{

    protected float $gravity = 0;
    protected bool $gravityEnabled = false;
    private string $type = "";
    private bool $isRotating = false;
    private array $rotationPeriods = [];
    private int $currentRotationIndex = 0;

    public function __construct(Location $location, ?CompoundTag $nbt = null) {
        parent::__construct($location, $nbt);
        $this->setMaxHealth(99999);
        $this->setHealth(99999);
        $this->setNoClientPredictions();
        $this->setHasGravity(false);
        $this->setScale(0.00001);
        $this->setNameTagVisible();
        $this->setNameTagAlwaysVisible();
        $this->setCanSaveWithChunk(false);
    }

    final public function getDrops(): array {
        return [];
    }

    final public function getXpDropAmount(): int {
        return 0;
    }

    public function setRotationPeriods(array $periods): void{
        $this->isRotating = true;
        $this->rotationPeriods = $periods;
    }

    public function getType(): string {
        if($this->isRotating){
            if($this->currentRotationIndex === count($this->rotationPeriods)){
                $this->currentRotationIndex = 0;
            }
            $index = $this->currentRotationIndex;
            $this->currentRotationIndex++;
            return $this->rotationPeriods[$index];
        }
        return $this->type;
    }

    public function setType(string $type): void{
        $this->type = $type;
    }

    public function setNameTag(string $name): void {
        parent::setNameTag($name);
       // $this->sendData($this->getWorld()->getPlayers());
    }

}