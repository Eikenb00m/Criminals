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

// Check if user is logged in and has admin privileges
if (LOGGEDIN === false) {
    header('Location: ' . ROOT_URL . 'index.php');
    exit();
}
if ($userData['level'] < 3) {
    header('Location: ' . ROOT_URL . '/ingame/index.php');
    exit();
}

$error = array();
$form_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'GET') {
    // Admin wants to reset user
    if (isset($_POST['reset']) || isset($_GET['reset'])) {
        if (isset($_POST['player']) || isset($_GET['player'])) {
            $player = isset($_POST['player']) ? $_POST['player'] : $_GET['player'];
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
            $stmt->execute(['username' => $player]);

            if ($stmt->rowCount() != 1) {
                $tpl->assign('error', 'Speler is niet gevonden.');
            } else {
                if (isset($_GET['sureReset'])) {
                    $playerData = $stmt->fetch();
                    $pdo->beginTransaction();
                    try {
                        $pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $playerData['id']]);
                        $pdo->prepare('DELETE FROM temp WHERE userid = :userid')->execute(['userid' => $playerData['id']]);
                        $pdo->prepare('INSERT INTO users (username, password, email, type) VALUES (:username, :password, :email, :type)')
                            ->execute(['username' => $playerData['username'], 'password' => $playerData['password'], 'email' => $playerData['email'], 'type' => $playerData['type']]);
                        $pdo->commit();
                        $tpl->assign('success', 'De speler ' . $player . ' is succesvol gereset!');
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        $tpl->assign('error', 'Er is een fout opgetreden bij het resetten van de speler: ' . $e->getMessage());
                    }
                } else {
                    $tpl->assign('info', 'Weet je het zeker dat je speler ' . $player . ' wilt resetten? Klik <a href="' . ROOT_URL . 'admin/adminbasic.php?reset=true&sureReset=true&player=' . $player . '">hier</a> als je het zeker weet!');
                    $tpl->assign('player', $player);
                }
            }
        } else {
            $tpl->assign('error', 'Geen spelernaam opgegeven');
        }
    }

    // Admin wants to donate to user
    if (isset($_POST['donate']) || (isset($_GET['donate']) && isset($_GET['amount']))) {
        if (isset($_POST['player']) || isset($_GET['player'])) {
            $player = isset($_POST['player']) ? $_POST['player'] : $_GET['player'];
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
            $stmt->execute(['username' => $player]);

            if ($stmt->rowCount() != 1) {
                $tpl->assign('error', 'Speler is niet gevonden.');
            } else {
                if (isset($_POST['amount']) || isset($_GET['amount'])) {
                    $amount = isset($_POST['amount']) ? $_POST['amount'] : $_GET['amount'];
                    if (is_numeric($amount)) {
                        $pdo->prepare('UPDATE users SET bank = bank + :amount WHERE username = :username')
                            ->execute(['amount' => (int)$amount, 'username' => $player]);
                        $tpl->assign('success', 'De speler ' . $player . ' heeft ' . $amount . ' op zijn bank erbij gekregen!');
                    } else {
                        $tpl->assign('error', 'Het ingegeven bedrag is niet numeriek.');
                        $tpl->assign('player', $player);
                    }
                } else {
                    $tpl->assign('error', 'Geen bedrag ingevoerd voor donatie.');
                    $tpl->assign('player', $player);
                }
            }
        } else {
            $tpl->assign('error', 'Geen spelernaam opgegeven');
        }
    }

    // Admin wants to delete user
    if (isset($_POST['delete']) || isset($_GET['delete'])) {
        if (isset($_POST['player']) || isset($_GET['player'])) {
            $player = isset($_POST['player']) ? $_POST['player'] : $_GET['player'];
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
            $stmt->execute(['username' => $player]);

            if ($stmt->rowCount() != 1) {
                $tpl->assign('error', 'Speler is niet gevonden.');
            } else {
                if (isset($_GET['sureDelete'])) {
                    $playerData = $stmt->fetch();
                    $pdo->beginTransaction();
                    try {
                        $pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $playerData['id']]);
                        $pdo->prepare('DELETE FROM temp WHERE userid = :userid')->execute(['userid' => $playerData['id']]);
                        $pdo->commit();
                        $tpl->assign('success', 'De speler ' . $player . ' is succesvol verwijderd!');
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        $tpl->assign('error', 'Er is een fout opgetreden bij het verwijderen van de speler: ' . $e->getMessage());
                    }
                } else {
                    $tpl->assign('error', 'Weet je het zeker dat je speler ' . $player . ' wilt verwijderen? Klik <a href="' . ROOT_URL . 'admin/adminbasic.php?delete=true&sureDelete=true&player=' . $player . '">hier</a> als je het zeker weet!');
                    $tpl->assign('player', $player);
                }
            }
        } else {
            $tpl->assign('error', 'Geen spelernaam opgegeven');
        }
    }
}

$tpl->display('admin/adminBasic.tpl');
