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

require_once('../../init.php');
// Initialiseer de databaseverbinding
$dbCon = getPDOConnection();

// Check if user is logged in, if not, redirect to index page
if (LOGGEDIN == FALSE) { header('Location: ' . ROOT_URL . 'index.php'); exit; }

$showPage = 'index';
$error = array();
$form_error = '';

// Check if specific page is called, if not, show default
if (isset($_GET['page']) && !empty($_GET['page'])) {
    
    // Clan member wants to delete the clan
    if ($_GET['page'] == 'delete') {
        
        // Check if user is clan owner
        if ($userData['clan_level'] < 10) {
            $tpl->assign('error', 'Je hebt geen autorisatie voor deze pagia.');
        } else {
            $showPage = 'delete';
            
            // Check if confirmation is asked
            if (isset($_GET['confirmation'])) {
                
                // Remove clan id from all users
                $stmt = $dbCon->prepare('UPDATE users SET clan_id = 0, clan_level = 0 WHERE clan_id = :clan_id');
                $stmt->execute(['clan_id' => $userData['clan_id']]);
                
                // Delete the clan itself
                $stmt = $dbCon->prepare('DELETE FROM clans WHERE clan_id = :clan_id');
                $stmt->execute(['clan_id' => $userData['id']]);
                
                $tpl->assign('succes', 'De clan is succesvol verwijderd.');
            } else {
                // Show HTML page with confirmation option
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    $tpl->assign('confirmation', true);
                }
            }
        }
    }
    
    // User wants to create a clan
    elseif ($_GET['page'] == 'create') {
        
        // Check if user is already in a clan
        if ($userData['clan_level'] > 0) {
            $tpl->assign('error', 'Je hebt al een clan of je zit in een clan, je kan niet nog een clan aanmaken!');
        } else {
            $showPage = 'create';

            // Validate creation of clan
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                // Check if name is filled
                if (!isset($_POST['name']) OR empty($_POST['name'])) {
                    $error[] = 'Geen clan naam ingevuld';
                }

                // Check if name is valid
                elseif (!preg_match('/^[A-Za-z0-9_\- ]+$/', $_POST['name'])) {
                    $error[] = 'Clan naam mag alleen letters, spaties en _- tekens bevatten!';
                } else {
                    // Check if clan name already exists
                    $stmt = $dbCon->prepare('SELECT clan_name FROM clans WHERE clan_name = :clan_name');
                    $stmt->execute(['clan_name' => $_POST['name']]);
                    if ($stmt->rowCount() > 0) {
                        $error[] = 'Clan naam bestaat al!';
                    }
                }

                // Check for errors
                if (count($error) > 0) {
                    foreach ($error as $item) {
                        $form_error .= '- ' . $item . '<br />';
                    }

                    $tpl->assign('clan_name', $_POST['name']);
                    $tpl->assign('form_error', $form_error);
                } else {
                    // Finally we can create the clan itself
                    $stmt = $dbCon->prepare('INSERT INTO clans (clan_name, clan_owner_id, clan_type) VALUES (:clan_name, :clan_owner_id, :clan_type)');
                    $stmt->execute([
                        'clan_name' => $_POST['name'],
                        'clan_owner_id' => $userData['id'],
                        'clan_type' => $userData['type']
                    ]);

                    $clan_id = $dbCon->lastInsertId();
                    $stmt = $dbCon->prepare('UPDATE users SET clan_id = :clan_id, clan_level = 10 WHERE id = :user_id');
                    $stmt->execute([
                        'clan_id' => $clan_id,
                        'user_id' => $userData['id']
                    ]);
                    
                    $tpl->assign('success', 'De clan is aangemaakt!');
                }
            }
        }
    }
    
    // User wants to leave a clan
    elseif ($_GET['page'] == 'leave') {
        
        // Check if user is in a clan
        if ($userData['clan_level'] < 1) {
            $tpl->assign('error', 'Je zit momenteel niet in een clan, dan kan je deze ook niet verlaten.');
        } else {
            $showPage = 'leave';
            
            // Check if confirmation is asked
            if (isset($_GET['confirmation'])) {
                
                // Remove user from clan
                if ($userData['clan_level'] == 10) {
                    $error[] = 'Je kan niet uit de clan stappen als je de owner bent!';
                } else {
                    $stmt = $dbCon->prepare('UPDATE users SET clan_id = 0, clan_level = 0 WHERE id = :id');
                    $stmt->execute(['id' => $userData['id']]);
                    $tpl->assign('success', 'Je bent succesvol uit de clan gestapt!');
                }
            } else {
                // Show HTML page with confirmation option
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    $tpl->assign('confirmation', true);
                }
            }
        }
    }
    
    // User wants to join a clan
    elseif ($_GET['page'] == 'join') {
        $showPage = 'join';
        
        // Check if user is in a clan
        if ($userData['clan_level'] > 0) {
            $tpl->assign('error', 'Je zit momenteel al in een clan, dan kan je een nieuwe clan niet joinen!');
        } else {
            // Validate entered clan name
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                
                // Check if name is entered
                if (!isset($_POST['name']) OR empty($_POST['name'])) {
                    $error[] = 'Je hebt geen clan naam opgegeven!';
                } else {
                    // Check if clan exists
                    $stmt = $dbCon->prepare('SELECT clan_name, clan_type, clan_id FROM clans WHERE clan_name = :clan_name LIMIT 1');
                    $stmt->execute(['clan_name' => $_POST['name']]);
                    if ($stmt->rowCount() < 1) {
                        $error[] = 'De clan naam opgegeven kan niet worden gevonden!';
                    } else {
                        // Check if the user is of the same type as the clan
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($userData['type'] != $row['clan_type']) {
                            $error[] = 'De clan heeft een andere type dan dat jij bent, je kan deze clan niet joinen!';
                        }
                    }
                }
                
                // Check for errors
                if (count($error) < 1) {
                    // Let the user apply for the clan
                    $stmt = $dbCon->prepare('INSERT INTO temp (userid, area, variable) VALUES (:userid, "clan_join", :clan_id)');
                    $stmt->execute([
                        'userid' => $userData['id'],
                        'clan_id' => $row['clan_id']
                    ]);
                    $tpl->assign('success', 'Je bent succesvol aangemeld voor de clan ' . $row['clan_name'] . '! De mensen die er over gaan zullen je aanvraag zo spoedig mogelijk bekijken!');
                }
            }
        }
    }
    
    // Show overview of the clans
    elseif ($_GET['page'] == 'overview') {
        $clanArray = array();
        
        $showPage = 'overview';
        $stmt = $dbCon->query('SELECT * FROM clans ORDER BY clan_name');
        while ($clanRow = $stmt->fetch(PDO::FETCH_ASSOC)) {
            
            $clanArray[$clanRow['clan_id']]['clan_id'] = $clanRow['clan_id'];
            $clanArray[$clanRow['clan_id']]['clan_name'] = $clanRow['clan_name'];
            
            // Retrieve total power of clan with separated clan members
            $stmtMembers = $dbCon->prepare('SELECT attack_power, defence_power, clicks, clan_level, username, id FROM users WHERE clan_id = :clan_id');
            $stmtMembers->execute(['clan_id' => $clanRow['clan_id']]);
            
            $clanArray[$clanRow['clan_id']]['clan_power'] = 0;
            $clanArray[$clanRow['clan_id']]['clan_members'] = 0;
            
            while ($memberRow = $stmtMembers->fetch(PDO::FETCH_ASSOC)) {
                $clanArray[$clanRow['clan_id']]['clan_power'] += ($memberRow['attack_power'] + ($memberRow['clicks'] * 5));
                $clanArray[$clanRow['clan_id']]['clan_members']++;
                 
                if ($memberRow['clan_level'] == 10) {
                    $clanArray[$clanRow['clan_id']]['clan_owner'] = $memberRow['username'];
                    $clanArray[$clanRow['clan_id']]['clan_owner_id'] = $memberRow['id'];
                }
            }
        }
        $tpl->assign('clanArray', $clanArray);
    } else {
        // No matching page found
        header('Location: index.php?page=overview');
        exit;
    }
} else {
    header('Location: index.php?page=overview');
    exit;
}

if (count($error) > 0) {
    foreach ($error as $item) {
        $form_error .= '- ' . $item . '<br />';
    }
    $tpl->assign('form_error', $form_error);
}

$tpl->display('clan/' . $showPage . '.tpl');
?>
