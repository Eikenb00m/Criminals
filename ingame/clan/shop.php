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

$error = array();
$form_error = '';

// Check if user is logged in, if not, redirect to index page
if (LOGGEDIN == FALSE) { header('Location: ' . ROOT_URL . 'index.php'); exit; }

// Check if user has clan access to this page, if not, redirect to clan index page
if ($userData['clan_level'] < 7) { header('Location: ' . ROOT_URL . 'ingame/clan/index.php'); exit; }

if (isset($_GET['page']) && !empty($_GET['page'])) {
    if ($_GET['page'] == 'sell') {
        $showPage = 'sell';

        // User has told what he wants to sell, sell it!
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            foreach ($_POST as $item => $value) {
                if ($item != 'submit') {
                    $item = str_replace('sell', '', $item);

                    // Check if has the power to sell the stuff he claims he wants to sell
                    if (!empty($value)) {
                        if (!ctype_digit($value)) {
                            $stmt = $dbCon->prepare('SELECT item_name FROM items WHERE item_id = :item_id');
                            $stmt->execute(['item_id' => $item]);
                            $itemResult = $stmt->fetch(PDO::FETCH_ASSOC);
                            $error[] = 'De opgegeven waarden voor het verkopen van een ' . $itemResult['item_name'] . ' is niet numeriek!';
                        } else {
                            $stmt = $dbCon->prepare('SELECT item_name, item_count FROM clan_items
                                                     LEFT JOIN items ON clan_items.item_id = items.item_id 
                                                     WHERE clan_items.item_id = :item_id AND clan_id = :clan_id');
                            $stmt->execute(['item_id' => $item, 'clan_id' => $userData['clan_id']]);
                            $itemResult = $stmt->fetch(PDO::FETCH_ASSOC);

                            if ($itemResult['item_count'] < $value) {
                                $error[] = 'Je hebt meer ingegeven dan je kan verkopen voor item ' . $itemResult['item_name'] . '!';
                            }
                        }
                    }

                    if ($item == 27) {
                        $error[] = 'Eenmaal een huis gekocht kan je deze niet meer verkopen!';
                    }
                }            
            }

            if (count($error) > 0) {
                foreach ($error as $item) {
                    $form_error .= '- ' . $item . '<br />';
                }
                $tpl->assign('form_error', $form_error);
            } else {
                // Clan may sell the stuff so let him do that
                foreach ($_POST as $item => $value) {
                    if ($item != 'submit') {
                        $item = str_replace('sell', '', $item);

                        if (!empty($value)) {
                            $stmt = $dbCon->prepare('SELECT item_sell, item_count, item_attack_power, item_defence_power FROM items
                                                     LEFT JOIN clan_items ON items.item_id = clan_items.item_id 
                                                     WHERE clan_items.item_id = :item_id AND clan_items.clan_id = :clan_id');
                            $stmt->execute(['item_id' => $item, 'clan_id' => $userData['clan_id']]);
                            $sellItem = $stmt->fetch(PDO::FETCH_ASSOC);

                            // Clan wants to sell it all, just delete the line...
                            if ($sellItem['item_count'] == $value) {
                                $stmt = $dbCon->prepare('DELETE FROM clan_items WHERE clan_id = :clan_id AND item_id = :item_id');
                                $stmt->execute(['clan_id' => $userData['clan_id'], 'item_id' => $item]);
                            } else {
                                $stmt = $dbCon->prepare('UPDATE clan_items SET item_count = item_count - :item_count 
                                                         WHERE clan_id = :clan_id AND item_id = :item_id');
                                $stmt->execute(['item_count' => $value, 'clan_id' => $userData['clan_id'], 'item_id' => $item]);
                            }

                            // And give the clan their money in cash but lower his attack/defence power
                            $stmt = $dbCon->prepare('UPDATE clans SET cash = cash + :cash, 
                                                         attack_power = attack_power - :attack_power, 
                                                         defence_power = defence_power - :defence_power 
                                                         WHERE clan_id = :clan_id');
                            $stmt->execute([
                                'cash' => $value * $sellItem['item_sell'],
                                'attack_power' => $sellItem['item_attack_power'] * $value,
                                'defence_power' => $sellItem['item_defence_power'] * $value,
                                'clan_id' => $userData['clan_id']
                            ]);

                            $tpl->assign('success', 'De shop is dankbaar voor de verkoop! Alles is succesvol verkocht!');
                        }
                    }
                }
            }
        }

        // Show the sell page
        $userItems = array();
        $stmt = $dbCon->prepare('SELECT * FROM clan_items
                                 LEFT JOIN items ON clan_items.item_id = items.item_id 
                                 WHERE clan_id = :clan_id AND items.item_area BETWEEN 8 AND 11');
        $stmt->execute(['clan_id' => $userData['clan_id']]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $userItems[$row['item_id']] = [
                'id' => $row['item_id'],
                'name' => $row['item_name'],
                'count' => $row['item_count'],
                'attack_power' => $row['item_attack_power'],
                'defence_power' => $row['item_defence_power'],
                'total_attack_power' => $row['item_attack_power'] * $row['item_count'],
                'total_defence_power' => $row['item_defence_power'] * $row['item_count']
            ];
        }

        $tpl->assign('items', $userItems);

    } elseif ($_GET['page'] == 'buy') {
        $showPage = 'buy';
        
        $buy = array();
        $totalCosts = 0;
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            foreach ($_POST as $item => $value) {
                if ($item != 'submit') {
                    if (strpos($item, 'buy') !== false) {
                        $item = str_replace('buy', '', $item);

                        // Check if the user has the power to buy the stuff he claims he wants to buy
                        if (!empty($value)) {
                            if (!ctype_digit($value)) {
                                $stmt = $dbCon->prepare('SELECT item_name FROM items WHERE item_id = :item_id');
                                $stmt->execute(['item_id' => $item]);
                                $itemResult = $stmt->fetch(PDO::FETCH_ASSOC);
                                $error[] = 'De opgegeven waarden voor het kopen van een ' . $itemResult['item_name'] . ' is niet numeriek!';
                            } else {
                                $stmt = $dbCon->prepare('SELECT * FROM items WHERE item_id = :item_id');
                                $stmt->execute(['item_id' => $item]);
                                $itemResult = $stmt->fetch(PDO::FETCH_ASSOC);

                                // Get total costs
                                $totalitemKosts = $value * $itemResult['item_costs'];
                                $stmt = $dbCon->prepare('SELECT cash FROM clans WHERE clan_id = :clan_id');
                                $stmt->execute(['clan_id' => $userData['clan_id']]);
                                $clanCash = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($totalitemKosts > $clanCash['cash']) {
                                    $error[] = 'Je kan niet zoveel kopen van een ' . $itemResult['item_name'] . '!';
                                }

                                // Check if the user wants to buy too much...
                                $totalCosts += $totalitemKosts;
                                if ($totalCosts > $clanCash['cash']) {
                                    $error[] = 'Je wilt te veel kopen, zoveel cash heb je niet...';
                                }
                            }
                        }
                    }
                }
            }

            if (count($error) > 0) {
                foreach ($error as $item) {
                    $form_error .= '- ' . $item . '<br />';
                }
                $tpl->assign('form_error', $form_error);
            } else {
                // User may buy it
                foreach ($_POST as $item => $value) {
                    if ($item != 'submit') {
                        if (strpos($item, 'buy') !== false) {
                            $item = str_replace('buy', '', $item);

                            if ($value > 0) {
                                // Check if there is a row for the clan and item, if so update, if not insert
                                $stmt = $dbCon->prepare('SELECT clan_id FROM clan_items WHERE clan_id = :clan_id AND item_id = :item_id');
                                $stmt->execute(['clan_id' => $userData['clan_id'], 'item_id' => $item]);
                                $checkResult = $stmt->rowCount();

                                if ($checkResult > 0) {
                                    $stmt = $dbCon->prepare('UPDATE clan_items SET item_count = item_count + :item_count 
                                                             WHERE clan_id = :clan_id AND item_id = :item_id');
                                    $stmt->execute(['item_count' => $value, 'clan_id' => $userData['clan_id'], 'item_id' => $item]);
                                } else {
                                    $stmt = $dbCon->prepare('INSERT INTO clan_items (clan_id, item_id, item_count) VALUES (:clan_id, :item_id, :item_count)');
                                    $stmt->execute(['clan_id' => $userData['clan_id'], 'item_id' => $item, 'item_count' => $value]);
                                }

                                // And now get the money from the clan!
                                $stmt = $dbCon->prepare('SELECT item_costs, item_attack_power, item_defence_power FROM items WHERE item_id = :item_id');
                                $stmt->execute(['item_id' => $item]);
                                $costResult = $stmt->fetch(PDO::FETCH_ASSOC);
                                $costs = $value * $costResult['item_costs'];
                                
                                if (!isset($costResult['attack_power'])) { $costResult['attack_power'] = 0; }
                                if (!isset($costResult['defence_power'])) { $costResult['defence_power'] = 0; }
                                
                                $stmt = $dbCon->prepare('UPDATE clans SET cash = cash - :cash, 
                                                         attack_power = attack_power + :attack_power, 
                                                         defence_power = defence_power + :defence_power 
                                                         WHERE clan_id = :clan_id');
                                $stmt->execute([
                                    'cash' => $costs,
                                    'attack_power' => $costResult['attack_power'] * $value,
                                    'defence_power' => $costResult['defence_power'] * $value,
                                    'clan_id' => $userData['clan_id']
                                ]);
                                
                                $tpl->assign('success', 'De transactie is succesvol afgerond!');
                            }
                        }
                    }
                }
            }
        }

        $itemArray = array();
        $stmt = $dbCon->prepare('SELECT * FROM items WHERE item_area = 8 OR item_area = :item_area');
        $stmt->execute(['item_area' => $userData['type'] + 8]);
        $stmtCash = $dbCon->prepare('SELECT cash FROM clans WHERE clan_id = :clan_id');
        $stmtCash->execute(['clan_id' => $userData['clan_id']]);
        $clanCash = $stmtCash->fetch(PDO::FETCH_ASSOC);

        while ($items = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $itemArray[$items['item_id']] = [
                'id' => $items['item_id'],
                'name' => $items['item_name'],
                'attack_power' => $items['item_attack_power'],
                'defence_power' => $items['item_defence_power'],
                'costs' => $items['item_costs'],
                'max_buy' => floor($clanCash['cash'] / $items['item_costs'])
            ];
        }
        $tpl->assign('items', $itemArray);
    }
} else {
    header('Location: shop.php?page=buy');
    exit;
}

$tpl->display('clan/' . $showPage . '.tpl');
?>
