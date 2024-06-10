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
    if (!isset($_POST['number'])) {
        $error[] = 'Nummer niet gevonden.';
    } elseif ($_POST['number'] < 0 || $_POST['number'] > 10) {
        $error[] = 'Nummer wat is ingegeven kan niet.';
    }
    
    if (!isset($_POST['gambleMoney'])) {
        $error[] = 'Je hebt geen inzet ingegeven.';
    } elseif (!ctype_digit($_POST['gambleMoney'])) {
        $error[] = 'Je inzet is niet numeriek.';
    } else {
        $stmt = $dbCon->prepare('SELECT cash FROM users WHERE session_id = :session_id');
        $stmt->execute(['session_id' => $userData['session_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row['cash'] < $_POST['gambleMoney']) {
            $error[] = 'Je inzet is hoger dan je nu in cash hebt.';
        }
    }
     
    if (count($error) > 0) {
        foreach ($error as $item) {
            $form_error .= '- ' . $item . '<br />';
        }
        $tpl->assign('form_error', $form_error);
    } else {
        // We got a number, now check if the user won something...
        $number = (int) $_POST['number'];
        $gambleMoney = (int) $_POST['gambleMoney'];
        $wonNumber = rand(0, 10);
        
        if ($number == $wonNumber) {
            $moneyWon = $gambleMoney * 8;
            $stmt = $dbCon->prepare('UPDATE users SET cash = cash + :money WHERE session_id = :session_id');
            $stmt->execute(['money' => $moneyWon, 'session_id' => $userData['session_id']]);
            
            $tpl->assign('success', 'Je hebt het juiste getal geraden! Je wint ' . $moneyWon . '!');
        } else {
            $stmt = $dbCon->prepare('UPDATE users SET cash = cash - :money WHERE session_id = :session_id');
            $stmt->execute(['money' => $gambleMoney, 'session_id' => $userData['session_id']]);
            $tpl->assign('form_error', 'Helaas... je hebt het juiste getal niet geraden... Je hebt ' . $gambleMoney . ' verloren!');
        }
    }
}

$tpl->display('ingame/getallenspel.tpl');
?>
