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

try {
    $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_name = :setting_name LIMIT 1');
    $stmt->execute(['setting_name' => 'rules']);
    $row = $stmt->fetch();

    if ($row) {
        $tpl->assign('rules', $row['setting_value']);
    } else {
        $tpl->assign('error', 'Rules setting not found.');
    }
} catch (PDOException $e) {
    $tpl->assign('error', 'Database error: ' . $e->getMessage());
}

$tpl->display('rules.tpl');
