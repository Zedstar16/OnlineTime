<?php

namespace Zedstar16\OnlineTime;

use pocketmine\player\Player;
use Zedstar16\OnlineTime\database\MysqliProvider;
use Zedstar16\OnlineTime\database\ProviderInterface;
use Zedstar16\OnlineTime\database\Sqlite3Provider;
use Zedstar16\OnlineTime\database\thread\DatabaseThreadHandler;
use Zedstar16\OnlineTime\session\Session;

class OnlineTime
{

    /** @var ProviderInterface */
    private ProviderInterface $provider;

    /** @var array<string, Session> */
    private array $sessions;

    private static ?OnlineTime $instance;

    public function __construct() {
        $this->provider = (Loader::getCfg()->get("database")["provider"] === "mysql")
            ? new MysqliProvider()
            : new Sqlite3Provider();
        $this->sessions = [];
        $this->provider->initTables();
        self::$instance = $this;
    }

    /**
     * @param int $seconds
     * @return array<int, int> # [Hours, Minutes]
     */
    public function calcTimeComponents(int $seconds): array {
        $hrs = $seconds / 3600;
        $minutes = intval(($hrs - floor($hrs)) * 60);
        return [floor($hrs), $minutes];
    }

    public function insertplaceholderdata() {
        $now = time();
        $then = $now - (86400 * 30);
        $names = yaml_parse_file("names.yml");
        $z = 0;
        foreach ($names as $xuid => $name) {
            $z++;
            $q1 = "INSERT INTO XuidRelation values ('$xuid', '$name')";
            echo "[$z/500] REGISTERED IN DB [$q1]\n";
            DatabaseThreadHandler::add($q1, function ($result) use ($z, $q1) {
                echo "[" . ($result ? "T" : "F") . "] [$z/500] REGISTERED IN DB [$q1]\n";
            });
            $x = 0;
            for ($i = $then; $i < $now; $i += 86400) {
                $st = mt_rand($i, intval($i + (86400 / 2)));
                $dur = mt_rand(0, 45000);
                $x++;
                $q2 = "INSERT INTO PlayerSessions values ('$xuid', $st, $dur)";

                DatabaseThreadHandler::add($q2, function ($result) use ($x, $z, $q2) {
                    echo "[" . ($result ? "T" : "F") . "] [$z/500] [$x/30] INJECT [$q2]\n";
                });
            }
        }
    }

    public static function getInstance(): OnlineTime {
        return self::$instance;
    }

    public function addSession(Player $player): void {
        $this->sessions[$player->getName()] = new Session();
        $this->provider->register($player->getXuid(), $player->getName());
    }

    public function getSession(Player $player): ?Session {
        return $this->sessions[$player->getName()] ?? null;
    }

    public function getAllSessions(): array {
        return $this->sessions;
    }

    public function removeSession(Player $player): void {
        $session = $this->sessions[$player->getName()] ?? null;
        if (isset($session)) {
            $this->provider->addSessionRecord($player->getXuid(), $session->getStartTimestamp(), $session->getDuration());
            unset($session);
        }
    }

    public function getProvider(): ProviderInterface {
        return $this->provider;
    }


}