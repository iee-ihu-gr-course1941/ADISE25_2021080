<?php
require_once '../config/database.php';
require_once '../utils/Response.php';
require_once '../utils/Rules.php';

class movecontrol {
    private $db;
    private $response;
    private $rules;
    
    public function __construct($db) {
        $this->db = $db
        $this->response = new Response();
        $this->rules = new Rules();
    }
    
    // Εκτέλεση κίνησης
    public function makeMove($data) {
        try {
            $game_id = $data['game_id'] ?? '';
            $player_id = $data['player_id'] ?? '';
            $from = $data['from'] ?? 0;
            $to = $data['to'] ?? 0;
            $dice = $data['dice'] ?? 0;
            
            // validation
            if (empty($game_id) || empty($player_id) || $from === 0 || $to === 0 || $dice === 0) {
                return $this->response->sendError('Missing required parameters');
            }
            
            $stmt = $this->db->prepare("SELECT * FROM games WHERE id = ?");
            $stmt->execute([$game_id]);
            $game = $stmt->fetch();
            
            if (!$game) {
                return $this->response->sendError('Game not found');
            }
            
            // ελεγχος στατους παιχνιδιου
            if ($game['status'] !== 'active') {
                return $this->response->sendError('Game is not active');
            }
            
            // ορισμος χρώματος στους παιχτες
            $player_color = null;
            if ($game['player1_id'] == $player_id) {
                $player_color = 'white';
            } elseif ($game['player2_id'] == $player_id) {
                $player_color = 'black';
            } else {
                return $this->response->sendError('Player not in this game');
            }
            
            // στατους ταμπλο
            $board_state = json_decode($game['board_state'], true);
            
            // έλεγχος σειρας
            if ($board_state['current_turn'] !== $player_color) {
                return $this->response->sendError('Not your turn');
            }
            
            // ελεγχος αν τα ζαρια ειναι ελευθερα
            if (!in_array($dice, $board_state['available_dice'])) {
                return $this->response->sendError('Dice not available');
            }
            
            if (!$this->rules->isValidMove($board_state, $from, $to, $dice, $player_color)) {
                return $this->response->sendError('Invalid move');
            }
            
            // Update board state
            $updated_board = $this->updateBoardState($board_state, $from, $to, $player_color);
            
            // used dice
            $updated_board = $this->useDice($updated_board, $dice);
            
            // έλγχος αν χρησιμοποιηθηκαν κ τα δτο ζαρια
            if (empty($updated_board['available_dice'])) {
                // αλλαγή σειράς
                $updated_board['current_turn'] = ($player_color == 'white') ? 'black' : 'white';
                
                $new_dice1 = rand(1, 6);
                $new_dice2 = rand(1, 6);
                $updated_board['dice'] = [$new_dice1, $new_dice2];
                $updated_board['available_dice'] = [$new_dice1, $new_dice2];
                
                // ενεημερωση τιμης ζαριων
                $updateDiceStmt = $this->db->prepare("UPDATE games SET dice1 = ?, dice2 = ? WHERE id = ?");
                $updateDiceStmt->execute([$new_dice1, $new_dice2, $game_id]);
            }
            
            // σταους παιχνιδιου
            $game_status = 'active';
            $winner = null;
            
            if ($this->checkGameEnd($updated_board, $player_color)) {
                $game_status = 'finished';
                $winner = $player_color;
                $winner_player_id = ($winner == 'white') ? $game['player1_id'] : $game['player2_id'];
            }
            
            // ενημερωση βασης
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
            
            $moveStmt = $this->db->prepare("
                INSERT INTO moves 
                (game_id, player_id, move_from, move_to, dice_used) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $moveStmt->execute([$game_id, $player_id, $from, $to, $dice]);
            
            return $this->response->send([
                'game_id' => $game_id,
                'player_id' => $player_id,
                'move' => [
                    'from' => $from,
                    'to' => $to,
                    'dice_used' => $dice
                ],
                'board_state' => $updated_board,
                'current_turn' => $updated_board['current_turn'],
                'remaining_dice' => $updated_board['available_dice'],
                'game_status' => $game_status,
                'message' => 'Move executed successfully'
            ]);
            
        } catch (Exception $e) {
            return $this->response->sendError('Move failed: ' . $e->getMessage());
        }
    }
    
    private function updateBoardState($board, $from, $to, $player_color) {
       
         if (isset($board['points'][$from])) {
        $board['points'][$from]['count']--;
        
        if ($board['points'][$from]['count'] == 0) {
            unset($board['points'][$from]);
        }
    }//αφαιρεση απο τη θεση που ηταν
        if ($to == 0) {
        $board['bearing_off'][$player_color]++;
        return $board;
    }// αν μαζευει πουλι
        // Προσθήκη στον προορισμό
        if (isset($board['points'][$to])) {
            if ($board['points'][$to]['color'] == $player_color) {
                $board['points'][$to]['count']++;
            } else {
               if ($board['points'][$to]['count'] == 1) {
                    $opponent_color = $board['points'][$to]['color'];
                    $board['bar'][$opponent_color]++; // Το πούλι φευγει απο το ταμπλ
                    
                    $board['points'][$to] = ['count' => 1, 'color' => $player_color];
                } else {
                    error_log("Cannot hit opponent - point is blocked");  // Δεν μπορούμε να χτυπήσουμε - το σημείο είναι κλειστό
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

    public function getPossibleMoves($data) {
        $game_id = $data['game_id'] ?? '';
        $player_id = $data['player_id'] ?? '';
        
        $stmt = $this->db->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$game_id]);
        $game = $stmt->fetch();
        
        if (!$game) {
            return $this->response->sendError('Game not found', 404);
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
    }
}
?>