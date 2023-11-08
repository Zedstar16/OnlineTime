<?php

namespace Zedstar16\OnlineTime\tasks;

use pocketmine\scheduler\Task;
use Zedstar16\OnlineTime\Loader;

class UpdateLeaderboardsTask extends Task
{

    public function onRun(): void {
        Loader::getLeaderboardManager()->updateLeaderboards();
    }
}