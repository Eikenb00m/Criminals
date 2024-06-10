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

// Check if user is logged in, if not, redirect to index page
if (LOGGEDIN == FALSE) { header('Location: ' . ROOT_URL . 'index.php'); exit; }

$error = array();
$form_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['donate'])) {
        $error[] = 'Je hebt niemand ingevuld waarna je wilt doneren.';
    } else {
        $stmt = $dbCon->prepare('SELECT username, protection FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $_POST['donate']]);
        $donateUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($stmt->rowCount() < 1) {
            $error[] = 'De speler naar wie je wilt doneren bestaat niet!';
        } elseif ($donateUser['protection'] == 1) {
            $error[] = 'De speler naar wie je wilt doneren staat nog onder bescherming!';
        }
    }
    
    if ($userData['protection'] == 1) {
        $error[] = 'Je kan niet doneren, je staat zelf nog onder protectie';
    }
    
    if (isset($donateUser) && $userData['username'] == $donateUser['username']) {
        $error[] = 'Je kan niet naar jezelf doneren!';
    }
    
    if (!isset($_POST['money'])) {
        $error[] = 'Je hebt geen donatie bedrag ingegeven.';
    } elseif (!ctype_digit($_POST['money'])) {
        $error[] = 'Je donatie bedrag is niet numeriek.';
    } else {
        $stmt = $dbCon->prepare('SELECT cash FROM users WHERE session_id = :session_id');
        $stmt->execute(['session_id' => $userData['session_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row['cash'] < $_POST['money']) {
            $error[] = 'Je donatie bedrag is hoger dan je nu in cash hebt.';
        }
    }
     
    if (count($error) > 0) {
        foreach ($error as $item) {
            $form_error .= '- ' . $item . '<br />';
        }
        $tpl->assign('form_error', $form_error);
    } else {
        // The user can donate, and the user who's getting donated also exists... just do the donate...
        $DonateMoney = (int) $_POST['money'];
        
        $stmt = $dbCon->prepare('UPDATE users SET cash = cash - :money WHERE session_id = :session_id');
        $stmt->execute(['money' => $DonateMoney, 'session_id' => $userData['session_id']]);
        
        $stmt = $dbCon->prepare('UPDATE users SET cash = cash + :money WHERE username = :username');
        $stmt->execute(['money' => $DonateMoney, 'username' => $donateUser['username']]);

        $tpl->assign('success', 'Je hebt je donatie verstuurd!');
    }
}

// Check if username is already filled
if (isset($_GET['donateTo'])) {
    $tpl->assign('donateUser', $_GET['donateTo']);
}

$tpl->display('ingame/doneren.tpl');
