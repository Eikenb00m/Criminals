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
    // Update users
    $pdo->query('UPDATE users SET bank_left = (
                                    CASE 
                                        WHEN (attack_power + defence_power) < 5000 THEN 5
                                        WHEN (attack_power + defence_power) < 10000 THEN 4
                                        WHEN (attack_power + defence_power) > 10000 THEN 5
                                    END),
                                bank = (bank * 1.05), 
                                clicks_today = 0');

    // Update clans
    $pdo->query('UPDATE clans SET bank = (bank * 1.05), bankleft = 10, clicks_today = 0');

    // Update clicks
    $pdo->query('DELETE FROM clicks');
    
    echo "Cron job executed successfully.";
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo 'Internal server error';
}
