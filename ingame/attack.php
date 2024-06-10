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

if (isset($_GET['player']) || isset($_GET['id'])) {
    
    if (isset($_GET['player'])) {
        $stmt = $dbCon->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $_GET['player']]);
    } else {
        $stmt = $dbCon->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $_GET['id']]);
    }
    
    // Check if the defender exists
    if ($stmt->rowCount() > 0) {
        $defUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If defender is under protection you cannot attack that person
        if ($defUser['protection'] != 1) {
            
            // Players cannot attack a person of the same type
            if ($defUser['type'] != $userData['type']) {
                
                // The player cannot attack himself
                if ($defUser['username'] != $userData['username']) {
                    $stmt = $dbCon->prepare('SELECT * FROM temp WHERE userid = :userid AND variable = :variable AND area = "attack"');
                    $stmt->execute(['userid' => $userData['id'], 'variable' => $defUser['id']]);
                    
                    // You can attack the same player for max 5 times a day
                    if ($stmt->rowCount() < 5) {
                        
                        // With pity, players below a 1000 cash cannot be attacked
                        if ($defUser['cash'] > 1000) {
                        
                            // Check if the cooldown for attacks has passed
                            $stmt = $dbCon->prepare('SELECT * FROM temp WHERE userid = :userid AND area = "cooldown" AND (UNIX_TIMESTAMP(NOW()) - variable) < 10');
                            $stmt->execute(['userid' => $userData['id']]);
                           
                            if ($stmt->rowCount() < 1) {
                                // Insert into temp table for max attack count
                                $stmt = $dbCon->prepare('INSERT INTO temp (userid, variable, area) VALUES(:userid, :variable, "attack")');
                                $stmt->execute(['userid' => $userData['id'], 'variable' => $defUser['id']]);

                                $outcome = ((($userData['attack_power'] + $userData['extra_attack_power']) * rand(90,115)) >= ($defUser['defence_power'] + $defUser['clicks'] * 5) * rand(90,115)) ? 1 : 0;
                                $moneyTaken = ($outcome == 1) ? (int)($defUser['cash'] * rand(40,75) / 100) : (int)($userData['cash'] * rand(25,40) / 100);

                                if ($outcome == 1) {
                                    // Attacker won
                                    $stmt = $dbCon->prepare('UPDATE users SET cash = cash - :money, attacks_lost = attacks_lost + 1 WHERE id = :defender_id');
                                    $stmt->execute(['money' => $moneyTaken, 'defender_id' => $defUser['id']]);
                                    
                                    $stmt = $dbCon->prepare('UPDATE users SET cash = cash + :money, attacks_won = attacks_won + 1 WHERE id = :attacker_id');
                                    $stmt->execute(['money' => $moneyTaken, 'attacker_id' => $userData['id']]);

                                    $tpl->assign('success', 'Je valt ' . $defUser['username'] . ' aan en je wint het gevecht! Je wint ' . $moneyTaken . ' in harde cash!');
                                } else {
                                    // Attacker lost
                                    $stmt = $dbCon->prepare('UPDATE users SET cash = cash + :money, attacks_won = attacks_won + 1 WHERE id = :defender_id');
                                    $stmt->execute(['money' => $moneyTaken, 'defender_id' => $defUser['id']]);
                                    
                                    $stmt = $dbCon->prepare('UPDATE users SET cash = cash - :money, attacks_lost = attacks_lost + 1 WHERE id = :attacker_id');
                                    $stmt->execute(['money' => $moneyTaken, 'attacker_id' => $userData['id']]);

                                    $tpl->assign('error', 'Je valt ' . $defUser['username'] . ' aan en je had niet verwacht dat hij zo sterk was! Je verliest ' . $moneyTaken . '!');
                                }

                                // Slow down the fast clickers with a 5 second cooldown
                                $stmt = $dbCon->prepare('SELECT * FROM temp WHERE userid = :userid AND area = "cooldown"');
                                $stmt->execute(['userid' => $userData['id']]);
                                
                                if ($stmt->rowCount() > 0) {
                                    $stmt = $dbCon->prepare('UPDATE temp SET variable = UNIX_TIMESTAMP(NOW()) WHERE userid = :userid AND area = "cooldown"');
                                    $stmt->execute(['userid' => $userData['id']]);
                                } else {
                                    $stmt = $dbCon->prepare('INSERT INTO temp (userid, variable, area) VALUES(:userid, UNIX_TIMESTAMP(NOW()), "cooldown")');
                                    $stmt->execute(['userid' => $userData['id']]);
                                }
                            } else {
                                $tpl->assign('error', 'Je bent nog moe van de vorige aanval!');
                            }
                        } else {
                            $tpl->assign('error', $defUser['username'] . ' heeft al weinig geld, uit medelijden val je niet aan!');
                        }
                    } else {
                        $tpl->assign('error', 'Je hebt ' . $defUser['username'] . ' al 5x aangevallen vandaag!');
                    }
                } else {
                    $tpl->assign('error', 'je slaat je zelf tegen je hoofd en valt flauw neer...');
                }
            } else {
                $tpl->assign('error', 'Je mag de zelfde type niet aanvallen, is een beetje cru, niet?');
            }
        } else {
            $tpl->assign('error', 'Deze speler staat nog onder bescherming!');
        }
    } else {
        $tpl->assign('error', 'Deze speler bestaat niet!');
    }
} else {
    $tpl->assign('error', 'Geen speler ingevoerd.');
}

$tpl->display('ingame/attack.tpl');
?>
