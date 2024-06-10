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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Admin wants to mass message players
    if (isset($_POST['massMsg'])) {
        if (!empty($_POST['message'])) {
            if (!empty($_POST['subject'])) {
                try {
                    $stmt = $pdo->query('SELECT id FROM users');
                    $messageStmt = $pdo->prepare('INSERT INTO messages (message_from_id, message_to_id, message_subject, message_message) VALUES (:from_id, :to_id, :subject, :message)');
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $messageStmt->execute([
                            'from_id' => $userData['id'],
                            'to_id' => $row['id'],
                            'subject' => $_POST['subject'],
                            'message' => nl2br($_POST['message'])
                        ]);
                    }
                    $tpl->assign('success', 'Het bericht is verstuurd naar alle spelers');
                } catch (PDOException $e) {
                    $tpl->assign('error', 'Er is een fout opgetreden: ' . $e->getMessage());
                }
            } else {
                $tpl->assign('error', 'Er is geen onderwerp ingegeven');
            }
        } else {
            $tpl->assign('error', 'Er is geen bericht ingegeven');
        }
    }
}

$tpl->display('admin/adminMsg.tpl');
