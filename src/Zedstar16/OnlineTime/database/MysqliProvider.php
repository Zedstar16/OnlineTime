<?php

namespace Zedstar16\OnlineTime\database;

use DateTime;
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


}