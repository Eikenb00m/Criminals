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

if (isset($_GET['page']) && !empty($_GET['page'])) {
    
    // User wants to read a message
    if ($_GET['page'] == 'read') {
        $showPage = 'Read';
        
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $error[] = 'Geen bericht ID gevonden of ID is niet valide!';
        } else {
            $stmt = $dbCon->prepare('SELECT message_id, message_to_id FROM messages WHERE message_id = :id');
            $stmt->execute(['id' => $_GET['id']]);
            $fetch = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$fetch) {
                $error[] = 'Bericht is niet gevonden!';
            } elseif ($userData['id'] != $fetch['message_to_id'] && $level == 0) {
                $error[] = 'Je hebt geen rechten om dit bericht te lezen!';
            }
        }
        
        if (count($error) > 0) {
            foreach ($error as $item) {
                $form_error .= '- ' . $item . '<br />';
            }
            $tpl->assign('form_error', $form_error);
        } else {
            // User may read the message
            $stmt = $dbCon->prepare('SELECT message_subject, message_message, message_time, fromUser.username, fromUser.id
                                     FROM messages
                                     LEFT JOIN users AS fromUser ON messages.message_from_id = fromUser.id
                                     WHERE message_id = :id LIMIT 1');
            $stmt->execute(['id' => $_GET['id']]);
            $message = $stmt->fetch(PDO::FETCH_ASSOC);

            // Update to read status
            $stmt = $dbCon->prepare('UPDATE messages SET message_read = 1 WHERE message_id = :id');
            $stmt->execute(['id' => $_GET['id']]);
            $tpl->assign('message', $message);
        }
    }
    
    // User wants to see their inbox
    if ($_GET['page'] == 'inbox') {
        $showPage = 'Inbox';
        
        $messageArray = array();
        $stmt = $dbCon->prepare('SELECT message_id, message_subject, message_time, fromUser.username, message_read
                                 FROM messages
                                 LEFT JOIN users AS fromUser ON messages.message_from_id = fromUser.id
                                 WHERE message_to_id = :id AND message_deleted_to = 0');
        $stmt->execute(['id' => $userData['id']]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $messageArray[$row['message_id']] = [
                'id' => $row['message_id'],
                'subject' => $row['message_subject'],
                'from' => $row['username'],
                'time' => $row['message_time'],
                'read' => $row['message_read']
            ];
        }
        
        $tpl->assign('message', $messageArray);
    }
    
    // User wants to see their outbox
    if ($_GET['page'] == 'outbox') {
        $showPage = 'Outbox';
        
        $messageArray = array();
        $stmt = $dbCon->prepare('SELECT message_id, message_subject, message_time, toUser.username
                                 FROM messages
                                 LEFT JOIN users AS toUser ON messages.message_to_id = toUser.id
                                 WHERE message_from_id = :id AND message_deleted_from = 0');
        $stmt->execute(['id' => $userData['id']]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $messageArray[$row['message_id']] = [
                'id' => $row['message_id'],
                'subject' => $row['message_subject'],
                'to' => $row['username'],
                'time' => $row['message_time']
            ];
        }
        
        $tpl->assign('message', $messageArray);
    }
    
    // User wants to send a message
    if ($_GET['page'] == 'new') {
        $showPage = 'New';
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (empty($_POST['message'])) {
                $error[] = 'Er is geen bericht ingegeven!';
            } elseif (strlen($_POST['message']) > 1000) {
                $error[] = 'Het ingevoerde bericht is te lang!';
            }
            
            if (empty($_POST['subject'])) {
                $error[] = 'Er is geen onderwerp ingegeven!';
            }
            
            if (empty($_POST['to'])) {
                $error[] = 'Er is geen speler ingegeven waar naartoe verzonden moet worden!';
            } else {
                $stmt = $dbCon->prepare('SELECT username FROM users WHERE username = :username');
                $stmt->execute(['username' => $_POST['to']]);
                if ($stmt->rowCount() < 1) {
                    $error[] = 'De speler waar naartoe verzonden wilt worden bestaat niet!';
                }
            }
            
            if (count($error) > 0) {
                foreach ($error as $item) {
                    $form_error .= '- ' . $item . '<br />';
                }

                $tpl->assign('form_error', $form_error);
            } else {
                // User may send the message
                $stmt = $dbCon->prepare('SELECT id, username FROM users WHERE username = :username LIMIT 1');
                $stmt->execute(['username' => $_POST['to']]);
                $recUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $dbCon->prepare('INSERT INTO messages (message_from_id, message_to_id, message_subject, message_message)
                                         VALUES (:from_id, :to_id, :subject, :message)');
                $stmt->execute([
                    'from_id' => $userData['id'],
                    'to_id' => $recUser['id'],
                    'subject' => $_POST['subject'],
                    'message' => $_POST['message']
                ]);
            
                $tpl->assign('success', 'Het bericht is succesvol verzonden naar ' . $recUser['username'] . '!');
            }
        } else {
            // Check if user id is found, if so check if user exists and if so fill send to with that user
            if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                $stmt = $dbCon->prepare('SELECT username FROM users WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $_GET['id']]);
                $fetchUser = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($fetchUser) {
                    $tpl->assign('toUser', $fetchUser['username']);
                }
            }
        }
    }
} else {
    header('Location: ' . ROOT_URL . 'ingame/message.php?page=inbox');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if user wants to delete message, if so delete the given messages in $_POST['id']
    if (isset($_POST['delMessagesOutbox']) || isset($_POST['delMessagesInbox'])) {
        if (isset($_POST['id'])) {
            foreach ($_POST['id'] as $id => $status) {
                $stmt = $dbCon->prepare('SELECT message_deleted_from, message_deleted_to FROM messages WHERE message_id = :id');
                $stmt->execute(['id' => $id]);
                $mDelete = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                // Check if user delete messages from inbox or outbox
                if (isset($_POST['delMessagesInbox'])) {
                    // If sender already deleted the message delete the row
                    if ($mDelete['message_deleted_from'] == 1) {
                        $stmt = $dbCon->prepare('DELETE FROM messages WHERE message_id = :id');
                        $stmt->execute(['id' => $id]);
                    } else {
                        $stmt = $dbCon->prepare('UPDATE messages SET message_deleted_to = 1 WHERE message_id = :id');
                        $stmt->execute(['id' => $id]);
                    }
                } else {
                    // If receiver already deleted the message delete the row
                    if ($mDelete['message_deleted_to'] == 1) {
                        $stmt = $dbCon->prepare('DELETE FROM messages WHERE message_id = :id');
                        $stmt->execute(['id' => $id]);
                    } else {
                        $stmt = $dbCon->prepare('UPDATE messages SET message_deleted_from = 1 WHERE message_id = :id');
                        $stmt->execute(['id' => $id]);
                    } 
                }
                $tpl->assign('success', (count($_POST['id']) > 1 ? 'De berichten zijn' : 'Het bericht is') . ' succesvol verwijderd!');
                header('Refresh: 0.5; url=' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }
}

$tpl->display('ingame/message' . $showPage . '.tpl');
?>
