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

// Check if user has admin privileges
if ($userData['level'] < 10) {
    header('Location: ' . ROOT_URL . '/ingame/index.php');
    exit();
}

// Themes list
$themes = array('blue', 'begangster');
$tpl->assign('themes', $themes);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['theme'])) {
        $error[] = 'Geen ander thema opgegeven!';
    } elseif (!in_array($_POST['theme'], $themes)) {
        $error[] = 'Dit thema bestaat niet!';
    }

    // Check for errors, if there are any show them
    if (count($error) > 0) {
        foreach ($error as $item) {
            $form_error .= '- ' . $item . '<br />';
        }
        $tpl->assign('form_error', $form_error);
    } else {
        // Admin is going to change the theme
        try {
            $stmt = $pdo->prepare('UPDATE settings SET setting_value = :theme WHERE setting_id = 3');
            $stmt->execute(['theme' => $_POST['theme']]);
            $tpl->assign('success', 'Het thema is succesvol gewijzigd!');
        } catch (PDOException $e) {
            $tpl->assign('error', 'Er is een fout opgetreden bij het wijzigen van het thema: ' . $e->getMessage());
        }
    }
}

$tpl->display('admin/adminTheme.tpl');
