<?php

namespace Zedstar16\OnlineTime\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\Server;
use Zedstar16\OnlineTime\OnlineTime;

class OnlineTimeCommand extends Command
{

    const LINE_SEPARATOR = "-----------------------------------";
    
    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        $this->setPermission("onlinetime.command");
        parent::__construct($name, $description, $usageMessage, $aliases);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if (!isset($args[0])) {
            $sender->sendMessage(implode("\n", [
                "  §l§8»§r §g§lOnline Time Usage§r§l§8 «",
                "§l§8»§r§f /ot §g(username)",
                "§l§8»§r§f /ot §gtop [7d/30d/45d/90d/all]",
                "§l§8»§r§f /ot §g(username) §eFrom:§g(dd/mm/yyyy) §eTo:§g(dd/mm/yyyy)"
            ]));
            return;
        }
        $provider = OnlineTime::getInstance()->getProvider();
        if ($args[0] === "top") {
            $lookbackPeriod = -1;
            if (isset($args[1])) {
                $period = str_replace("d", "", $args[1]);
                if ($args[1] === "all"){
                    $lookbackPeriod = -1;
                } elseif (is_numeric($period)) {
                    $lookbackPeriod = (int)$period;
                }else{
                    $sender->sendMessage("§l§8»§r §cYou must provide a number of days, ie 7d or 90d or 365d etc OR just \"all\"");
                    return;
                }
            }
            $provider->getTopTimes($lookbackPeriod, function ($result) use ($sender, $args) {
                $sender->sendMessage("  §7» §g§lTop Online Times " . (($args[1] ?? null) !== null ? "§7(§f$args[1]§7) " : "") . "§fLeaderboard§r§7 «");
                $sender->sendMessage(str_repeat("-", 35));
                foreach ($result as $index => $playerOt) {
                    $position = $index + 1;
                    $timeComponents = OnlineTime::getInstance()->calcTimeComponents($playerOt["total_duration"]);
                    $sender->sendMessage(" §l§8»§r §e$position. §g$playerOt[username] §f$timeComponents[0]§7hrs §f$timeComponents[1]§7mins §7");
                }
                $sender->sendMessage(str_repeat("-", 35));
            });
        } else {
            $target = $args[0];
            if(!OnlineTime::getInstance()->validateUsername($target)){
                $sender->sendMessage("§l§8»§r §cInvalid username characters provided");
                return;
            }
            if (!isset($args[1])) {
                $p = Server::getInstance()->getPlayerExact($target);
                $sessionDuration = 0;
                if ($p !== null) {
                    $sessionDuration = OnlineTime::getInstance()->getSession($p)->getDuration();
                    $target = $p->getName();
                }
                $provider->getRecentTime($target, function ($result) use ($sender, $target, $sessionDuration) {
                    if(($result["duration_total"] ?? null) === null){
                        if($sessionDuration > 0){
                            $sender->sendMessage("§l§8»§r §7It is §f$target's§7 first time on the server, onlinetime is yet to be saved");
                            return;
                        }
                        $sender->sendMessage("§l§8»§r §7Player §f{$target} §7does not exist in database");
                        return;
                    }
                    $sender->sendMessage("  §l§8»§r §g§lOnline Time for §f{$target}§r§l§8 «");
                    $sender->sendMessage(self::LINE_SEPARATOR);
                    foreach ($result as $index => $playerOt) {
                        $index_name = [
                            "duration_7d" => "Last 7d",
                            "duration_30d" => "Last 30d",
                            "duration_total" => "All Time"
                        ];
                        $timeComponents = OnlineTime::getInstance()->calcTimeComponents($playerOt + $sessionDuration);
                        $sender->sendMessage(" §l§8»§r §g$index_name[$index]: §f$timeComponents[0]§7hrs §f$timeComponents[1]§7mins §7");
                    }
                    $sender->sendMessage(self::LINE_SEPARATOR);
                });
            } else {
                $specificUsage = [
                    "§eUsage examples of Specific lookup:",
                    "§gDate format should be dd/mm/yyyy",
                    "",
                    "§gOT between 1st July 2023 to 21st August 2023",
                    "§f- §e/ot username 1/7/2023 21/8/2023",
                    "",
                    "§gOT between 24th December 2022 to 8th March 2023",
                    "§f- §e/ot username 24/12/2022 8/3/2023"
                ];
                $specificUsage = implode("\n", $specificUsage);
                if (count($args) !== 3) {
                    $sender->sendMessage($specificUsage);
                    return;
                }
                $from = strtotime(str_replace("/", "-", $args[1]));
                $to = strtotime(str_replace("/", "-", $args[2]));
                if (!$to || !$from) {
                    $sender->sendMessage($specificUsage);
                    return;
                }
                if(($targetPlayer = Server::getInstance()->getPlayerExact($target)) !== null){
                    $target = $targetPlayer->getName();
                }
                $provider->getTimeBetween($target, $from, $to, function ($result) use($sender, $target, $to, $from) {
                    $toDate = date("jS F Y", $to);
                    $fromDate = date("jS F Y", $from);
                    if (($result["total_duration"] ?? null) === null) {
                        $sender->sendMessage("§l§8»§r §7$target §chas no onlinetime between $fromDate to $toDate");
                        return;
                    }
                    $sender->sendMessage("  §l§8»§r §g§lOnline Time for §f{$target}§r§l§8 «");
                    $sender->sendMessage(self::LINE_SEPARATOR);
                    $timeComponents = OnlineTime::getInstance()->calcTimeComponents($result["total_duration"]);
                    $sender->sendMessage(" §l§8»§r §gFrom: §f$fromDate");
                    $sender->sendMessage(" §l§8»§r §gTo: §f$toDate");
                    $sender->sendMessage(" §l§8»§r §gTime: §f$timeComponents[0]§7hrs §f$timeComponents[1]§7mins §7");
                    $sender->sendMessage(self::LINE_SEPARATOR);
                });
            }
        }
    }

}