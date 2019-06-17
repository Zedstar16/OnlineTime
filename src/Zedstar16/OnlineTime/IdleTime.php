<?php

/**
 * @author Bavfalcon9
 */

namespace Zedstar16\OnlineTime;

use pocketmine\scheduler\Task;
use Zedstar16\OnlineTime\Main;

class IdleTime extends Task {
    private $pl;

    public function __construct(Main $plugin) {
        $this->pl = $plugin;
    }

    public function onRun(int $tick) {
        $this->pl->moveTimes = $this->pl->moveTimes;
        foreach($this->pl->moveTimes as $player=>$time) {
            $this->pl->moveTimes[$player]['count']++; // add a second.

            if ($time['count'] >= 60) {
                if ($this->pl->moveTimes[$player]['afk'] === true) continue;
                $this->afk($player);
                $this->pl->moveTimes[$player]['afk'] = true;
            }

            if (isset($this->pl->moveTimes[$player]['checked'])) {
                $this->unafk($player);
                $this->pl->moveTimes[$player]['count'] = 0;
                $this->pl->moveTimes[$player]['afk'] = null;
                unset($this->pl->moveTimes[$player]['checked']);
            }
        }
    }

    private function afk(String $player) {
        $p = $this->pl->getServer()->getPlayer($player);
        $p->sendMessage('You are now afk');
        if (isset(Main::$times[$player])) {
            $old = $this->pl->db->getRawTime($p);
            $this->pl->db->setRawTime($p, ($old + (time() - Main::$times[$player])));
            unset(Main::$times[$player]);
        }
    }

    private function unafk(String $player) {
        $p = $this->pl->getServer()->getPlayer($player);
        $p->sendMessage('You are no longer afk');

        if ($this->pl->db->hasTime($p) === false) {
            $this->pl->db->registerTime($p);
        }
        $pn = strtolower($player);
        Main::$times[$pn] = time();
    }



}