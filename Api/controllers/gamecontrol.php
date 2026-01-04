

<?php
require_once __DIR__ . '/../utils/Response.php';

class gamecontrol {
    private $db;
    private $response;
    
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
            
            // ελεγχος υπαρξης παιχτη
            $stmt = $this->db->prepare("SELECT id FROM players WHERE id = ?");
            $stmt->execute([$player_id]);
            
            if (!$stmt->fetch()) {
                return $this->response->sendError('Player not found');
            }
            
            $dice1 = rand(1, 6);
            $dice2 = rand(1, 6);

            //αρχικη κατασταση ταμπλο
            $initialBoard = [
                'points' => [
                    '12' => ['count' => 15, 'color' => 'white'],
                    '13' => ['count' => 15, 'color' => 'black']
                ],
                'bar' => [
                    'white' => 0,
                    'black' => 0
                ],
                'bearing_off' => [
                    'white' => 0,
                    'black' => 0
                ],
                'current_turn' => 'white',
                'dice' => [$dice1, $dice2],
                'available_dice' => [$dice1, $dice2]
            ];
            
            
            $stmt = $this->db->prepare("
                INSERT INTO games 
                (player1_id, current_player, board_state, dice1, dice2, status) 
                VALUES (?, ?, ?, ?, ?, 'waiting')
            ");
            
            $stmt->execute([
                $player_id,
                'white',
                json_encode($initialBoard),
                $dice1,
                $dice2
            ]);
            
            $game_id = $this->db->lastInsertId();
            
            return $this->response->send([
                'game_id' => $game_id,
                'player_id' => $player_id,
                'dice' => [$dice1, $dice2],
                'board_state' => $initialBoard,
                'message' => 'Game created successfully'
            ]);
            
        } catch (Exception $e) {
            return $this->response->sendError('Game creation failed: ' . $e->getMessage());
        }
    }
    
    public function joinGame($data) {
        try {
            $game_id = $data['game_id'] ?? '';
            $player_id = $data['player_id'] ?? '';
            
            if (empty($game_id) || empty($player_id)) {
                return $this->response->sendError('Game ID and Player ID are required');
            }
            
            // Get game
            $stmt = $this->db->prepare("SELECT * FROM games WHERE id = ? AND status = 'waiting'");
            $stmt->execute([$game_id]);
            $game = $stmt->fetch();
            
            if (!$game) {
                return $this->response->sendError('Game not found or not available');
            }
            
            //ελεγχος υπαρξης παιχτη
            $stmt = $this->db->prepare("SELECT id FROM players WHERE id = ?");
            $stmt->execute([$player_id]);
            
            if (!$stmt->fetch()) {
                return $this->response->sendError('Player not found');
            }
            
            // Update game
            $stmt = $this->db->prepare("
                UPDATE games SET 
                player2_id = ?, 
                status = 'active'
                WHERE id = ?
            ");
            
            $stmt->execute([$player_id, $game_id]);
            
            // Get updated game state
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
            return $this->response->sendError('Get available games failed: ' . $e->getMessage());
        }
    }

    public function rollDice($data) {
    try {
        $game_id = $data['game_id'] ?? '';
        $player_id = $data['player_id'] ?? '';
        
        if (empty($game_id) || empty($player_id)) {
            return $this->response->sendError('Game ID and Player ID are required');
        }
        
        // Get game
        $stmt = $this->db->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$game_id]);
        $game = $stmt->fetch();
        
        if (!$game) {
            return $this->response->sendError('Game not found');
        }
        
        // έλεγχος σειρας
        $board_state = json_decode($game['board_state'], true);
        $player_color = null;
        if ($game['player1_id'] == $player_id) {
            $player_color = 'white';
        } elseif ($game['player2_id'] == $player_id) {
            $player_color = 'black';
        } else {
            return $this->response->sendError('Player not in this game');
        }
        
        if ($board_state['current_turn'] !== $player_color) {
            return $this->response->sendError('Not your turn');
        }
        
        $dice1 = rand(1, 6);
        $dice2 = rand(1, 6);
        
        // ενημερωση καταστασης ταμπλο
        $board_state['dice'] = [$dice1, $dice2];
        $board_state['available_dice'] = [$dice1, $dice2];
        
        // ενεημερωη βασης
        $updateStmt = $this->db->prepare("
            UPDATE games SET 
            dice1 = ?, 
            dice2 = ?,
            board_state = ?
            WHERE id = ?
        ");
        
        $updateStmt->execute([
            $dice1,
            $dice2,
            json_encode($board_state),
            $game_id
        ]);
        
        return $this->response->send([
            'game_id' => $game_id,
            'player_id' => $player_id,
            'dice' => [$dice1, $dice2],
            'message' => 'Dice rolled successfully'
        ]);
        
    } catch (Exception $e) {
        return $this->response->sendError('Roll dice failed: ' . $e->getMessage());
    }
}
}
?>