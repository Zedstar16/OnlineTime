# OnlineTime 2.0
Measure how long players are spending on your server!
<br>Yes this update was delayed by 6 years, but it is now release-ready!<br>
[![](https://poggit.pmmp.io/shield.dl.total/OnlineTime)](https://poggit.pmmp.io/p/OnlineTime)
[![](https://poggit.pmmp.io/shield.state/OnlineTime)](https://poggit.pmmp.io/p/OnlineTime)
[![](https://poggit.pmmp.io/shield.api/OnlineTime)](https://poggit.pmmp.io/p/OnlineTime)

## Features

* MySQL and SQLite3 database support, designed for use on large networks. (Currently in use on zeqa.net)
* Tracks active playtime only with (movement + rotation or chat), AFK time is ignored.
* Add Floating text leaderboards with static or rotating display of time periods.
* Tracking of individual session times, allowing for advanced insights
* In-depth stats, retrieve time spent within last x days for a player & leaderboards for last x days for all players

## Commands

* `/ot <username>`
  Shows Last 7d, Last 30d, and All-time. If the player is online, current session time is included.
* `/ot top [<Nd>|all]`
  Top 10 by time. Examples: `7d`, `30d`, `90d`, `all`. Any positive day count accepted.
* `/ot <username> <dd/mm/yyyy> <dd/mm/yyyy>`
  Time for a user between the two dates (inclusive). Example:
  `/ot Zedstar16 1/7/2023 21/8/2023`

## Admin Commands

* `/ota lb set <period>`
  Create a static leaderboard at your position. `<period>` is `Nd` or `all`.
  Examples: `7d`, `30d`, `all`.
* `/ota lb set <period1,period2,...>`
  Create a rotating leaderboard. Examples: `7d,30d,all` or `90d,all`.
* `/ota lb list`
  List all leaderboards with IDs. Shows distance when you are in the same world.
* `/ota lb remove <ID>`
  Remove a leaderboard by ID.
* `/ota reset <username>`
  Delete that user’s stored records - Not possible if the user is online.

## Leaderboards

* Leaderboards are spawned at your position and saved in `leaderboards.yml`.
* Rotation advances on each `leaderboard-update-interval`.

## Install & Configuration
* Obtain compiled version from [Releases](https://github.com/Zedstar16/OnlineTime/releases/)
* After adding plugin to plugins folder, restart server
* config.yml located at plugin_data/OnlineTime/config.yml should be modified to suit your use-case


---
