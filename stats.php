<?php
/*
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>. 
 */

require_once('init.php');

// Get best players
try {
    $bestPlayers = array();
    $stmt = $pdo->query('SELECT username FROM users ORDER BY attack_power DESC LIMIT 5');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $bestPlayers[] = $row['username'];
    }
    $tpl->assign('bestPlayers', $bestPlayers);

    // Get best clans
    $bestClans = array();
    $stmt = $pdo->query('SELECT clan_name FROM clans ORDER BY clan_clicks DESC LIMIT 5');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $bestClans[] = $row['clan_name'];
    }
    $tpl->assign('bestClans', $bestClans);

    // Get newest members
    $newestMembers = array();
    $stmt = $pdo->query('SELECT username FROM users WHERE activated = 1 ORDER BY id DESC LIMIT 5');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $newestMembers[] = $row['username'];
    }
    $tpl->assign('newestMembers', $newestMembers);

    // Get most clicks
    $mostClicks = array();
    $stmt = $pdo->query('SELECT username FROM users ORDER BY clicks DESC LIMIT 5');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mostClicks[] = $row['username'];
    }
    $tpl->assign('mostClicks', $mostClicks);

    // Get member count by type
    $memberCount = array();
    $stmt = $pdo->query('SELECT COUNT(*) as aantal, type FROM users GROUP BY type');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $memberCount[$row['type']] = $row['aantal'];
    }
    $tpl->assign('memberCount', $memberCount);

} catch (PDOException $e) {
    $tpl->assign('error', 'Database error: ' . $e->getMessage());
}

$tpl->display('stats.tpl');
