<?php

namespace Zedstar16\OnlineTime\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class OnlineTimeAdminCommand extends Command
{

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        $usage = "/ota leaderboard set (7d / 30d / all) - Set a leaderboard of top 7d or 30d etc
            /ota leaderboard set (7d,30d / 7d,30d,all,90d) - Set a rotating leaderboard displaying top 7d then 30d etc
            /ota leaderboard list - List all leaderboards
            /ota leaderboard remove (ID) - Remove a leaderboard
            /ota reset (username) - Reset a user's online time data";
        if (!isset($args[0])) {
            $sender->sendMessage($usage);
            return;
        }

        if ($args[0] === "leaderboard") {
            if(!isset($args[1])){
                $sender->sendMessage($usage);
            }
            if($args[1] === "set"){
                if(!isset($args[2])){
                    $sender->sendMessage($usage);
                }
            }
        }
    }
}