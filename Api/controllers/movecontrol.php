<?php
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Rules.php';

class movecontrol {
    private $db;
    private $response;
    private $rules;
    
    public function __construct($db) {  
        $this->db = $db;
        $this->response = new Response();  
        $this->rules = new Rules();
    }
    
    public function makeMove($data) {
        try {
            $game_id = $data['game_id'] ?? '';
            $username = $data['username'] ?? '';
            $from = $data['from'] ?? 0;
            $to = $data['to'] ?? 0;
            $dice = $data['dice'] ?? 0;
            
            // Validation
            if (empty($username) || empty($game_id) || $from === 0 || $to === 0 || $dice === 0) {
                return $this->response->sendError('Missing required parameters');
            }
            
             $stmt = $this->db->prepare("SELECT id FROM players WHERE username = ?");
             $stmt->execute([$username]);
             $player = $stmt->fetch();
        
            if (!$player) {
            $player_id = 'player_' . uniqid();
            $stmt = $this->db->prepare("INSERT INTO players (id, username) VALUES (?, ?)");
            $stmt->execute([$player_id, $username]);
             } else {
            $player_id = $player['id'];
                     }

            $stmt = $this->db->prepare("SELECT * FROM games WHERE id = ?");
            $stmt->execute([$game_id]);
            $game = $stmt->fetch();
            
            if (!$game) {
                return $this->response->sendError('Game not found');
            }
            
            //Ελεγχος στατους παιχνιδιου
            if ($game['status'] !== 'active') {
                return $this->response->sendError('Game is not active');
            }
            
            $player_color = null;
            if ($game['player1_id'] == $player_id) {
                $player_color = 'white';
            } elseif ($game['player2_id'] == $player_id) {
                $player_color = 'black';
            } else {
                return $this->response->sendError('Player not in this game');
            } //χρωμα παιχτη
            
            //κατασταση ταμπλο
            $board_state = json_decode($game['board_state'], true);
            
            // ελεγχος σειράς
            if ($board_state['current_turn'] !== $player_color) {
                return $this->response->sendError('Not your turn');
            }

            if (!in_array($dice, $board_state['available_dice'])) {
                return $this->response->sendError('Dice not available');
            }
            
            if (!$this->rules->isValidMove($board_state, $from, $to, $dice, $player_color)) {
                return $this->response->sendError('Invalid move');
            }
            
            // Update board state
            $updated_board = $this->updateBoardState($board_state, $from, $to, $player_color);
            
            // Remove used dice
            $updated_board = $this->useDice($updated_board, $dice);
            
            $rolled_new_dice = false; //ελεγχος οτι τελειωσαν τα ζαρια
            if (empty($updated_board['available_dice'])) {
                // Αλλαγή σειράς
                $updated_board['current_turn'] = ($player_color == 'white') ? 'black' : 'white';
                
                // ριχνουμε νεα ζαρια
                $rolled_new_dice = true;
                $new_dice1 = rand(1, 6);
                $new_dice2 = rand(1, 6);

               if ($new_dice1 == $new_dice2) { //περιπτωση ιδιων ζαριων
                $updated_board['available_dice'] = [$new_dice1, $new_dice1, $new_dice1, $new_dice1];
                 } else {
                $updated_board['available_dice'] = [$new_dice1, $new_dice2];
                      }
            
            $updated_board['dice'] = [$new_dice1, $new_dice2];
            
            // Ενημέρωση βάσης για τα νέα ζάρια
            $updateDiceStmt = $this->db->prepare("UPDATE games SET dice1 = ?, dice2 = ? WHERE id = ?");
            $updateDiceStmt->execute([$new_dice1, $new_dice2, $game_id]);
        }
            
            $game_status = 'active';
            $winner = null;
            
            if ($this->checkGameEnd($updated_board, $player_color)) {
                $game_status = 'finished';
                $winner = $player_color;
                $winner_player_id = ($winner == 'white') ? $game['player1_id'] : $game['player2_id'];
            }
            
            // Update game in database
            $updateStmt = $this->db->prepare("
                UPDATE games SET 
                board_state = ?,
                current_player = ?,
                status = ?,
                winner = ?
                WHERE id = ?
            ");
            
            $updateStmt->execute([
                json_encode($updated_board),
                $updated_board['current_turn'],
                $game_status,
                $winner_player_id ?? null,
                $game_id
            ]);
            
            // ενημερωση κινησης στη βαση
             try {
            $moveStmt = $this->db->prepare("
                INSERT INTO moves 
                (game_id, player_id, move_from, move_to, dice_used) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $moveStmt->execute([$game_id, $player_id, $from, $to, $dice]);
            
            // σε περιπτωση νικης
            if ($game_status === 'finished') {
                $winStmt = $this->db->prepare("UPDATE games SET winner = ? WHERE id = ?");
                $winStmt->execute([$winner_player_id, $game_id]);
            }
            
        } catch (PDOException $e) {
            error_log("Error saving move: " . $e->getMessage());
        }
        
        $response_data = [
            'game_id' => $game_id,
            'username' => $username,
            'move' => [
                'from' => $from,
                'to' => $to,
                'dice_used' => $dice,
                'player_color' => $player_color
            ],
            'board_state' => $updated_board,
            'current_turn' => $updated_board['current_turn'],
            'remaining_dice' => $updated_board['available_dice'],
            'game_status' => $game_status,
            'message' => 'Move executed successfully'
        ];
        
        if ($rolled_new_dice) {
            $response_data['new_dice_rolled'] = true;
            $response_data['new_dice'] = $updated_board['dice'];
        }
        
        if ($game_status === 'finished') {
            $response_data['winner'] = $winner;
            $response_data['winner_player_id'] = $winner_player_id;
            $response_data['message'] = 'Game finished! ' . $player_color . ' wins!';
        }
        
        return $this->response->send($response_data);
        
    } catch (Exception $e) {
        return $this->response->sendError('Move failed: ' . $e->getMessage());
    }
}
    
    private function updateBoardState($board, $from, $to, $player_color) {
        if (isset($board['points'][$from])) {
            $board['points'][$from]['count']--; //αφαιρεση πουλιου απο την θεση του
            
            if ($board['points'][$from]['count'] == 0) {
                unset($board['points'][$from]);
            }
        }

        if ($to == 0) { //ελεγχος αν βγαινει πουλι
            $board['bearing_off'][$player_color]++;
            return $board;
        }
        
        if (isset($board['points'][$to])) {
            if ($board['points'][$to]['color'] == $player_color) {
                $board['points'][$to]['count']++; //προσθηκη στον προορισμο
            } else {
                if ($board['points'][$to]['count'] == 1) {
                    $opponent_color = $board['points'][$to]['color']; //"χτυπημα"
                    $board['bar'][$opponent_color]++; //βγαινει το πουλι του αντιπαλου
                    $board['points'][$to] = ['count' => 1, 'color' => $player_color];
                }
            }
        } else {
            $board['points'][$to] = ['count' => 1, 'color' => $player_color]; //κενο σημειο
        }
        
        return $board;
    }
    
    private function useDice($board, $dice_used) {
        if (isset($board['available_dice'])) {
            $key = array_search($dice_used, $board['available_dice']);
            if ($key !== false) {
                unset($board['available_dice'][$key]);
                $board['available_dice'] = array_values($board['available_dice']);
            }
        }
        return $board;
    }
    
    private function checkGameEnd($board, $player_color) {
        return ($board['bearing_off'][$player_color] ?? 0) == 15;
    }
    
    public function getPossibleMoves($params) {
        try {
            $game_id = $params['game_id'] ?? '';
            $username = $params['username'] ?? '';
            
            if (empty($game_id) || empty($username)) {
                return $this->response->sendError('Game ID and username are required');
            }
            
            $stmt = $this->db->prepare("SELECT id FROM players WHERE username = ?");
            $stmt->execute([$username]);
             $player = $stmt->fetch();
        
             if (!$player) {
            $player_id = 'player_' . uniqid();
            $stmt = $this->db->prepare("INSERT INTO players (id, username) VALUES (?, ?)");
            $stmt->execute([$player_id, $username]);
               } else {
            $player_id = $player['id'];
               }

            $stmt = $this->db->prepare("SELECT * FROM games WHERE id = ?");
            $stmt->execute([$game_id]);
            $game = $stmt->fetch();
            
            if (!$game) {
                return $this->response->sendError('Game not found');
            }
            
            $player_color = null;
            if ($game['player1_id'] == $player_id) {
                $player_color = 'white';
            } elseif ($game['player2_id'] == $player_id) {
                $player_color = 'black';
            } else {
                return $this->response->sendError('Player not in this game');
            }
            
            $board_state = json_decode($game['board_state'], true);
            $possible_moves = $this->rules->getPossibleMoves($board_state, $player_color);
            
            return $this->response->send([
                'possible_moves' => $possible_moves,
                'current_dice' => $board_state['available_dice'],
                'current_turn' => $board_state['current_turn']
            ]);
            
        } catch (Exception $e) {
            return $this->response->sendError('Get possible moves failed: ' . $e->getMessage());
        }
    }
}
?>