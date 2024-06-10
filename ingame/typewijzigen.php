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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['type'])) {
        $error[] = 'Er is geen type opgegeven';
    } elseif ($_POST['type'] < 1 || $_POST['type'] > 3) {
        $error[] = 'Er is niet een correcte type opgegeven.';
    } elseif ($_POST['type'] == $userData['type']) {
        $error[] = 'Je kan niet naar het type veranderen wat je al bent.';
    }

    if (count($error) > 0) {
        foreach ($error as $item) {
            $form_error .= '- ' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '<br />';
        }
        $tpl->assign('form_error', $form_error);
    } else {
        // User wants to change his type
        $stmt = $dbCon->prepare('UPDATE users SET type = :type WHERE id = :id');
        $stmt->execute([
            ':type' => (int) $_POST['type'],
            ':id' => $userData['id']
        ]);

        $tpl->assign('success', 'Je bent succesvol overgestapt naar ' . htmlspecialchars($type[$_POST['type']]['name'], ENT_QUOTES, 'UTF-8') . '!');
    }
}

$tpl->display('ingame/typewijzigen.tpl');
