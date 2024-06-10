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

// Check if protection is taken off or change in online list has been requested
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['onlineList'])) {
        $newStatus = ($userData['showonline'] == 1 ? 0 : 1);
        $stmt = $dbCon->prepare('UPDATE users SET showonline = :showonline WHERE id = :id');
        $stmt->execute(['showonline' => $newStatus, 'id' => $userData['id']]);
        
        $tpl->assign('success', 'Online status is succesvol gewijzigd!');
        $userData['showonline'] = $newStatus;
        $tpl->assign('showonline', $userData['showonline']);
    }
    
    if (isset($_POST['guard'])) {
        $stmt = $dbCon->prepare('UPDATE users SET protection = 0 WHERE id = :id');
        $stmt->execute(['id' => $userData['id']]);
        
        $userData['protection'] = 0;
        $tpl->assign('protection', 0);
        $tpl->assign('success', 'Je bescherming is er nu vanaf gehaald, succes!');
    }
}

// Get current online users from view
$stmt = $dbCon->query('SELECT * FROM onlineUsers');
$onlineUsers = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $onlineUsers[$row['showonline']] = $row['Count'];
}
$tpl->assign('onlineusers', $onlineUsers);

// Get click count today
$stmt = $dbCon->prepare('SELECT COUNT(userid) AS Count FROM clicks WHERE userid = :userid');
$stmt->execute(['userid' => $userData['id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$row['Count'] = $row['Count'] ?? 0;
$tpl->assign('clicks_today', $row['Count']);

// Get unread messages
$stmt = $dbCon->prepare('SELECT COUNT(message_id) FROM messages WHERE message_to_id = :message_to_id AND message_deleted_to = 0 AND message_read = 0');
$stmt->execute(['message_to_id' => $userData['id']]);
$message = $stmt->fetchColumn();
$tpl->assign('message_count', $message);

$tpl->display('ingame/index.tpl');
?>