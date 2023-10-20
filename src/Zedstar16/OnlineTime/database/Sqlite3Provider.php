<?php

namespace Zedstar16\OnlineTime\database;

use DateTime;
use Zedstar16\OnlineTime\database\thread\DatabaseThreadHandler;

class Sqlite3Provider extends ProviderInterface
{
    public function initTables(): void {
        DatabaseThreadHandler::add("
            CREATE TABLE IF NOT EXISTS XuidRelation(
                xuid TEXT NOT NULL UNIQUE PRIMARY KEY,
                username TEXT
            );                                      
        ");
        DatabaseThreadHandler::add("
            CREATE TABLE IF NOT EXISTS PlayerSessions(
                xuid TEXT NOT NULL,
                session_start_time INT,
                session_duration INT
            );                         
        ");
    }

}