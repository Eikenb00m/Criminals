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
require_once('init.php');

$error = '';

// Check if user is not logged in, no need to be here...
if (LOGGEDIN === false) {
    header('Location: ' . ROOT_URL . 'ingame/index.php');
    exit();
}

$sessionId = $_COOKIE['game_session_id'];

$stmt = $pdo->prepare('UPDATE users SET session_id = "", online_time = "" WHERE session_id = :session_id');
$stmt->execute(['session_id' => $sessionId]);

setcookie('game_session_id', '', time() - 3600, '/');
$tpl->display('logout.tpl');
