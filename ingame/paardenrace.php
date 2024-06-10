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

// Initialiseer de databaseverbinding
$dbCon = getPDOConnection();

// Check if user is logged in, if not, redirect to index page
if (LOGGEDIN == FALSE) { header('Location: ' . ROOT_URL . 'index.php'); exit; }

$error = array();
$form_error = '';
$updateTicket = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['betHorse']) && !empty($_POST['betHorse'])) {
        if ($_POST['betHorse'] < 1 || $_POST['betHorse'] > 50) {
            $error[] = 'Het paard waarop je wilt wedden bestaat niet!';
        }
    } else {
        $error[] = 'Je hebt geen paard aangegeven waarop je wilt wedden!';
    }

    if (isset($_POST['ticket']) && !empty($_POST['ticket'])) {
        if ($_POST['ticket'] < 1 || $_POST['ticket'] > 3) {
            $error[] = 'Het ticket dat je wilt kopen bestaat niet!';
        }

        $ticketCosts = 250 * pow(2, $_POST['ticket'] - 1);
        if ($userData['cash'] < $ticketCosts) {
            $error[] = 'Je hebt niet genoeg cash voor het ticket!';
        }
    } else {
        $error[] = 'Je hebt geen ticket aangegeven!';
    }

    // Check if user already bought a ticket, if so he can only buy a higher ticket
    $stmt = $dbCon->prepare('SELECT * FROM temp WHERE userid = :userid AND area = "horse"');
    $stmt->execute(['userid' => $userData['id']]);
    $fetch = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($fetch) {
        if ($fetch['extra'] == $_POST['ticket']) {
            $error[] = 'Je kan niet nogmaals hetzelfde ticket kopen!';
        }

        if ($fetch['extra'] > $_POST['ticket']) {
            $error[] = 'Je kan geen lager kostende ticket kopen dan dat je al hebt gekocht!';
        }

        $updateTicket = true;
    }

    if (count($error) > 0) {
        foreach ($error as $item) {
            $form_error .= '- ' . $item . '<br />';
        }
        $tpl->assign('form_error', $form_error);
    } else {
        if ($updateTicket) {
            $stmt = $dbCon->prepare('UPDATE temp SET extra = :ticket, variable = :betHorse WHERE userid = :userid AND area = "horse"');
            $stmt->execute(['ticket' => $_POST['ticket'], 'betHorse' => $_POST['betHorse'], 'userid' => $userData['id']]);
        } else {
            $stmt = $dbCon->prepare('INSERT INTO temp (userid, area, variable, extra) VALUES (:userid, "horse", :betHorse, :ticket)');
            $stmt->execute(['userid' => $userData['id'], 'betHorse' => $_POST['betHorse'], 'ticket' => $_POST['ticket']]);
        }

        $stmt = $dbCon->prepare('UPDATE users SET cash = cash - :ticketCosts WHERE id = :userid');
        $stmt->execute(['ticketCosts' => $ticketCosts, 'userid' => $userData['id']]);
        $tpl->assign('success', 'De weddenschap is voltooid. Je hebt gewed op paard nummer ' . $_POST['betHorse'] . '!');
    }
}

$tpl->display('ingame/paardenrace.tpl');
?>
