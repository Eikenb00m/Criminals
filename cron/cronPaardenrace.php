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

$winningHorse = rand(1, 50);
$jackpot = 0;
$gamblers = 0;
$multiply = 0;

try {
    // Calculate total jackpot
    $stmt = $pdo->query('SELECT * FROM temp WHERE area = "horse"');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $jackpot += 20000;
        $gamblers++;
    }

    // Calculate gains and add them to the bank
    $stmt = $pdo->query('SELECT * FROM temp WHERE area = "horse"');
    $updateStmt = $pdo->prepare('UPDATE users SET bank = bank + :amount WHERE id = :user_id');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        switch ($row['extra']) {
            case 3:
                $multiply = 1;
                break;
            case 2:
                $multiply = 0.5;
                break;
            case 1:
                $multiply = 0.25;
                break;
            default:
                $multiply = 0;
        }

        $amount = floor($jackpot / $gamblers * (25 * pow(2, $multiply)));
        $updateStmt->execute([
            'amount' => $amount,
            'user_id' => $row['user_id']
        ]);
    }

    // Clear horse race bets
    $pdo->query('DELETE FROM temp WHERE area = "horse"');
    
    echo "Cron job executed successfully.";
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo 'Internal server error';
}
