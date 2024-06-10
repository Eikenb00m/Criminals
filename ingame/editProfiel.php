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

// Check if user is not logged in, if so, redirect to index page
if (LOGGEDIN == FALSE) { header('Location: ' . ROOT_URL . 'index.php'); exit; }

$error = array();
$form_error = '';
$website = '';
$userInfo = '';
$password = $userData['password'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // User wants to change their password
    if (isset($_POST['passVerify']) && !empty($_POST['passVerify'])) {
        if (isset($_POST['pass']) && !empty($_POST['pass'])) {
            if ($_POST['pass'] !== $_POST['passVerify']) {
                $error[] = 'Wachtwoorden komen niet overeen!';
            } else {
                // Create safe hash for user password with blowfish algorithm
                $userSalt = strtr(base64_encode(random_bytes(16)), '+', '.');
                $userSalt = sprintf("$2y$%02d$", 10) . $userSalt;
                $password = crypt($_POST['pass'], $userSalt);
            }
        } else {
            $error[] = 'Je hebt geen wachtwoord ingevoerd!';
        }
    }
    
    // Website check
    if (isset($_POST['website']) && !empty($_POST['website'])) {
        if (!preg_match("/\b(?:(?:https?):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $_POST['website'])) {
            $error[] = 'De ingevoerde website heeft een incorrect formaat!';
        } else {
            $website = $_POST['website'];
        }
    }
    
    // User info check
    if (isset($_POST['info']) && !empty($_POST['info'])) {
        $userInfo = $_POST['info'];
    }
    
    if (count($error) > 0) {
        foreach ($error as $item) {
            $form_error .= '- ' . $item . '<br />';
        }
        $tpl->assign('form_error', $form_error);
    } else {
        $stmt = $dbCon->prepare('UPDATE users SET website = :website, info = :info, password = :password WHERE id = :id');
        $stmt->execute([
            'website' => $website,
            'info' => $userInfo,
            'password' => $password,
            'id' => $userData['id']
        ]);

        $tpl->assign('success', 'Je gegevens zijn succesvol geüpdatet!');
        $tpl->assign('website', $website);
        $tpl->assign('info', $userInfo);
    }
}

$tpl->display('ingame/editProfiel.tpl');
?>
