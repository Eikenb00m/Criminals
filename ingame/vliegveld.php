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

$error = [];
$form_error = '';

// Get current country settings
$stmt = $dbCon->prepare('SELECT setting_value FROM settings WHERE setting_id = 4 LIMIT 1');
$stmt->execute();
$country = $stmt->fetch(PDO::FETCH_ASSOC);
$countryArray = json_decode($country['setting_value'], true);

$tpl->assign('countryArray', $countryArray);
$tpl->assign('currentCountry', $countryArray[$userData['country_id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($userData['cash'] < 250) {
        $error[] = 'Een ticket kost &euro; 250,- cash';
    }
    if (empty($_POST['country']) || !isset($_POST['country'])) {
        $error[] = 'Selecteer een land om waar je heen wilt vliegen.';
    } else {
        if (!isset($countryArray[$_POST['country']])) {
            $error[] = 'Dit land bestaat niet!';
        } else {
            if ($userData['country_id'] == $_POST['country']) {
                $error[] = 'Je bent al in ' . htmlspecialchars($countryArray[$_POST['country']], ENT_QUOTES, 'UTF-8') . '!';
            }
        }
    }

    if (count($error) > 0) {
        foreach ($error as $item) {
            $form_error .= '- ' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '<br />';
        }
        $tpl->assign('form_error', $form_error);
    } else {
        $stmt = $dbCon->prepare('UPDATE users SET cash = cash - 250, country_id = :country_id WHERE id = :id');
        $stmt->execute([
            ':country_id' => $_POST['country'],
            ':id' => $userData['id']
        ]);

        $tpl->assign('currentCountry', $countryArray[$_POST['country']]);
        $tpl->assign('success', 'Je betaald 250 en bent nu in '. htmlspecialchars($countryArray[$_POST['country']], ENT_QUOTES, 'UTF-8') . '!');
    }
}

$tpl->display('ingame/vliegveld.tpl');
