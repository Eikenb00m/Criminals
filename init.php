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

// Show errors even if host doesn't want it...
ini_set('display_errors', 1);
error_reporting(-1);

// Debug mode
DEFINE('DEBUG_MODE', true);
date_default_timezone_set("Europe/Amsterdam");
$sysError = array();

// Load in default settings
require_once('config.inc.php');

// Init Smarty class
require_once('lib/Smarty.class.php');
$tpl = new Smarty();

// Define root path
DEFINE('BASE_DIR', dirname(__FILE__) . '/');

// Init database
$pdo = getPDOConnection();

// Get layout used
$stmt = $pdo->query('SELECT setting_value FROM settings WHERE setting_id = 3');
$layout = $stmt->fetch();

if (!file_exists(BASE_DIR . 'templates/' . $layout['setting_value'] . '/') || $layout['setting_value'] == '') {
    $layout['setting_value'] = 'blue';
}
DEFINE('TEMPLATE_DIR', BASE_DIR . 'templates/' . $layout['setting_value'] . '/');

// Set tpl options
$tpl->setTemplateDir(BASE_DIR . 'templates/' . $layout['setting_value'] . '/')
    ->setCompileDir(BASE_DIR . 'templates/templates_c/')
    ->setCacheDir(BASE_DIR . 'cache');

$tpl->assign('TEMPLATE_DIR', TEMPLATE_DIR);
$tpl->assign('TEMPLATE_URL', ROOT_URL . 'templates/' . $layout['setting_value'] . '/');

// Basic information to tpl
$tpl->assign('BASE_DIR', BASE_DIR);
$tpl->assign('ROOT_URL', ROOT_URL);
$tpl->assign('WEBSITE_NAME', WEBSITE_NAME);

// Check if hash_equals exists, if not php version lower than 5.6 create our own
if (!function_exists('hash_equals')) {
    function hash_equals($known_string, $user_string) {
        if (func_num_args() !== 2) {
            trigger_error('hash_equals() expects exactly 2 parameters, ' . func_num_args() . ' given', E_USER_WARNING);
            return null;
        }
        if (!is_string($known_string)) {
            trigger_error('hash_equals(): Expected known_string to be a string, ' . gettype($known_string) . ' given', E_USER_WARNING);
            return false;
        }
        $known_string_len = strlen($known_string);
        if (!is_string($user_string)) {
            trigger_error('hash_equals(): Expected user_string to be a string, ' . gettype($user_string) . ' given', E_USER_WARNING);
            $user_string_len = strlen($user_string);
            $user_string_len = $known_string_len + 1;
        } else {
            $user_string_len = strlen($user_string);
        }
        if ($known_string_len !== $user_string_len) {
            $res = $known_string ^ $known_string;
            $ret = 1;
        } else {
            $res = $known_string ^ $user_string;
            $ret = 0;
        }
        for ($i = strlen($res) - 1; $i >= 0; $i--) {
            $ret |= ord($res[$i]);
        }
        return $ret === 0;
    }
}

// Check login state
if (isset($_COOKIE['game_session_id'])) {
    $sessionId = $_COOKIE['game_session_id'];
    $stmt = $pdo->prepare('SELECT * FROM users WHERE session_id = :session_id LIMIT 1');
    $stmt->execute(['session_id' => $sessionId]);
    $userData = $stmt->fetch();

    if ($userData) {
        setcookie('game_session_id', $sessionId, time() + 86400, '/');
        DEFINE('LOGGEDIN', true);
        
        // Move all the user info into tpl variables
        $tpl->assign($userData);
        
        // Define calculated fields
        $userData['extra_attack_power'] = ($userData['clicks'] * 5);
        $tpl->assign('extra_attack_power', $userData['extra_attack_power']);
        
        $userData['total_power'] = ($userData['attack_power'] + $userData['extra_attack_power']);
        $tpl->assign('total_power', $userData['total_power']);
        
        // Update online time
        $stmt = $pdo->prepare('UPDATE users SET online_time = CURRENT_TIMESTAMP WHERE session_id = :session_id');
        $stmt->execute(['session_id' => $sessionId]);
    } else {
        setcookie('game_session_id', '', time() - 3600, '/');
        DEFINE('LOGGEDIN', false);
    }
} else {
    DEFINE('LOGGEDIN', false);
}

// Rank array
$rankStmt = $pdo->query("SELECT * FROM ranks");
$rankArray = $rankStmt->fetchAll();

$rankLastStmt = $pdo->query("SELECT * FROM ranks ORDER BY id DESC LIMIT 1");
$rankLast = $rankLastStmt->fetch();

$tpl->assign('ranks', $rankArray);
$tpl->assign('rankLast', $rankLast);

// Type array
$type = array(
    array('', ''),
    array('id' => 1, 'name' => 'Drugsdealer'),
    array('id' => 2, 'name' => 'Wetenschapper'),
    array('id' => 3, 'name' => 'Politie')
);
$tpl->assign('type', $type);

// Get current rank & type of user if user is logged in
if (LOGGEDIN) {
    foreach ($rankArray as $item) {
        if ($userData['attack_power'] >= $item['power_low'] && $userData['attack_power'] < $item['power_high']) {
            $userData['rank'] = $item['name'];
            $tpl->assign('rank', $userData['rank']);
        } elseif ($userData['attack_power'] > $rankLast['power_high']) {
            $tpl->assign('rank', $rankLast['name']);
        }
    }
    
    // Set user type
    $userData['typeName'] = $type[$userData['type']]['name'];
    $tpl->assign('typeName', $userData['typeName']);
    
    // Get unread messages
    $stmt = $pdo->prepare('SELECT COUNT(message_id) AS unreadMessages
                           FROM messages
                           LEFT JOIN users AS fromUser ON messages.message_from_id = fromUser.id
                           WHERE message_to_id = :user_id AND message_deleted_to = 0 AND message_read = 0');
    $stmt->execute(['user_id' => $userData['id']]);
    $messageCount = $stmt->fetchColumn();
    
    $tpl->assign('unreadMessages', $messageCount);
}

// Get total players
$stmt = $pdo->query('SELECT COUNT(username) AS Count FROM users WHERE activated != 0');
$row = $stmt->fetch();
$tpl->assign('totalusers', $row['Count']);

$tpl->assign('LOGGEDIN', LOGGEDIN);
?>
