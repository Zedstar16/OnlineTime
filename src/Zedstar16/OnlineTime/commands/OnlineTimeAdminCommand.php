<?php

namespace Zedstar16\OnlineTime\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use Zedstar16\OnlineTime\Loader;
use Zedstar16\OnlineTime\OnlineTime;

class OnlineTimeAdminCommand extends Command
{

    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        $this->setPermission("onlinetime.admin");
        parent::__construct($name, $description, $usageMessage, $aliases);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        $usage = implode("\n", [
            "  §l§8»§r §g§lOnline Time §cAdmin §gUsage§r§l§8 «",
            "§l§8»§r§f /ota §glb set §e(7d / 30d / all) §7- Set a static leaderboard of top 7d or 30d etc",
            "§l§8»§r§f /ota §glb set §e(7d,30d / 7d,30d,all,90d) §7- Set a rotating leaderboard displaying top 7d then 30d etc",
            "§l§8»§r§f /ota §glb remove §e(ID) §7- Remove a leaderboard",
            "§l§8»§r§f /ota §glb list §7- List all leaderboards",
            "§l§8»§r§f /ota §greset §e(username) §7- Reset a user's online time data",
        ]);
        if (!isset($args[0])) {
            $sender->sendMessage($usage);
            return;
        }
        /** @var Player $sender */
        $cfg = Loader::getInstance()->getLeaderboardCfg();
        if ($args[0] === "lb") {
            if (!isset($args[1])) {
                $sender->sendMessage($usage);
                return;
            }
            if ($args[1] === "set") {
                if (!isset($args[2])) {
                    $sender->sendMessage($usage);
                    return;
                }
                $type = $args[2];
                $finalType = [];
                $exp = explode(",", $type);
                $rotating = false;
                if (count($exp) > 1) {
                    $rotating = true;
                    foreach ($exp as $value) {
                        $processedValue = $this->processTypeValue($value);
                        if ($processedValue === null) {
                            $examples = [
                                "§f- §e7d,30d,all",
                                "§f- §e90d,all",
                                "§f- §e7d,30d,60d",
                            ];
                            $sender->sendMessage("§cInvalid leaderboard type value: §f\"$value\" §cencountered when trying to create §erotating §cleaderboard\n§gExamples:" . implode("\n", $examples));
                            return;
                        }
                        $finalType[] = $processedValue;
                    }
                } else {
                    $value = $this->processTypeValue($type);
                    if ($value === null) {
                        $sender->sendMessage("§cInvalid leaderboard type value, accepted values for static leaderboard are only days or \"all\"\n§gExamples: §g30d or 90d or all ");
                        return;
                    }
                    $finalType[] = $value;
                }
                $finalType = implode(",", $finalType);

                /** @var Player $sender */
                $pos = $sender->getPosition();
                $lbCfg = [
                    "x" => $pos->getFloorX(),
                    "y" => $pos->getFloorY(),
                    "z" => $pos->getFloorZ(),
                    "world" => $pos->getWorld()->getDisplayName(),
                    "type" => $finalType,
                    "cx" => $pos->getFloorX() >> 4,
                    "cz" => $pos->getFloorZ() >> 4
                ];
                $cfg[] = $lbCfg;
                Loader::getInstance()->setLeaderboardCfg($cfg);
                Loader::getLeaderboardManager()->spawnLeaderboard($lbCfg);
                Loader::getLeaderboardManager()->updateLeaderboards();
                if ($rotating) {
                    $sender->sendMessage("§gNew §eRotating §gleaderboard created at your position");
                    $sender->sendMessage("§4Rotations: §c" . $args[2]);
                } else {
                    $sender->sendMessage("§gNew §eStatic §f$args[2] §gleaderboard created at your position");
                }
            } elseif ($args[1] === "list") {
                $msg = "";
                foreach ($cfg as $index => $lbCfg) {
                    $world = $sender->getPosition()->getWorld();
                    $worldName = $world->getDisplayName();
                    $msg .= "§gID: §e$index §4Type: §c$lbCfg[type] §2World: §a$worldName, §2(§aX:$lbCfg[x], Y:$lbCfg[y], Z:$lbCfg[z]§2)";
                    if ($worldName === $lbCfg["world"]) {
                        $lbPos = new Position($lbCfg["x"], $lbCfg["y"], $lbCfg["z"], $world);
                        $dist = (int)$lbPos->distance($sender->getPosition());
                        $msg .= " §7[{$dist}m away]";
                    }
                    $msg .= "\n";
                }
                $sender->sendMessage(strlen($msg) > 0 ? $msg : "§cNo leaderboards exist yet");
            } elseif ($args[1] === "remove") {
                if (!isset($args[2])) {
                    $sender->sendMessage("§cYou must specify a leaderboard ID to remove");
                    return;
                }
                $id = (int)$args[2];
                if (isset($cfg[$id])) {
                    $sender->sendMessage("§cRemoved LB with §gID: §e$id");
                    Loader::getLeaderboardManager()->removeEntity(serialize($cfg[$id]));
                    unset($cfg[$id]);
                    Loader::getInstance()->setLeaderboardCfg($cfg);
                } else $sender->sendMessage("§cLeaderboard with ID §f$id §cdoes not exist");
            }else{
                $sender->sendMessage("§cInvalid leaderboard subcommand given");
            }
        }elseif($args[0] === "reset"){
            $target = $args[1] ?? null;
            if(!isset($target)){
                $sender->sendMessage("§cYou must provide a username to reset onlinetime for");
                return;
            }
            if(!OnlineTime::getInstance()->validateUsername($target)){
                $sender->sendMessage("§cInvalid username characters provided");
                return;
            }
            if(Server::getInstance()->getPlayerExact($target) !== null){
                $sender->sendMessage("§cYou cannot reset a user's onlinetime while they are online");
                return;
            }
            OnlineTime::getInstance()->getProvider()->reset($target, function ($result) use($sender, $target){
                $sender->sendMessage("§aSuccessfully removed any existing records for §f$target");
            });
        }
    }

    private function processTypeValue(string $value): ?string {
        $trimmed = trim($value, "d");
        if (is_numeric($trimmed) || $trimmed === "all") {
            return $trimmed;
        }
        return null;
    }
}