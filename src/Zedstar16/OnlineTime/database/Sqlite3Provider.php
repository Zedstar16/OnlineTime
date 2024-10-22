<?php

namespace Zedstar16\OnlineTime\database;

use Zedstar16\OnlineTime\database\thread\DatabaseThreadHandler;

class Sqlite3Provider extends ProviderInterface
{
    public function initTables(): void {
        DatabaseThreadHandler::add("
            CREATE TABLE IF NOT EXISTS XuidRelation(
                xuid VARCHAR(16) NOT NULL UNIQUE PRIMARY KEY,
                username VARCHAR(16)
            );                                      
        ");
        DatabaseThreadHandler::add("
            CREATE TABLE IF NOT EXISTS PlayerSessions(
                xuid VARCHAR(16) NOT NULL,
                session_start_time INT,
                session_duration INT
            );                         
        ");
    }

    /**
     * Registers player if they do not exist in db and updates username if changed
     */
    public function register(string $xuid, string $username): void {
        $username = strtolower($username);
        DatabaseThreadHandler::add("SELECT username from XuidRelation where xuid = '$xuid'", function ($result) use ($xuid, $username) {
            if (($result["username"] ?? null) === null) {
                DatabaseThreadHandler::add("INSERT OR IGNORE INTO XuidRelation VALUES('$xuid', '$username')");
                return;
            }
            if ($result["username"] !== $username) {
                DatabaseThreadHandler::add("UPDATE XuidRelation SET username = '$username' where xuid = '$xuid'");
            }
        });
    }

}