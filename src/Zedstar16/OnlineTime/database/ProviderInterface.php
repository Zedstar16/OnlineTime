<?php

namespace Zedstar16\OnlineTime\database;

use Zedstar16\OnlineTime\database\thread\DatabaseThread;
use Zedstar16\OnlineTime\database\thread\DatabaseThreadHandler;

abstract class ProviderInterface
{

    abstract public function initTables(): void;

    public function addSessionRecord($xuid, int $startTime, int $sessionDuration): void {
        DatabaseThreadHandler::add("INSERT INTO PlayerSessions VALUES('$xuid', '$startTime', '$sessionDuration')");
    }

    /** Get OT for last 7d, 30d and Overall */
    public function getRecentTime(string $username, callable $callable): void {
        $timestamp7d = time() - (86400 * 7);
        $timestamp30d = time() - (86400 * 30);
        DatabaseThreadHandler::add("
            SELECT 
                SUM(CASE WHEN session_start_time > $timestamp7d THEN session_duration ELSE 0 END) AS duration_7d,
                SUM(CASE WHEN session_start_time > $timestamp30d THEN session_duration ELSE 0 END) AS duration_30d,
                SUM(session_duration) AS duration_total
            FROM PlayerSessions
            JOIN XuidRelation ON PlayerSessions.xuid = XuidRelation.xuid
            WHERE LOWER(XuidRelation.username) = LOWER('$username');
        ", $callable);
    }

    public function getTimeBetween(string $username, int $startTime, int $endTime, callable $callable): void {
        DatabaseThreadHandler::add("
            SELECT
                SUM(session_duration) AS total_duration
            FROM PlayerSessions
            JOIN XuidRelation ON PlayerSessions.xuid = XuidRelation.xuid
            WHERE session_start_time >= $startTime 
            AND session_start_time <= $endTime
            AND LOWER(XuidRelation.username) = LOWER('$username');   
        ", $callable);
    }

    public function getTopTimes(int $lookbackPeriod, callable $callable): void {
        if($lookbackPeriod !== -1){
            $lookbackPeriod = time() - (86400 * $lookbackPeriod);
        }
        $query =
            "
            SELECT XuidRelation.username, SUM(PlayerSessions.session_duration) AS total_duration
            FROM PlayerSessions 
            JOIN XuidRelation ON PlayerSessions.xuid = XuidRelation.xuid
            "
            . ($lookbackPeriod === -1 ? "" : "WHERE PlayerSessions.session_start_time >= $lookbackPeriod") .
            "
            GROUP BY XuidRelation.username
            ORDER BY total_duration DESC
            LIMIT 10; 
        ";
        DatabaseThreadHandler::add($query, $callable, DatabaseThread::TYPE_QUERY_ALL);
    }

    public function reset(string $username, callable $callable): void {
        DatabaseThreadHandler::add("
            DELETE PlayerSessions
            FROM PlayerSessions
            JOIN XuidRelation ON PlayerSessions.xuid = XuidRelation.xuid
            WHERE LOWER(XuidRelation.username) = LOWER('$username');
        ", $callable);
    }


}