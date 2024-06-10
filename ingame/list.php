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

// Check if user is logged in, if not, redirect to index page
if (LOGGEDIN == FALSE) { header('Location: ' . ROOT_URL . 'index.php'); exit; }
$listArray = array();

// User online list check
if (isset($_POST['onlineList'])) {
    $newStatus = ($userData['showonline'] == 1 ? 0 : 1);
    $stmt = $dbCon->prepare('UPDATE users SET showonline = :showonline WHERE id = :id');
    $stmt->execute(['showonline' => $newStatus, 'id' => $userData['id']]);
    $tpl->assign('success', 'Online status is succesvol gewijzigd!');
        
    $userData['showonline'] = $newStatus;
    $tpl->assign('showonline', $userData['showonline']);
}

// Check if user wants a different sorting
$orderBy = 'username';
if (isset($_GET['order']) && !empty($_GET['order'])) {
    $validColumns = ['username', 'attack_power', 'type', 'cash', 'bank'];
    if (in_array($_GET['order'], $validColumns)) {
        $orderBy = $_GET['order'];
    }
}

// Check if pagination is active
$start = 0;
if (isset($_GET['start']) && is_numeric($_GET['start'])) {
    $start = (int) $_GET['start'];
}

$stmt = $dbCon->prepare('SELECT id, username, attack_power, type, cash, bank, clicks FROM users WHERE activated = 1 AND showonline = 1 ORDER BY ' . $orderBy . ' LIMIT :start, 50');
$stmt->bindParam(':start', $start, PDO::PARAM_INT);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $listArray[$row['id']] = [
        'id' => $row['id'],
        'username' => $row['username'],
        'type' => $type[$row['type']]['name'],
        'type_id' => $row['type'],
        'cash' => $row['cash'],
        'bank' => $row['bank'],
        'attack_power' => ($row['attack_power'] + ($row['clicks'] * 5))
    ];
}
$tpl->assign('list', $listArray);

// Activate pagination
$pageResult = $dbCon->query('SELECT COUNT(id) FROM users')->fetchColumn();
$pageCount = ceil($pageResult / 50);

// Get current active page
$p = 1;
$pC = 0;
while ($p <= $pageCount) {
    if ($pC == $start) {
        break;
    }
    $pC += 50;
    $p++;
}
$pagination = [
    'cPage' => $p,
    'tPage' => $pageCount
];

for ($i = 1; $i <= $pageCount; $i++) {
    $pagination['pageBegin'][$i] = (($i - 1) * 50);
}
$tpl->assign('pagination', $pagination);

// Get current online users from view
$result = $dbCon->query('SELECT * FROM onlineUsers');
$onlineUsers = [];
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $onlineUsers[$row['showonline']] = $row['Count'];
}
$tpl->assign('onlineusers', $onlineUsers);

// Output page
$tpl->assign('order', $orderBy);
$tpl->display('ingame/list.tpl');
?>
