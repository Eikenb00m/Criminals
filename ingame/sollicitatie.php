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

$formError = '';
$error = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $requiredFields = [
        'function' => 'Je hebt geen functie aangegeven',
        'onlineHours' => 'Je hebt niet aangegeven hoeveel uur je online bent',
        'goodQuality' => 'Je hebt je goede eigenschappen niet aangegeven',
        'badQuality' => 'Je hebt je slechte eigenschappen niet aangegeven',
        'reason' => 'Je hebt geen reden waarom je teamlid wilt worden aangegeven'
    ];

    foreach ($requiredFields as $field => $errorMsg) {
        if (empty($_POST[$field])) {
            $error[] = $errorMsg;
        }
    }

    if (count($error) > 0) {
        foreach ($error as $item) {
            $formError .= '- ' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '<br />';
        }
        $tpl->assign('form_error', $formError);
    } else {

        // Prevent email injection
        foreach ($_POST as $data => $post) {
            $_POST[$post] = str_replace(["\r", "\n", "%0a", "%0d"], '', stripslashes($data));
        }

        $to = ROOT_EMAIL;
        $subject = 'Sollicitatie';
        $message = htmlspecialchars($_POST['login'], ENT_QUOTES, 'UTF-8') . ' heeft gesolliciteerd via uw criminals.\n\n '
                   . 'Zijn e-mail adres is:\n ' . htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8') . ' \n\n Hij zou graag de functie hebben van:\n '
                   . htmlspecialchars($_POST['function'], ENT_QUOTES, 'UTF-8') . '\n\n Hij is daarvoor zoveel uur per dag beschikbaar: \n '
                   . htmlspecialchars($_POST['onlineHours'], ENT_QUOTES, 'UTF-8') . '\n\n Zijn goede eigenschappen zijn:\n '
                   . htmlspecialchars($_POST['goodQuality'], ENT_QUOTES, 'UTF-8') . '\n\n En zijn slechte eigenschappen zijn:\n '
                   . htmlspecialchars($_POST['badQuality'], ENT_QUOTES, 'UTF-8') . '\n\n Hij wil deze functie om deze reden:\n '
                   . htmlspecialchars($_POST['reason'], ENT_QUOTES, 'UTF-8');

        $headers = 'From: noreply@noreply.nl' . "\r\n" .
                   'Reply-To: noreply@noreply.nl' . "\r\n" .
                   'Return-Path: noreply@noreply.nl' . "\r\n";

        if (mail($to, $subject, $message, $headers)) {
            $tpl->assign('success', 'Je hebt succesvol gesolliciteerd, het team neemt zo spoedig mogelijk contact met je op!');
        } else {
            $tpl->assign('form_error', 'Er is een technische storing ontstaan, je sollicitatie is niet verstuurd!');
        }
    }
}

$tpl->display('ingame/sollicitatie.tpl');
