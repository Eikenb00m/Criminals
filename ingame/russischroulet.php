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

require_once('../init.php');

// Check if user is logged in, if not redirect to login page
if (!LOGGEDIN) {
    header('Location: ' . ROOT_URL . 'index.php');
    exit;
}

$winningMoney = 500;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Chance variable
    $shot = (int) rand(0, 1);

    if ($shot === 1) {
        // User won
        $stmt = $dbCon->prepare('UPDATE users SET cash = cash + :winningMoney WHERE session_id = :session_id');
        $stmt->execute([
            ':winningMoney' => $winningMoney,
            ':session_id' => $userData['session_id']
        ]);
        $tpl->assign('success', 'Je haalt de trekker over en de magnum klikt, je hebt het overleeft en wint ' . $winningMoney . '!');
    } else {
        // User did not win
        $stmt = $dbCon->prepare('UPDATE users SET cash = cash - :losingMoney WHERE session_id = :session_id');
        $stmt->execute([
            ':losingMoney' => $winningMoney,
            ':session_id' => $userData['session_id']
        ]);
        $tpl->assign('form_error', 'Je haalt de trekker over en voor dat je het weet schiet de kogel dwars door je hoofd! Je hebt ' . $winningMoney . ' verloren!');
    }
}

$tpl->display('ingame/russischroulet.tpl');
