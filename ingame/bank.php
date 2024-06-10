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
    if (!isset($_POST['money'])) {
        $error[] = 'Je hebt geen bedrag aangegeven';
    } elseif (!ctype_digit($_POST['money'])) {
        $error[] = 'Het ingegeven bedrag is niet numeriek.';
    } elseif (strlen($_POST['money']) > 20) {
        $error[] = 'Het bedrag wat je hebt ingevoerd is abnormaal hoog!';
    }
    
    if (!isset($_POST['withdraw']) && !isset($_POST['deposit'])) {
        $error[] = 'Je hebt niet aangegeven of je geld wilt opnemen of storten!';
    } else {
        $stmt = $dbCon->prepare('SELECT bank, cash FROM users WHERE session_id = :session_id');
        $stmt->execute(['session_id' => $userData['session_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (isset($_POST['withdraw'])) {
            if ($row['bank'] < $_POST['money']) {
                $error[] = 'Je hebt niet genoeg op de bank staan!';
            }
        } else {
            if ($row['cash'] < $_POST['money']) {
                $error[] = 'Het bedrag wat je hebt aangegeven heb je niet in cash.';
            }
        }

        if ($userData['bank_left'] < 1) {
            $error[] = 'Je kan vandaag geen stortingen meer maken!';
        }
    }
    
    if (count($error) > 0) {
        foreach ($error as $item) {
            $form_error .= '- ' . $item . '<br />';
        }
        $tpl->assign('form_error', $form_error);
    } else {
        // We got a kom, now check if he won something...
        $money = (int) $_POST['money'];
        $WithOrDep = isset($_POST['withdraw']) ? 1 : 2;
        
        // User wants to withdraw money
        if ($WithOrDep == 1) {
            $stmt = $dbCon->prepare('UPDATE users SET cash = cash + :money, bank = bank - :money, bank_left = bank_left - 1 WHERE session_id = :session_id');
        } else {
            $stmt = $dbCon->prepare('UPDATE users SET cash = cash - :money, bank = bank + :money, bank_left = bank_left - 1 WHERE session_id = :session_id');
        }
        $stmt->execute(['money' => $money, 'session_id' => $userData['session_id']]);
        
        $tpl->assign('success', 'Je hebt geld ' . ($WithOrDep == 1 ? 'opgenomen' : 'gestort') . '!');
    }
}

$tpl->display('ingame/bank.tpl');
?>
