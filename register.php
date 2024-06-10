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
    } else {
        $stmt = $pdo->prepare('SELECT username FROM users WHERE username = :username');
        $stmt->execute(['username' => $_POST['login']]);
        if ($stmt->rowCount() > 0) {
            $formError[] = 'Gebruikersnaam is al in gebruik';
        }
    }

    if (empty($_POST['password'])) {
        $formError[] = 'Wachtwoord is niet ingevuld.';
    }

    if (empty($_POST['passconfirm'])) {
        $formError[] = 'Controle wachtwoord is niet ingevuld.';
    }

    if (empty($_POST['emailCheck'])) {
        $formError[] = 'Email is niet ingevuld.';
    } elseif (!filter_var($_POST['emailCheck'], FILTER_VALIDATE_EMAIL)) {
        $formError[] = 'Email adres is niet volledig.';
    }

    if (count($formError) > 0) {
        foreach ($formError as $value) {
            $error .= '- ' . $value . '<br />';
        }
        $tpl->assign($_POST);
        $tpl->assign('form_error', $error);
    } else {
        // Create safe hash for user password with bcrypt algorithm
        $userHash = password_hash($_POST['password'], PASSWORD_BCRYPT);

        $stmt = $pdo->prepare('INSERT INTO users (username, password, email, type, activated) VALUES (:username, :password, :email, :type, :activated)');
        $stmt->execute([
            'username' => $_POST['login'],
            'password' => $userHash,
            'email' => $_POST['emailCheck'],
            'type' => $_POST['type'],
            'activated' => 1
        ]);

        if ($stmt->rowCount() > 0) {
            $tpl->assign('REGISTERED', 'U bent succesvol geregistreerd, je kan nu inloggen!');
        } else {
            $sysError[] = 'Query failed...';
        }
    }
}

$tpl->display('register.tpl');
