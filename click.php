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

require_once('init.php'); // Zorg ervoor dat init.php je config.inc.php bevat en de juiste autoloaders
require_once('config.inc.php');

$text = array(
    '',
    array('intro' => '%username% geeft je een plastic zakje...', 'resultText' => 'Je neemt het zakje aan en ziet dat er wit poeder in zit. Wanneer je
                                                                       aan dit poeder ruikt, komt er een vreemd gevoel over je heen.
                                                                       Na een paar minuten trekt het gevoel weg, maar je wil meer...
                                                                       Je bent verslaafd!', 'type' => 'junkies'),
    array('intro' => '%username% vraagt of hij een test met je mag doen...', 'resultText' => 'Je gaat naar binnen, maar opeens voel je een
                                                                                              harde klap op je achterhoofd. Wanneer je wakker wordt, zit je
                                                                                              vastgebonden in een stoel, en tegenover je ligt nog iemand.
                                                                                              Wanneer je beter kijkt, blijk jij het te zijn! Je bent
                                                                                              gekloond!', 'type' => 'klonen'),
    array('intro' => '%username% geeft je een formulier...', 'resultText' => 'Meteen nadat je het formulier hebt ingevuld, word je geblindoekt en
                                                                   in een wagen gegooid. Na een paar uur rijden wordt de
                                                                   blindoek weggehaald, en je ziet dat je op een trainings-basis
                                                                   bent!', 'type' => 'agenten')
);

// Check if id is entered, if not give error
if (!isset($_GET['id'])) {
    $tpl->assign('error', 'user id niet gevonden!');
} else {
    $pdo = getPDOConnection();
    $userId = $_GET['id'];
    
    // Check if id exists
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    $userData = $stmt->fetch();

    if (!$userData) {
        $tpl->assign('error', 'Gebruiker niet gevonden!');
    } else {
        // check if already clicked today
        $tpl->assign($userData);
        
        $stmt = $pdo->prepare('SELECT clicked_ip FROM clicks WHERE clicked_ip = :clicked_ip AND userid = :userid LIMIT 1');
        $stmt->execute(['clicked_ip' => $_SERVER['REMOTE_ADDR'], 'userid' => $userData['id']]);
        $rowCount = $stmt->rowCount();
    
        // check if button is pressed
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $rowCount < 1) {
            $tpl->assign('text', $text[$userData['type']]['resultText']);
            $tpl->assign('clickType', $text[$userData['type']]['type']);
            $tpl->assign('clicked', true);

            $stmt = $pdo->prepare('INSERT INTO clicks (userid, clicked_ip) VALUES (:userid, :clicked_ip)');
            $stmt->execute(['userid' => $userData['id'], 'clicked_ip' => $_SERVER['REMOTE_ADDR']]);

            $stmt = $pdo->prepare('UPDATE users SET clicks_today = clicks_today + 1, clicks = clicks + 1 WHERE session_id = :session_id');
            $stmt->execute(['session_id' => $userData['session_id']]);
        
        // check for click today already done for the id
        } elseif ($rowCount > 0) {
            $tpl->assign('error', 'Je hebt vandaag al geclicked!');
            
        // max of 50 clicks allowed
        } elseif ($userData['clicks_today'] >= 50) {
            $tpl->assign('error', '%username% heeft vandaag al genoeg clicks gehad!');
        } else {
            $tpl->assign('text', $text[$userData['type']]['intro']);
        }
    }
}

$tpl->display('click.tpl');
?>
