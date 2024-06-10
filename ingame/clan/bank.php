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

$error = array();
$form_error = '';

// Check if user is logged in, if not redirect
if (LOGGEDIN === false) {
    header('Location: ' . ROOT_URL . 'index.php');
    exit();
}

// Check if user is in a clan, if not redirect
if ($userData['clan_id'] == 0) {
    header('Location: ' . ROOT_URL . 'ingame/clan/index.php');
    exit();
}

// Check if user has clan access to this page, if not redirect
if ($userData['clan_level'] < 6) {
    header('Location: ' . ROOT_URL . 'ingame/clan/index.php');
    exit();
}

// Check if user has submitted the form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if amount is entered and if it is valid
    if (empty($_POST['amount'])) {
        $error[] = 'Er is geen bedrag ingevuld!';
    } elseif (!is_numeric($_POST['amount'])) {
        $error[] = 'Het bedrag wat is ingegeven is niet numeriek!';
    } else {
        // Fetch clan data
        $stmt = $pdo->prepare('SELECT bank, cash, bank_left FROM clans WHERE clan_id = :clan_id');
        $stmt->execute(['clan_id' => $userData['clan_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $error[] = 'Kan clan gegevens niet ophalen!';
        } else {
            // Clan member wants to deposit money
            if (isset($_POST['deposit'])) {
                if ($_POST['amount'] > $result['cash']) {
                    $error[] = 'Zoveel heeft de clan niet in cash!';
                }

                // Check if clan hasn't deposited enough times for one day
                if ($result['bank_left'] < 1) {
                    $error[] = 'Je kan vandaag geen stortingen meer naar de clan bank doen!';
                }
            }
            // Clan member wants to withdraw money
            else {
                if ($_POST['amount'] > $result['bank']) {
                    $error[] = 'Zoveel heeft de clan niet op de bank staan!';
                }
            }
        }
    }

    // Check for errors and show them if there are any
    if (count($error) > 0) {
        foreach ($error as $item) {
            $form_error .= '- ' . $item . '<br />';
        }
        $tpl->assign('form_error', $form_error);
    } else {
        // Clan member may deposit / withdraw
        if (isset($_POST['deposit'])) {
            // Clan member is going to deposit money
            $stmt = $pdo->prepare('UPDATE clans SET cash = cash - :amount, bank = bank + :amount, bank_left = bank_left - 1 WHERE clan_id = :clan_id');
            $stmt->execute([
                'amount' => (int)$_POST['amount'],
                'clan_id' => $userData['clan_id']
            ]);

            $tpl->assign('success', 'Je hebt succesvol geld op de clan bank gezet!');
        } else {
            // Clan member is going to withdraw money
            $stmt = $pdo->prepare('UPDATE clans SET cash = cash + :amount, bank = bank - :amount WHERE clan_id = :clan_id');
            $stmt->execute([
                'amount' => (int)$_POST['amount'],
                'clan_id' => $userData['clan_id']
            ]);

            $tpl->assign('success', 'Je hebt succesvol geld van de clan bank gehaald!');
        }
    }
}

// Give general information
$stmt = $pdo->prepare('SELECT cash, bank, bank_left FROM clans WHERE clan_id = :clan_id');
$stmt->execute(['clan_id' => $userData['clan_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$tpl->assign('cash', $result['cash']);
$tpl->assign('bank', $result['bank']);
$tpl->assign('deposit_left', $result['bank_left']);

// Display page
$tpl->display('clan/bank.tpl');
