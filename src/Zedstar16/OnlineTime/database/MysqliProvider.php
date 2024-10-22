<?php

namespace Zedstar16\OnlineTime\database;

use Zedstar16\OnlineTime\database\thread\DatabaseThreadHandler;

class MysqliProvider extends ProviderInterface
{
    public function initTables(): void {
        DatabaseThreadHandler::add("
            CREATE TABLE IF NOT EXISTS XuidRelation(
                xuid VARCHAR(16) NOT NULL UNIQUE,
                username VARCHAR(16),
                PRIMARY KEY (xuid)
            );                                       
        ");
        DatabaseThreadHandler::add("
            CREATE TABLE IF NOT EXISTS PlayerSessions(
                xuid VARCHAR(16) NOT NULL,
                session_start_time int,
                session_duration int,
                FOREIGN KEY (xuid) REFERENCES XuidRelation(xuid)
            );                                       
        ");
    }

    /**
     * Registers player if they do not exist in db and updates username if changed
     */
    public function register(string $xuid, string $username): void {
        $username = strtolower($username);
        DatabaseThreadHandler::add("SELECT username from XuidRelation where xuid = '$xuid'", function ($result) use ($xuid, $username) {
            if ((($result["username"] ?? null) === null) || $result["username"] !== $username) {
                $sql = <<<SQL
                    INSERT INTO XuidRelation (xuid, username) 
                    VALUES ('$xuid', '$username') 
                    ON DUPLICATE KEY UPDATE username = VALUES(username);
                SQL;
                DatabaseThreadHandler::add($sql);
            }
        });
    }


}