<?php

namespace Zedstar16\OnlineTime\listener;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\VanillaItems;
use Zedstar16\OnlineTime\Loader;
use Zedstar16\OnlineTime\OnlineTime;

class EventListener implements Listener
{

    private OnlineTime $ot;

    public function __construct(OnlineTime $ot) {
        $this->ot = $ot;
    }

    public function onJoin(PlayerJoinEvent $event) {
        $this->ot->addSession($event->getPlayer());
    }

    public function onMove(PlayerMoveEvent $event) {
        $p = $event->getPlayer();
        $session = OnlineTime::getInstance()->getSession($p);
        if ($session !== null) {
            $to = $event->getTo();
            $from = $event->getFrom();
            // Check for both that player is moving AND turning around, prevents spinbot and most afk scripts, but easily bypass-able
            if (
                ((int)$to->getYaw() !== (int)$from->getYaw() || (int)$to->getPitch() !== (int)$from->getPitch())
                && ($to->getFloorX() !== $from->getFloorX() || $to->getFloorZ() !== $from->getFloorZ())
            ) {
                $session->setLastActive();
            }
        }
    }

    // Chatting in chat should be considered a form of activity even if not moving around
    public function onChat(PlayerChatEvent $event) {
        $p = $event->getPlayer();
        $session = OnlineTime::getInstance()->getSession($p);
        $session?->setLastActive();
    }

    public function onQuit(PlayerQuitEvent $event) {
        $this->ot->removeSession($event->getPlayer());
    }
}