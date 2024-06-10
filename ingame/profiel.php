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

// Check if id is given, if not show own profile
$userId = $userData['id'];
if (isset($_GET['id']) && !empty($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $dbCon->prepare('SELECT username FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $_GET['id']]);
    if ($stmt->rowCount() > 0) {
        // Valid id
        $userId = (int) $_GET['id'];
    } else {
        // User does not exist, show default profile (user with id 1)
        $userId = 1;
    }
}

// Fetch user profile
$stmt = $dbCon->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $userId]);
$userProfile = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user items
$stmt = $dbCon->prepare('SELECT * FROM user_items LEFT JOIN items ON user_items.item_id = items.item_id WHERE user_id = :user_id');
$stmt->execute(['user_id' => $userId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$itemArray = [];
foreach ($items as $item) {
    $itemArray[$item['item_id']] = [
        'id' => $item['item_id'],
        'name' => $item['item_name'],
        'attack_power' => $item['item_attack_power'],
        'defence_power' => $item['item_defence_power'],
        'costs' => $item['item_costs'],
        'count' => $item['item_count'],
        'total_attack_power' => ($item['item_attack_power'] * $item['item_count']),
        'total_defence_power' => ($item['item_defence_power'] * $item['item_count']),
    ];
}

if (count($items) > 1) {
    $tpl->assign('items', $itemArray);
}

$tpl->assign('user', $userProfile);
$tpl->display('ingame/profiel.tpl');
?>
