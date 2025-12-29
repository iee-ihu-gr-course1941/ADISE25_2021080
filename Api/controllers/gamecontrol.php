<?php
require_once '../config/database.php';
require_once '../utils/Response.php';
require_once '../utils/Rules.php';

class GameControl {
    private $db;
    private $response;
    private $rules;
    
    public function __construct() {
        $this->db = (new Database())->connect();
        $this->response = new Response();
        $this->rules = new Rules();
    }
    
    
    public function createGame($player_id) { // Νέο game
        $initialBoard = [
            'points' => [
                1 => ['count' => 2, 'color' => 'white'],
                12 => ['count' => 5, 'color' => 'white'],
                17 => ['count' => 3, 'color' => 'white'],
                19 => ['count' => 5, 'color' => 'white'],
                6 => ['count' => 5, 'color' => 'black'],
                8 => ['count' => 3, 'color' => 'black'],
                13 => ['count' => 5, 'color' => 'black'],
                24 => ['count' => 2, 'color' => 'black']
            ],
            'bar' => [
                'white' => 0,
                'black' => 0
            ],
            'bearing_off' => [
                'white' => 0,
                'black' => 0
            ],
            'current_turn' => 'white',  // ξεκιναμε με τα λευκα
            'dice' => []
        ];
        
        $stmt = $this->db->prepare("INSERT INTO games 
            (player1_id, current_player, board_state, status) 
            VALUES (?, ?, ?, 'waiting')");
        
        $stmt->execute([
            $player_id,
            'white',
            json_encode($initialBoard)
        ]);
        
        $game_id = $this->db->lastInsertId();
        
        return $this->response->send([
            'game_id' => $game_id,
            'message' => 'Game created successfully',
            'board' => $initialBoard
        ]);
    }
    
    
    public function joinGame($game_id, $player_id) { 
        $stmt = $this->db->prepare("SELECT * FROM games WHERE id = ? AND status = 'waiting'");
        $stmt->execute([$game_id]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$game) {
            return $this->response->sendError('Game not available', 404);
        } // για γκειμ που ηδη υπαρχει
        
        $updateStmt = $this->db->prepare("UPDATE games SET 
            player2_id = ?, 
            status = 'active',
            dice1 = ?, 
            dice2 = ?
            WHERE id = ?");
        
        // Ριχνουμε τα ζαρια
        $dice1 = rand(1, 6);
        $dice2 = rand(1, 6);
        
        $updateStmt->execute([
            $player_id,
            $dice1,
            $dice2,
            $game_id
        ]);
        
        return $this->response->send([
            'game_id' => $game_id,
            'dice' => [$dice1, $dice2],
            'message' => 'Joined game successfully'
        ]);
    }
    
    // Κατάσταση παιχνιδιου
    public function getGameState($game_id) {
        $stmt = $this->db->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$game_id]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$game) {
            return $this->response->sendError('Game not found', 404);
        }
        
        $board_state = json_decode($game['board_state'], true);
        $board_state['current_player'] = $game['current_player'];
        $board_state['dice'] = [$game['dice1'], $game['dice2']];
        $board_state['game_status'] = $game['status'];
        
        return $this->response->send($board_state);
    }
}
?>