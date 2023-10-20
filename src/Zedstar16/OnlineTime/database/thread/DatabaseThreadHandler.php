<?php

declare(strict_types=1);

namespace Zedstar16\OnlineTime\database\thread;

use pocketmine\Server;
use Zedstar16\OnlineTime\Loader;
use Zedstar16\OnlineTime\tasks\ThreadGCCollectorTask;

class DatabaseThreadHandler
{

    private static DatabaseThreadPool $DBThreadPool;

    /** @var array<int, callable> */
    private static array $callbacks = [];

    public static function initialize(): void {
        self::$DBThreadPool = new DatabaseThreadPool();
        $dbConfig = Loader::getCfg()->get("database");
        $dbProvider = $dbConfig["provider"];
        $dbInfo = $dbConfig[$dbProvider];
        $logger = Server::getInstance()->getLogger();
        if ($dbProvider === "mysql") {
            for ($i = 1; $i <= $dbInfo["db-thread-count"]; $i++) {
                $thread = new MysqliThread($i, self::$DBThreadPool->getSleeperEntry(), $dbInfo["host"], $dbInfo["user"], $dbInfo["password"], $dbInfo["database"], $logger);
                self::$DBThreadPool->addWorker($thread);
            }
        } else {
            $sqlite_db_path = Loader::getInstance()->getDataFolder() . $dbInfo["db-name"];
            if(!file_exists($sqlite_db_path)){
                Server::getInstance()->getLogger()->critical("Database type is set to SQlite3 and database file does not exist at: $sqlite_db_path");
                Server::getInstance()->getPluginManager()->disablePlugin(Loader::getInstance());
                return;
            }
            self::$DBThreadPool->addWorker(new SQlite3Thread(0, self::$DBThreadPool->getSleeperEntry(), Loader::getInstance()->getDataFolder() . $dbInfo["db-name"], $logger));
        }
        self::$DBThreadPool->start();
        Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new ThreadGCCollectorTask(), 20 * 60 * 30);
    }


    public static function add($query, ?callable $callable = null, $queryType = DatabaseThread::TYPE_QUERY_SINGLE): void {
        $requestID = mt_rand(0, 999999999);
        if ($callable !== null) {
            self::$callbacks[$requestID] = $callable;
        }
        $input = json_encode([
            "requestID" => $requestID,
            "query" => $query,
            "queryType" => $queryType
        ]);
        self::$DBThreadPool->getLeastBusyWorker()->queue($input);
    }

    public static function sendResult($result): void {
        $result = json_decode($result, true);
        $callback = self::$callbacks[$result["requestID"]] ?? null;
        if ($callback !== null) {
            $callback($result["result"]);
        }
    }

    public static function triggerGarbageCollector(): void {
        self::$DBThreadPool->triggerGarbageCollector();
    }

    public static function shutdown(): void {
        if (isset(self::$DBThreadPool)) {
            self::$DBThreadPool->shutdown();
        }
    }
}