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
// Initialiseer de databaseverbinding
$dbCon = getPDOConnection();
// Check if user is logged in, if not redirect to login page
if (!LOGGEDIN) {
    header('Location: ' . ROOT_URL . 'index.php');
    exit;
}

$error = [];
$form_error = '';

if (isset($_GET['page']) && !empty($_GET['page'])) {
    if ($_GET['page'] == 'sell') {
        $showPage = 'Sell';

        // user has told what he wants to sell, sell it!
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($_POST as $item => $value) {
                if ($item != 'submit') {
                    $item = str_replace('sell', '', $item);

                    // check if has the power to sell the stuff he claims he wants to sell
                    if (!empty($value)) {
                        if (!ctype_digit($value)) {
                            $stmt = $dbCon->prepare('SELECT item_name FROM items WHERE item_id = :item_id');
                            $stmt->execute([':item_id' => $item]);
                            $itemResult = $stmt->fetch(PDO::FETCH_ASSOC);
                            $error[] = 'De opgegeven waarden voor het verkopen van een ' . $itemResult['item_name'] . ' is niet numeriek!';
                        } else {
                            $stmt = $dbCon->prepare('SELECT item_name, item_count FROM user_items LEFT JOIN items ON user_items.item_id = items.item_id WHERE user_items.item_id = :item_id AND user_id = :user_id');
                            $stmt->execute([':item_id' => $item, ':user_id' => $userData['id']]);
                            $itemResult = $stmt->fetch(PDO::FETCH_ASSOC);

                            if ($itemResult['item_count'] < $value) {
                                $error[] = 'Je hebt meer ingegeven dan je kan verkopen voor item ' . $itemResult['item_name'] . '!';
                            }
                        }
                    }
                }
            }

            if (count($error) > 0) {
                foreach ($error as $item) {
                    $form_error .= '- ' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '<br />';
                }
                $tpl->assign('form_error', $form_error);
            } else {
                // user may sell the stuff so let him do that
                foreach ($_POST as $item => $value) {
                    if ($item != 'submit') {
                        $item = str_replace('sell', '', $item);

                        if (!empty($value)) {
                            $stmt = $dbCon->prepare('SELECT item_sell, item_count, item_attack_power, item_defence_power FROM items LEFT JOIN user_items ON items.item_id = user_items.item_id WHERE user_items.item_id = :item_id AND user_items.user_id = :user_id');
                            $stmt->execute([':item_id' => $item, ':user_id' => $userData['id']]);
                            $sellItem = $stmt->fetch(PDO::FETCH_ASSOC);

                            // User wants to sell it all, just delete the line...
                            if ($sellItem['item_count'] == $value) {
                                $stmt = $dbCon->prepare('DELETE FROM user_items WHERE user_id = :user_id AND item_id = :item_id');
                                $stmt->execute([':user_id' => $userData['id'], ':item_id' => $item]);
                            } else {
                                $stmt = $dbCon->prepare('UPDATE user_items SET item_count = item_count - :item_count WHERE user_id = :user_id AND item_id = :item_id');
                                $stmt->execute([':item_count' => $value, ':user_id' => $userData['id'], ':item_id' => $item]);
                            }

                            // And give the user his money in cash but lower his attack / defence power
                            $stmt = $dbCon->prepare('UPDATE users SET cash = cash + :cash, attack_power = attack_power - :attack_power, defence_power = defence_power - :defence_power WHERE id = :user_id');
                            $stmt->execute([
                                ':cash' => $value * $sellItem['item_sell'],
                                ':attack_power' => $sellItem['item_attack_power'] * $value,
                                ':defence_power' => $sellItem['item_defence_power'] * $value,
                                ':user_id' => $userData['id']
                            ]);

                            $tpl->assign('success', 'De shop is dankbaar voor de verkoop! Alles is succesvol verkocht!');
                        }
                    }
                }
            }
        }

        // show the sell page
        $userItems = [];
        $stmt = $dbCon->prepare('SELECT * FROM user_items LEFT JOIN items ON user_items.item_id = items.item_id WHERE user_id = :user_id AND items.item_area BETWEEN 1 AND 4');
        $stmt->execute([':user_id' => $userData['id']]);
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
    }

    if ($_GET['page'] == 'buy') {
        $showPage = 'Buy';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $buy = [];
            $totalCosts = 0;

            foreach ($_POST as $item => $value) {
                if ($item != 'submit') {
                    if (strpos($item, 'buy') !== false) {
                        $item = str_replace('buy', '', $item);

                        if (!empty($value)) {
                            if (!ctype_digit($value)) {
                                $stmt = $dbCon->prepare('SELECT item_name FROM items WHERE item_id = :item_id');
                                $stmt->execute([':item_id' => $item]);
                                $itemResult = $stmt->fetch(PDO::FETCH_ASSOC);
                                $error[] = 'De opgegeven waarden voor het kopen van een ' . $itemResult['item_name'] . ' is niet numeriek!';
                            } else {
                                $stmt = $dbCon->prepare('SELECT * FROM items WHERE item_id = :item_id');
                                $stmt->execute([':item_id' => $item]);
                                $itemResult = $stmt->fetch(PDO::FETCH_ASSOC);

                                $totalitemCosts = $value * $itemResult['item_costs'];

                                if ($totalitemCosts > $userData['cash']) {
                                    $error[] = 'Je kan niet zoveel kopen van een ' . $itemResult['item_name'] . '!';
                                }

                                $totalCosts += $totalitemCosts;
                                if ($totalCosts > $userData['cash']) {
                                    $error[] = 'Je wilt te veel kopen zoveel cash heb je niet...';
                                }
                            }
                        }
                    }
                }
            }

            if (count($error) > 0) {
                foreach ($error as $item) {
                    $form_error .= '- ' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '<br />';
                }
                $tpl->assign('form_error', $form_error);
            } else {
                foreach ($_POST as $item => $value) {
                    if ($item != 'submit') {
                        if (strpos($item, 'buy') !== false) {
                            $item = str_replace('buy', '', $item);

                            if ($value > 0) {
                                $stmt = $dbCon->prepare('SELECT user_id FROM user_items WHERE user_id = :user_id AND item_id = :item_id');
                                $stmt->execute([':user_id' => $userData['id'], ':item_id' => $item]);

                                if ($stmt->rowCount() > 0) {
                                    $stmt = $dbCon->prepare('UPDATE user_items SET item_count = item_count + :item_count WHERE user_id = :user_id AND item_id = :item_id');
                                    $stmt->execute([':item_count' => $value, ':user_id' => $userData['id'], ':item_id' => $item]);
                                } else {
                                    $stmt = $dbCon->prepare('INSERT INTO user_items (user_id, item_id, item_count) VALUES (:user_id, :item_id, :item_count)');
                                    $stmt->execute([':user_id' => $userData['id'], ':item_id' => $item, ':item_count' => $value]);
                                }

                                $stmt = $dbCon->prepare('SELECT item_costs, item_attack_power, item_defence_power FROM items WHERE item_id = :item_id');
                                $stmt->execute([':item_id' => $item]);
                                $costResult = $stmt->fetch(PDO::FETCH_ASSOC);
                                $costs = $value * $costResult['item_costs'];

                                $stmt = $dbCon->prepare('UPDATE users SET cash = cash - :cash, attack_power = attack_power + :attack_power, defence_power = defence_power + :defence_power WHERE id = :user_id');
                                $stmt->execute([
                                    ':cash' => $costs,
                                    ':attack_power' => $costResult['item_attack_power'] * $value,
                                    ':defence_power' => $costResult['item_defence_power'] * $value,
                                    ':user_id' => $userData['id']
                                ]);

                                $tpl->assign('success', 'De transactie is succesvol afgerond!');
                            }
                        }
                    }
                }
            }
        }

        $buyId = isset($_GET['id']) ? (int) $_GET['id'] : 1;
        $buyId = ($buyId < 1 || $buyId > 5) ? 1 : $buyId;

        $itemArray = [];
        $stmt = $dbCon->prepare('SELECT * FROM items WHERE item_area = :item_area');
        $stmt->execute([':item_area' => $buyId]);
        while ($items = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $itemArray[$items['item_id']] = [
                'id' => $items['item_id'],
                'name' => $items['item_name'],
                'attack_power' => $items['item_attack_power'],
                'defence_power' => $items['item_defence_power'],
                'costs' => $items['item_costs'],
                'max_buy' => floor($userData['cash'] / $items['item_costs'])
            ];
        }
        $tpl->assign('items', $itemArray);
    }
} else {
    header('Location: shop.php?page=buy&id=1');
    exit;
}

$tpl->display('ingame/shop' . $showPage . '.tpl');
