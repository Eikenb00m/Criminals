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

$form_error = '';
$error = [];
$nameArray = ['', 'steen', 'papier', 'schaar'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['choice'])) {
        $error[] = 'Er is geen steen, papier of schaar opgegeven';
    } elseif ($_POST['choice'] < 1 || $_POST['choice'] > 3) {
        $error[] = 'Er is niet gekozen tussen steen, papier of schaar.';
    }

    if (count($error) > 0) {
        foreach ($error as $item) {
            $form_error .= '- ' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '<br />';
        }
        $tpl->assign('form_error', $form_error);
    } else {
        $userChoice = (int) $_POST['choice'];
        $compChoice = rand(1, 3);

        // User won
        if (($userChoice == 1 && $compChoice == 3) || ($userChoice == 2 && $compChoice == 1) || ($userChoice == 3 && $compChoice == 2)) {
            $stmt = $dbCon->prepare('UPDATE users SET cash = cash + 500 WHERE id = :id');
            $stmt->execute([':id' => $userData['id']]);
            $tpl->assign('success', 'Je hebt gewonnen! Jij had ' . $nameArray[$userChoice] . ' gekozen en de computer ' . $nameArray[$compChoice]. '! Je wint 500 cash!');
        }
        // Computer won
        elseif (($userChoice == 3 && $compChoice == 1) || ($userChoice == 1 && $compChoice == 2) || ($userChoice == 2 && $compChoice == 3)) {
            $stmt = $dbCon->prepare('UPDATE users SET cash = cash - 500 WHERE id = :id');
            $stmt->execute([':id' => $userData['id']]);
            $tpl->assign('form_error', 'Je hebt verloren! Jij had ' . $nameArray[$userChoice] . ' gekozen en de computer ' . $nameArray[$compChoice]. '! Je verliest 500 cash!');
        } else {
            // Nobody won
            $tpl->assign('form_error', 'Je wint en verliest niet je had hetzelfde als de computer! Jullie hadden beiden ' . $nameArray[$userChoice] . '!');
        }    
    }
}

$tpl->display('ingame/sps.tpl');
