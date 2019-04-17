<?php

declare(strict_types=1);

namespace Zedstar16\OnlineTime;

use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener {

    public static $times = [];
    public $players;

	public function onEnable() : void{
	    $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->players = new Config($this->getDataFolder()."players.yml", Config::YAML);
    }

    public function onJoin(PlayerJoinEvent $event){
	    $pn = strtolower($event->getPlayer()->getName());
	    self::$times[$pn] = time();
    }

    public function onQuit(PlayerQuitEvent $event){
        $player = strtolower($event->getPlayer()->getName());
        if(isset(self::$times[$player])){
            $old = $this->players->get($player);
            $this->players->set($player, ($old + (time() - self::$times[$player])));
            unset(self::$times[$player]);
        }
    }

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool
    {
        if ($command->getName() == "onlinetime") {
           // if (!$sender instanceof Player && !isset($args[1]) && isset($args[0]) && strtolower($args[0]) !== "top") {
           //     $sender->sendMessage("You can only get the online time of other players, not yourself");
            //    return false;
           // }

            if (isset($args[0])) {
                $h=base64_decode("wqdkPS09LT3Cp2FPbmxpbmXCp2JUaW1lIEhlbHDCp2Q9LT0tPQrCp2Ivb3QgdG9wIFtwYWdlXSAgwqdhVmlldyB0aGUgdG9wIG1vc3QgYWN0aXZlIHBsYXllcnMKwqdiL290IHRvdGFsIFtwbGF5ZXJdICDCp2FWaWV3IGhvdyBsb25nIHlvdSBvciB0aGUgcGxheWVyIHlvdSBzZWxlY3RlZCBoYXZlIHNwZW50IG9ubGluZSBpbiB0b3RhbArCp2Ivb3Qgc2Vzc2lvbiBbcGxheWVyXSAgwqdhVmlldyBob3cgbG9uZyB5b3Ugb3IgdGhlIHBsYXllciB5b3Ugc2VsZWN0ZWQgaGF2ZSBzcGVudCBvbmxpbmUKwqdiL290IGluZm8gIMKnYVZpZXcgcGx1Z2luIHZlcnNpb24gYW5kIGNyZWRpdHMKCSAgICA=");
                switch ($args[0]) {
                    case "total":
                        if (!isset($args[1])) {
                            $sender->sendMessage("§aYour total online time is: §b" . $this->getTotalTime($sender->getName()));
                        } else if (isset($args[1])) {
                            strtolower($args[1]);
                            if ($this->getServer()->getPlayer($args[1]) !== null) {
                                $name = $this->getServer()->getPlayer($args[1])->getName();
                                $time = explode(":", $this->getTotalTime($name));
                                $sender->sendMessage("§aThe total online time of $name is: §b" .$time[0]."§9hrs §b".$time[1]."§9mins §b".$time[2]."§9secs");
                            } else {
                                if ($this->players->exists($args[1])) {
                                    $time = explode(":", $this->getTotalTime($args[1]));
                                    $sender->sendMessage("§aThe total online time of $args[1] is: §b" .$time[0]."§9hrs §b".$time[1]."§9mins §b".$time[2]."§9secs");
                                } else $sender->sendMessage("§cPlayer not found in database");
                            }
                        }
                        break;case "info":$sender->sendMessage("§aOnline§bTime\n§dVersion: §1.0\n§cMade By: §aZedstar16, §bTwitter: §e@Zedstar1603");break;
                        case "session":
                        if (!isset($args[1])) {
                            $sender->sendMessage("§aYour current session time is: §b" . $this->getSessionTime($sender->getName()));
                        } else if (isset($args[1])) {
                            if ($this->getServer()->getPlayer($args[1]) !== null) {
                                $name = $this->getServer()->getPlayer($args[1])->getName();
                                $sender->sendMessage("§aThe current session time of $name is: §b" . $this->getSessionTime($name));
                            } else {
                                $sender->sendMessage("§c$args[1] is not online");
                            }
                        }
                        break;
                    case "top":
                        $page = 1;
                        $data = $this->players->getAll();
                        arsort($data);
                        $i = 0;
                        $pagelength = 10;
                        $n = count($data);
                        $pages = round($n/$pagelength);
                        if(isset($args[1]) && is_numeric($args[1])){
                            if($args[1] > $n){
                                $sender->sendMessage("§cPage number is too large, max page number: $n");
                                return false;
                            }
                            $page = $args[1];
                            }
                        $sender->sendMessage("§bTop §aOnline §bTimes");
                        $sender->sendMessage("§6Displaying page §b".($page)."§6 out of §b$pages");
                        foreach($data as $key => $val){
                           $i++;
                           if($i >= $pagelength*($page - 1) && $i <= (($pagelength*($page - 1)) + 10)){
                               $time = explode(":", $this->getTotalTime($key));
                               $sender->sendMessage("§l§4$i.  §a$key §b". $time[0]."§9hrs §b".$time[1]."§9mins §b".$time[2]."§9secs");
                           }
                        }
                        break;
                    case "reset":
                        if($sender->hasPermission("reset.onlinetime")){
                            if(isset($args[1])){
                                if($args[1] == "all"){
                                    unlink($this->getDataFolder()."players.yml");
                                    $this->players->reload();
                                    $sender->sendMessage("Reset All online times");
                                }
                            }
                        }
                        break;
                    default:
                        $sender->sendMessage($h);
                        return true;
                }
            }
        }
        return true;
    }


	public function getTotalTime(String $pn): String {
        $pn = strtolower($pn);
        $totalsecs = $this->players->get($pn);
        if($this->getServer()->getPlayer($pn) !== null) {
            $t = (time() - self::$times[$pn]);
        }else $t = 0;
        $t = ($t + $totalsecs);
        return ($t < 0 ? '-' : '') . sprintf("%02d%s%02d%s%02d", floor(abs($t)/3600), ":", (abs($t)/60)%60, ":", abs($t)%60);
    }

    public function getSessionTime(String $pn) : String{
        $pn = strtolower($pn);
	    $t = time() - self::$times[$pn];
	    return ($t < 0 ? '-' : '') . sprintf("%02d%s%02d%s%02d", floor(abs($t)/3600), ":", (abs($t)/60)%60, ":", abs($t)%60);
    }
	public function onDisable() : void{
		foreach(self::$times as $player => $time){
		    $player = strtolower($player);
		    $old = $this->players->get($player);
		    $this->players->set($player, ($old + ( time() - self::$times[$player])));
            $this->players->save();
		    unset(self::$times[$player]);
        }
	}
}
