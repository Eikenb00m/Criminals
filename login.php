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

$formError = array();
$error = '';

// Check if user is logged in, if so no need to be here...
if (LOGGEDIN === true) { 
    header('Location: ' . ROOT_URL . 'ingame/index.php'); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['login'])) {
        $formError[] = 'Gebruikersnaam is niet ingevuld.';
    }

    if (empty($_POST['password'])) {
        $formError[] = 'Wachtwoord is niet ingevuld.';
    }

    if (!empty($_POST['login']) && !empty($_POST['password'])) {
        $stmt = $pdo->prepare('SELECT username, password FROM users WHERE username = :username');
        $stmt->execute(['username' => $_POST['login']]);
        $fetch = $stmt->fetch();

        if (!$fetch) {
            $formError[] = 'Gebruikersnaam of wachtwoord is incorrect.';
        } else {
            // check if user hash is correct
            if (!hash_equals($fetch['password'], crypt($_POST['password'], $fetch['password']))) {
                $formError[] = 'Gebruikersnaam of wachtwoord is incorrect.';
            }
        }
    }

    if (count($formError) > 0) {
        foreach ($formError as $key => $value) {
            $error .= '- ' . $value . '<br />';
        }
        $tpl->assign($_POST);
        $tpl->assign('form_error', $error);
    } else {
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $sessionId = '';

        for ($i = 0; $i < 20; $i++) {
            $sessionId .= $characters[rand(0, strlen($characters) - 1)];
        }

        $sessionId = sha1($sessionId);
        setcookie('game_session_id', $sessionId, time() + 86400, '/');

        $stmt = $pdo->prepare('UPDATE users SET session_id = :session_id WHERE username = :username');
        $stmt->execute(['session_id' => $sessionId, 'username' => $_POST['login']]);

        if ($stmt->rowCount() > 0) {
            $tpl->assign('LOGIN', 'U bent succesvol ingelogd.');
        } else {
            $sysError[] = 'Query failed...';
        }
    }
}

$tpl->display('login.tpl');
