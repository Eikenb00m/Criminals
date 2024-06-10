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

DEFINE('SQL_HOSTNAME', 'localhost'); // host, most of the time localhost
DEFINE('SQL_USERNAME', ''); // put username of the database here
DEFINE('SQL_PASSWORD', ''); // put password of the database here
DEFINE('SQL_DATABASE', ''); // put the database name here

DEFINE('ROOT_URL', ''); // write the URL of your website here
DEFINE('ROOT_EMAIL', ''); // write the email here

DEFINE('WEBSITE_NAME', 'Criminals'); // set the name here

// Create a PDO connection function
function getPDOConnection() {
    $host = SQL_HOSTNAME;
    $db = SQL_DATABASE;
    $user = SQL_USERNAME;
    $pass = SQL_PASSWORD;
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}
?>
