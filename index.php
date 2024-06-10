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
 
// Check if the installation folder is still available
if (file_exists('install/index.php')) {
    header('Location: install/');
    exit();
}

// Launch index
require_once('init.php');

// Check if user is logged in, if so no need to be here...
if (defined('LOGGEDIN') && LOGGEDIN === true) {
    header('Location: ' . ROOT_URL . 'ingame/index.php');
    exit();
}

// Show index page
$tpl->display('index.tpl');
?>
