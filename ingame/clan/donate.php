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

// Check if user is in a clan, if not redirect
if ($userData['clan_id'] == 0) { 
    header('Location: ' . ROOT_URL . 'ingame/clan/index.php'); 
    exit();
}

$error = array();
$form_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['amount'])) {
        $error[] = 'Er is geen bedrag ingevuld!';
    } elseif (!is_numeric($_POST['amount'])) {
        $error[] = 'Het bedrag wat is ingegeven is niet numeriek!';
    } else {
        $stmt = $pdo->prepare('SELECT cash FROM users WHERE id = :id');
        $stmt->execute(['id' => $userData['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($_POST['amount'] > $result['cash']) {
            $error[] = 'Het ingegeven bedrag heb je niet in cash!';
        }
    }

    if (count($error) > 0) {
        foreach ($error as $item) {
            $form_error .= '- ' . $item . '<br />';
        }
        $tpl->assign('form_error', $form_error);
    } else {
        // User can donate
        $amount = (int) $_POST['amount'];

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE users SET cash = cash - :amount WHERE id = :id');
            $stmt->execute([
                'amount' => $amount,
                'id' => $userData['id']
            ]);

            $stmt = $pdo->prepare('UPDATE clans SET cash = cash + :amount WHERE clan_id = :clan_id');
            $stmt->execute([
                'amount' => $amount,
                'clan_id' => $userData['clan_id']
            ]);

            $pdo->commit();
            $tpl->assign('success', 'Je hebt succesvol gedoneerd naar je clan!');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $tpl->assign('form_error', 'Er is een fout opgetreden bij de donatie: ' . $e->getMessage());
        }
    }
}

$tpl->display('clan/donate.tpl');
