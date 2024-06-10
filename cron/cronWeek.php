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
require_once('cron_config.php');

// Check if the script is accessed from an allowed IP
if ($_SERVER['REMOTE_ADDR'] !== ALLOWED_IP) {
    http_response_code(403);
    die('No direct access...');
}

try {
    // Delete old temp records
    $stmt = $pdo->prepare('DELETE FROM temp WHERE area NOT IN ("signup", "horse", "cooldown", "clan_join") AND (time + INTERVAL 7 DAY) < NOW()');
    $stmt->execute();

    // Delete inactive users
    $stmt = $pdo->prepare('DELETE FROM users WHERE activated = 0 AND (signup_date + INTERVAL 14 DAY) < NOW()');
    $stmt->execute();

    echo "Cron job executed successfully.";
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo 'Internal server error';
}
