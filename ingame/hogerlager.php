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

$hlNumber = '';

// How much you can win
$winMoney = $userData['hlround'] * (2.6 * 500);
$tpl->assign('winMoney', $winMoney);

// How much it costs
$costMoney = $userData['hlround'] * (2.5 * 500);
$tpl->assign('costMoney', $costMoney);

$error = array();
$form_error = '';

if (isset($_GET['number'])) {
    $hlNumber = (int) $_GET['number'];
} else {
    if ($_SERVER['REQUEST_METHOD'] == "POST") { }
    else { $hlNumber = rand(1, 100); }
}

// To get the answer to the user
if (isset($_GET['result'])) {
    if ($_GET['result'] == 'won') {
        $tpl->assign('success', 'Je hebt correct gekozen! Je bent door naar de volgende ronde!');
    } else {
        $tpl->assign('form_error', 'Helaas je hebt niet gewonnen! Volgende keer beter?');
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['hl']) || empty($_POST['hl'])) {
        $error[] = 'Geen hoger of lager antwoord gevonden';
    }
    
    if ($userData['cash'] < 500) {
        $error[] = 'Je hebt niet genoeg cash om te spelen!';
    }
    
    if ($hlNumber == '') {
        $error[] = 'Er ging iets fout...';
    }
    
    if (count($error) > 0) {
        foreach ($error as $item) {
            $form_error .= '- ' . $item . '<br />';
        }
        $tpl->assign('form_error', $form_error);
    } else {
        // We got higher or lower! :-)
        $hluserAnswer = $_POST['hl'];
        $hlNumerWin = rand(1, 100);
      
        if ($hlNumber > $hlNumerWin) {
            $hlcompAnswer = 1;
        } elseif ($hlNumber < $hlNumerWin) {
            $hlcompAnswer = 2;
        } else {
            $hlcompAnswer = $hluserAnswer;
        }
        
        $dbCon->beginTransaction();
        
        try {
            // User won
            if ($hluserAnswer == $hlcompAnswer) {
                $stmt = $dbCon->prepare('UPDATE users SET cash = cash - :costMoney WHERE id = :id');
                $stmt->execute(['costMoney' => $costMoney, 'id' => $userData['id']]);
                
                $stmt = $dbCon->prepare('UPDATE users SET cash = cash + :winMoney, hlround = hlround + 1 WHERE id = :id');
                $stmt->execute(['winMoney' => $winMoney, 'id' => $userData['id']]);
                
                $dbCon->commit();
                header('Location: ' . ROOT_URL . 'ingame/hogerlager.php?result=won');
                exit;
            }
            // User lost
            else {
                $stmt = $dbCon->prepare('UPDATE users SET cash = cash - :costMoney WHERE id = :id');
                $stmt->execute(['costMoney' => $costMoney, 'id' => $userData['id']]);
                
                $dbCon->commit();
                header('Location: ' . ROOT_URL . 'ingame/hogerlager.php?result=lost');
                exit;
            }
        } catch (Exception $e) {
            $dbCon->rollBack();
            $tpl->assign('form_error', 'Er ging iets mis. Probeer het opnieuw.');
        }
    }
}

$tpl->assign('hlNumber', $hlNumber);
$tpl->display('ingame/hogerlager.tpl');
?>
