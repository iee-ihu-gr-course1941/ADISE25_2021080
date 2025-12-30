<?php
require_once '../config/database.php';
require_once '../utils/Response.php';
require_once '../utils/Rules.php';

class MoveControl {
    private $db;
    private $response;
    private $rules;
    
    public function __construct() {
        $this->db = (new Database())->connect();
        $this->response = new Response();
        $this->rules = new Rules();
    }
    
    // Εκτέλεση κίνησης
    public function makeMove($game_id, $player_id, $from, $to, $dice_used) {
        $stmt = $this->db->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$game_id]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$game) {
            return $this->response->sendError('Game not found', 404);
        }
        
        // Έλεγχος σειράς 
        $board_state = json_decode($game['board_state'], true);
        $player_color = ($game['player1_id'] == $player_id) ? 'white' : 'black';
        
        if ($board_state['current_turn'] !== $player_color) {
            return $this->response->sendError('Not your turn', 400);
        }
        
        // Έλεγχος κανόνων
        if (!$this->rules->isValidMove($board_state, $from, $to, $dice_used, $player_color)) {
            return $this->response->sendError('Invalid move', 400);
        }
        
        // Ενημέρωση κατάστασης 
        $updated_board = $this->updateBoardState($board_state, $from, $to, $player_color);
        
        //  Αφαίρεση του ζαριού που χρησιμοποιήθηκε
        $updated_board = $this->useDice($updated_board, $dice_used);
        
        // Έλεγχος για τέλος παιχνιδιού
        if ($this->checkGameEnd($updated_board, $player_color)) {
            $winner = $player_color;
            $status = 'finished';
        } else {
            $winner = null;
            
            //Αλλαγή σειράς 
            if (empty($updated_board['available_dice'])) {
                $updated_board['current_turn'] = ($player_color == 'white') ? 'black' : 'white';
                // Ρίξιμο νέων ζαριών
                $updated_board['dice'] = [rand(1, 6), rand(1, 6)];
                $updated_board['available_dice'] = $updated_board['dice'];
            }
            $status = 'active';
        }
        
        //Αποθήκευση στη βάση
        $updateStmt = $this->db->prepare("UPDATE games SET 
            board_state = ?,
            current_player = ?,
            dice1 = ?,
            dice2 = ?,
            status = ?,
            winner = ?
            WHERE id = ?");
        
        $updateStmt->execute([
            json_encode($updated_board),
            $updated_board['current_turn'],
            $updated_board['dice'][0] ?? null,
            $updated_board['dice'][1] ?? null,
            $status,
            $winner,
            $game_id
        ]);
        
        // Αποθήκευση κίνησης
        $moveStmt = $this->db->prepare("INSERT INTO moves 
            (game_id, player_id, move_from, move_to, dice_used) 
            VALUES (?, ?, ?, ?, ?)");
        $moveStmt->execute([$game_id, $player_id, $from, $to, $dice_used]);
        
        return $this->response->send([
            'success' => true,
            'new_board' => $updated_board,
            'message' => 'Move executed successfully'
        ]);
    }
    
    private function updateBoardState($board, $from, $to, $player_color) {
       
        $board['points'][$from]['count']--;
        if ($board['points'][$from]['count'] == 0) {
            unset($board['points'][$from]);
        }
        
        // Προσθήκη στον προορισμό
        if (isset($board['points'][$to])) {
            if ($board['points'][$to]['color'] == $player_color) {
                $board['points'][$to]['count']++;
            } else {
                // 'Επίθεση' αντιπάλου
                $board['bar'][$board['points'][$to]['color']]++;
                $board['points'][$to] = ['count' => 1, 'color' => $player_color];
            }
        } else {
            $board['points'][$to] = ['count' => 1, 'color' => $player_color];
        }
        
        return $board;
    }
    
    private function useDice($board, $dice_used) {
        $key = array_search($dice_used, $board['available_dice']);
        if ($key !== false) {
            unset($board['available_dice'][$key]);
            $board['available_dice'] = array_values($board['available_dice']);
        }
        return $board;
    }
    
    private function checkGameEnd($board, $player_color) {
        return $board['bearing_off'][$player_color] == 15;
    }
}
?>