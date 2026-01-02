<?php
require_once '../config/database.php';
require_once '../utils/Response.php';

class gamecontrol {
    private $db;
    private $response;
    private $rules;
    
    public function __construct($db) {
        $this->db = $db;
        $this->response = new Response();
    }
    
    
    public function createGame($data) {
        try {
            $player_id = $data['player_id'] ?? '';
            
            if (empty($player_id)) {
                return $this->response->sendError('Player ID is required');
            }
            
            // ελεγχουμε υπαρξη παιχτη
            $stmt = $this->db->prepare("SELECT id FROM players WHERE id = ?");
            $stmt->execute([$player_id]);
            
            if (!$stmt->fetch()) {
                return $this->response->sendError('Player not found');
            }

        $initialBoard = [
            'points' => [
                12 => ['count' => 15, 'color' => 'white'],
                13 => ['count' => 15, 'color' => 'black'],
            ], //οι αρχικες θεσεις από τα πουλια
            'bar' => [
                'white' => 0,
                'black' => 0
            ],
            'bearing_off' => [
                'white' => 0,
                'black' => 0
            ], //όλα τα πουλια είναι εντός του παιχνιδιού στην αρχή
            'current_turn' => 'white',  // ξεκιναμε με τα λευκα
            'dice' => [] //στην αρχή τα ζάρια
            'available_dice' => []
        ];
        
        $dice1 = rand(1, 6);
        $dice2 = rand(1, 6);

        $stmt = $this->db->prepare("INSERT INTO games 
            (player1_id, current_player, board_state, status) 
            VALUES (?, ?, ?, 'waiting')");
        
        $stmt->execute([
            $player_id,
            'white',
            json_encode($initialBoard)
        ]); //ο παικτης με τα λευκα ξεκιναει
        
        $game_id = $this->db->lastInsertId();
        
        return $this->response->send([
            'Game_id' => $game_id,
            'player_id' => $player_id,
            'dice' => [$dice1, $dice2],
            'Message' => 'Game created successfully',
            'Board' => $initialBoard
        ]);
    }
    
    
    public function joinGame($data) {
        try {
            $game_id = $data['game_id'] ?? '';
            $player_id = $data['player_id'] ?? '';
            
            if (empty($game_id) || empty($player_id)) {
                return $this->response->sendError('Game ID and Player ID are required');
            }
            
            $stmt = $this->db->prepare("SELECT * FROM games WHERE id = ? AND status = 'waiting'");
            $stmt->execute([$game_id]);
            $game = $stmt->fetch();
            
            if (!$game) {
                return $this->response->sendError('Game not found or not available');
            }
            
            // ελεγχος υπαρξης παιχτη
            $stmt = $this->db->prepare("SELECT id FROM players WHERE id = ?");
            $stmt->execute([$player_id]);
            
            if (!$stmt->fetch()) {
                return $this->response->sendError('Player not found');
            }
            
            // update game
            $stmt = $this->db->prepare("
                UPDATE games SET 
                player2_id = ?, 
                status = 'active'
                WHERE id = ?
            ");
            
            $stmt->execute([$player_id, $game_id]);
            
            // updated game state
            $stmt = $this->db->prepare("SELECT * FROM games WHERE id = ?");
            $stmt->execute([$game_id]);
            $updated_game = $stmt->fetch();
            
            $board_state = json_decode($updated_game['board_state'], true);
            $board_state['available_dice'] = [$updated_game['dice1'], $updated_game['dice2']];
            
            return $this->response->send([
                'game_id' => $game_id,
                'player1_id' => $updated_game['player1_id'],
                'player2_id' => $updated_game['player2_id'],
                'dice' => [$updated_game['dice1'], $updated_game['dice2']],
                'board_state' => $board_state,
                'message' => 'Joined game successfully'
            ]);
            
        } catch (Exception $e) {
            return $this->response->sendError('Join game failed: ' . $e->getMessage());
        }
    }
    
    // Κατάσταση παιχνιδιου
    public function getGameState($params) {
        try {
            $game_id = $params['game_id'] ?? '';
            
            if (empty($game_id)) {
                return $this->response->sendError('Game ID is required');
            }
            
            $stmt = $this->db->prepare("SELECT * FROM games WHERE id = ?");
            $stmt->execute([$game_id]);
            $game = $stmt->fetch();
            
            if (!$game) {
                return $this->response->sendError('Game not found');
            }
            
            $board_state = json_decode($game['board_state'], true);
            
            //state ζαριων
            if (!isset($board_state['available_dice']) || empty($board_state['available_dice'])) {
                $board_state['available_dice'] = [$game['dice1'], $game['dice2']];
            }
            
            return $this->response->send([
                'game_id' => $game_id,
                'player1_id' => $game['player1_id'],
                'player2_id' => $game['player2_id'],
                'current_player' => $game['current_player'],
                'board_state' => $board_state,
                'dice' => [$game['dice1'], $game['dice2']],
                'status' => $game['status'],
                'winner' => $game['winner']
            ]);
            
        } catch (Exception $e) {
            return $this->response->sendError('Get game state failed: ' . $e->getMessage());
        }
    }
    
    public function getAvailableGames() {
        try {
            $stmt = $this->db->prepare("
                SELECT g.*, p1.username as player1_name 
                FROM games g 
                LEFT JOIN players p1 ON g.player1_id = p1.id 
                WHERE g.status = 'waiting'
            ");
            $stmt->execute();
            $games = $stmt->fetchAll();
            
            return $this->response->send([
                'games' => $games,
                'count' => count($games)
            ]);
            
        } catch (Exception $e) {
            return $this->response->sendError('Get available game failed: ' . $e->getMessage());
        }
    }
}
?>