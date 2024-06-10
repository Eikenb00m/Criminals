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

// Check if user is logged in, if not redirect
if (LOGGEDIN === false) { 
    header('Location: ' . ROOT_URL . 'index.php'); 
    exit();
}

// Check if user has admin privileges
if ($userData['level'] < 10) { 
    header('Location: ' . ROOT_URL . '/ingame/index.php'); 
    exit();
}

$error = array();
$form_error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Admin wants to add a new admin
    if (empty($_POST['user'])) {
        $error[] = 'Je hebt geen speler ingevoerd.';
    } else {
        $stmt = $pdo->prepare('SELECT username, level, id FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $_POST['user']]);
        if ($stmt->rowCount() < 1) {
            $error[] = 'De ingegeven gebruiker bestaat niet!';
        }
    }
    
    if (empty($_POST['level'])) {
        $error[] = 'Je hebt geen niveau aangegeven!';
    } else {
        if ($_POST['level'] < 0 || $_POST['level'] > 10) {
            $error[] = 'Het level wat je hebt aangegeven is niet correct!';
        }
    }
    
    if ($_POST['user'] == $userData['username']) {
        $error[] = 'Je kan jezelf niet aanpassen!';
    }
    
    if (count($error) > 0) {
        foreach ($error as $item) {
            $form_error .= '- ' . $item . '<br />';
        }
        $tpl->assign('form_error', $form_error);
    } else {
        $level = ($_POST['level'] == 'cnul') ? 0 : $_POST['level'];
        
        $stmt = $pdo->prepare('UPDATE users SET level = :level WHERE username = :username');
        $stmt->execute(['level' => $level, 'username' => $_POST['user']]);
        
        $tpl->assign('success', 'Je hebt succesvol de toegang van ' . $_POST['user'] . ' aangepast naar niveau ' . $_POST['level'] . '!');
    }
}

$tpl->display('admin/adminMaak.tpl');
