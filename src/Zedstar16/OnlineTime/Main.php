<?php

declare(strict_types=1);

namespace Zedstar16\OnlineTime;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use Zedstar16\OnlineTime\database\SQLite;

class Main extends PluginBase implements Listener
{
    public static $times = [];
    /** @var SQLite */
    public $db;

    public function onEnable(): void
    {
        $this->db = new SQLite($this);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onJoin(PlayerJoinEvent $event)
    {
        if ($this->db->hasTime($event->getPlayer()) === false) {
            $this->db->registerTime($event->getPlayer());
        }
        $pn = strtolower($event->getPlayer()->getName());
        self::$times[$pn] = time();
    }

    public function onQuit(PlayerQuitEvent $event)
    {
        $player = strtolower($event->getPlayer()->getName());
        $p = $event->getPlayer();
        if (isset(self::$times[$player])) {
            $old = $this->db->getRawTime($p);
            $this->db->setRawTime($p, ($old + (time() - self::$times[$player])));
            unset(self::$times[$player]);
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if ($command->getName() == "onlinetime") {
            if (!$sender instanceof Player && !isset($args[1]) && isset($args[0]) && strtolower($args[0]) == "session") {
                $sender->sendMessage("You can only get the online time of other players, not yourself");
                return false;
            }
            $h = base64_decode("wqdkPS09LT3Cp2FPbmxpbmXCp2JUaW1lIEhlbHDCp2Q9LT0tPQrCp2Ivb3QgdG9wIFtwYWdlXSAgwqdhVmlldyB0aGUgdG9wIG1vc3QgYWN0aXZlIHBsYXllcnMKwqdiL290IHRvdGFsIFtwbGF5ZXJdICDCp2FWaWV3IGhvdyBsb25nIHlvdSBvciB0aGUgcGxheWVyIHlvdSBzZWxlY3RlZCBoYXZlIHNwZW50IG9ubGluZSBpbiB0b3RhbArCp2Ivb3Qgc2Vzc2lvbiBbcGxheWVyXSAgwqdhVmlldyBob3cgbG9uZyB5b3Ugb3IgdGhlIHBsYXllciB5b3Ugc2VsZWN0ZWQgaGF2ZSBzcGVudCBvbmxpbmUKwqdiL290IGluZm8gIMKnYVZpZXcgcGx1Z2luIHZlcnNpb24gYW5kIGNyZWRpdHMKCSAgICA==");
            $c = base64_decode("wqdhT25saW5lwqdiVGltZQrCp2RWZXJzaW9uOiAxLjEKwqdjTWFkZSBCeTogwqdhWmVkc3RhcjE2LCDCp2JUd2l0dGVyOiDCp2VAWmVkc3RhcjE2MDM=");
            if (isset($args[0])) {
                switch ($args[0]) {
                    case "total":
                        if (!isset($args[1])) {
                            $time = explode(":", $this->getTotalTime($sender->getName()));
                            $sender->sendMessage("§aYour total online time is: §b" . $time[0] . "§9hrs §b" . $time[1] . "§9mins §b" . $time[2] . "§9secs");
                        } else if (isset($args[1])) {
                            strtolower($args[1]);
                            if ($this->getServer()->getPlayer($args[1]) !== null) {
                                $name = $this->getServer()->getPlayer($args[1])->getName();
                                $time = explode(":", $this->getTotalTime($name));
                                $sender->sendMessage("§aThe total online time of $name is: §b" . $time[0] . "§9hrs §b" . $time[1] . "§9mins §b" . $time[2] . "§9secs");
                            } else {
                                if ($this->db->hasTime($args[1])) {
                                    $time = explode(":", $this->getTotalTime($args[1]));
                                    $sender->sendMessage("§aThe total online time of $args[1] is: §b" . $time[0] . "§9hrs §b" . $time[1] . "§9mins §b" . $time[2] . "§9secs");
                                } else $sender->sendMessage("§cPlayer not found in database");
                            }
                        }break;case"info":$sender->sendMessage($c);break;
                    case "session":
                        if (!isset($args[1])) {
                            $time = explode(":", $this->getSessionTime($sender->getName()));
                            $sender->sendMessage("§aYour current session time is: §b" . $time[0] . "§9hrs §b" . $time[1] . "§9mins §b" . $time[2] . "§9secs");
                        } else if (isset($args[1])) {
                            if ($this->getServer()->getPlayer($args[1]) !== null) {
                                $name = $this->getServer()->getPlayer($args[1])->getName();
                                $time = explode(":", $this->getSessionTime($name));
                                $sender->sendMessage("§aThe current session time of $name is: §b" . $time[0] . "§9hrs §b" . $time[1] . "§9mins §b" . $time[2] . "§9secs");
                            } else {
                                $sender->sendMessage("§c$args[1] is not online");
                            }
                        }
                        break;
                    case "top":
                        $query = "SELECT username, time FROM players ORDER BY time;";
                        $result = $this->db->getDatabase()->query($query);
                        $place = 1;
                        $data = [];
                        $start = microtime(true);
                        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                            $data[$row["username"]] = $row["time"];
                            $place++;
                        }
                        arsort($data);

                        $i = 0;
                        $pagelength = 10;
                        $n = count($data);
                        $pages = round($n / $pagelength);
                        $page = 1;
                        if (isset($args[1]) && is_numeric($args[1])) {
                            if ($args[1] > ($n / $pagelength)) {
                                $sender->sendMessage("§cPage number is too large, max page number: $n");
                                return false;
                            }
                            $page = $args[1];
                        }
                        $sender->sendMessage("§bTop §aOnline §bTimes");
                        $sender->sendMessage("§6Displaying page §b" . ($page) . "§6 out of §b$pages");
                        foreach ($data as $key => $val) {
                            $i++;
                            if ($i >= $pagelength * ($page - 1) && $i <= (($pagelength * ($page - 1)) + 10)) {
                                $session = in_array($key, $this->getServer()->getOnlinePlayers()) ? self::$times[$key] : 0;

                                $formattedtime = $this->getFormattedTime(($val + $session));
                                $sender->sendMessage("§l§4$i.  §a$key §b" . $formattedtime);
                            }
                        }
                        break;
                    case "reset":
                        if ($sender->hasPermission("reset.onlinetime")) {
                            if (isset($args[1])) {
                                if ($args[1] == "all") {
                                    unlink($this->getDataFolder() . "players.db");
                                    $sender->sendMessage("Reset All online times");
                                }
                            }
                        }
                        break;
                    default:
                        $sender->sendMessage($h);
                        if ($sender->isOp()) {
                            $sender->sendMessage("§b/ot reset all  §aReset All Online Time data");
                        }
                        return true;
                }
            } else {
                $sender->sendMessage($h);
                if ($sender->isOp()) {
                    $sender->sendMessage("§b/ot reset all  §aReset All Online Time data");
                }
            }
        }
        return true;
    }

    public function getDatabase(): SQLite
    {
        return $this->db;
    }

    public function getFormattedTime($t)
    {
        $f = sprintf("%02d%s%02d%s%02d", floor(abs($t) / 3600), ":", (abs($t) / 60) % 60, ":", abs($t) % 60);
        $time = explode(":", $f);
        return $time[0] . "§9hrs §b" . $time[1] . "§9mins §b" . $time[2] . "§9secs";
    }

    public function getTotalTime($pn): String
    {
        $pn = "$pn";
        $pn = strtolower($pn);
        if ($this->getServer()->getPlayer($pn) !== null) {
            $p = $this->getServer()->getPlayer($pn);
        } else $p = $pn;
        $totalsecs = $this->db->getRawTime($p);
        if ($this->getServer()->getPlayer($pn) !== null) {
            $t = (time() - self::$times[$pn]);
        } else $t = 0;
        $t = ($t + $totalsecs);
        return ($t < 0 ? '-' : '') . sprintf("%02d%s%02d%s%02d", floor(abs($t) / 3600), ":", (abs($t) / 60) % 60, ":", abs($t) % 60);
    }

    public function getSessionTime($pn): String
    {
        $pn = "$pn";
        $pn = strtolower($pn);
        $t = time() - self::$times[$pn];
        return ($t < 0 ? '-' : '') . sprintf("%02d%s%02d%s%02d", floor(abs($t) / 3600), ":", (abs($t) / 60) % 60, ":", abs($t) % 60);
    }

    public function onDisable(): void
    {
        foreach (self::$times as $player => $time) {
            $player = "$player";
            $player = strtolower($player);
            if ($this->getServer()->getPlayer($player) !== null) {
                $p = $this->getServer()->getPlayer($player);
            } else $p = $player;
            $old = $this->db->getRawTime($p);
            $this->db->setRawTime($p, ($old + (time() - self::$times[$player])));
            unset(self::$times[$player]);
        }
    }
}
