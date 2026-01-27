<?php
require_once __DIR__ . '/../utils/Response.php';

class playercontrol {
    private $db;
    private $response;
    
    public function __construct($db) {
        $this->db = $db;
        $this->response = new Response();
    }
    
    public function login($data) {
        try {
            $username = $data['username'] ?? '';
            
            if (empty($username)) {
                return $this->response->sendError('Username is required');
            }
            
            // ελεγχος υπαρξης παιχτη
            $stmt = $this->db->prepare("SELECT id FROM players WHERE username = ?");
            $stmt->execute([$username]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $player_id = $existing['id'];
            } else {
                // δημιουργια παιχτη
                $player_id = 'player_' . uniqid();
                $stmt = $this->db->prepare("INSERT INTO players (id, username) VALUES (?, ?)");
                $stmt->execute([$player_id, $username]);
            }
            
            return $this->response->send([
                'player_id' => $player_id,
                'username' => $username,
                'message' => 'Login successful'
            ]);
            
        } catch (Exception $e) {
            return $this->response->sendError('Login failed: ' . $e->getMessage());
        }
    }
    
public function logout($data) {
    try {
        $username = $data['username'] ?? '';
        $game_id = $data['game_id'] ?? null;
        
        if (empty($username)) {
            return $this->response->sendError('Username is required');
        }
        
        $stmt = $this->db->prepare("SELECT id FROM players WHERE username = ?");
        $stmt->execute([$username]);
        $player = $stmt->fetch();
        
        if (!$player) {
            return $this->response->sendError('Player not found');
        }
        
        $player_id = $player['id'];
        
        if ($game_id !== null) {
            // Logout από συγκεκριμένο game
            return $this->logoutFromGame($player_id, $game_id, $username);
        } else {
            // Logout από όλα τα games
            return $this->logoutFromAllGames($player_id, $username);
        }
        
    } catch (Exception $e) {
        return $this->response->sendError('Logout failed: ' . $e->getMessage());
    }
}

private function logoutFromGame($player_id, $game_id, $username) {
    try {
        if ($game_id === null) {
            return $this->response->sendError('Game ID is required');
        }
        
        $game_id = intval($game_id);
        
        if ($game_id <= 0) {
            return $this->response->sendError('Invalid game ID: ' . $data['game_id']);
        }
        
        // Έλεγχος αν το game υπάρχει
        $stmt = $this->db->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$game_id]);
        $game = $stmt->fetch();
        
        if (!$game) {
            return $this->response->sendError('Game not found. ID: ' . $game_id);
        }
        
        // Έλεγχος αν ο παίκτης είναι μέρος του game
        $is_player1 = ($game['player1_id'] == $player_id);
        $is_player2 = ($game['player2_id'] == $player_id);
        
        if (!$is_player1 && !$is_player2) {
            $playerCheckStmt = $this->db->prepare("
                SELECT id FROM players 
                WHERE username = ?
            ");
            $playerCheckStmt->execute([$username]);
            $playerData = $playerCheckStmt->fetch();
            
            if ($playerData && ($game['player1_id'] == $playerData['id'] || $game['player2_id'] == $playerData['id'])) {
                // Βρήκαμε διαφορετικό player_id για το ίδιο username
                $player_id = $playerData['id'];
                $is_player1 = ($game['player1_id'] == $player_id);
                $is_player2 = ($game['player2_id'] == $player_id);
            } else {
                return $this->response->sendError('Player ' . $username . ' is not in game ' . $game_id);
            }
        }
        
        $new_status = $game['status'];
        $logout_type = '';
        
        // Αν ο παίκτης που βγαίνει είναι ο player1
        if ($is_player1) {
            if ($game['player2_id'] != null) {
                // Υπάρχει ακόμα ο player2
                $new_status = ($game['status'] == 'active') ? 'paused' : $game['status'];
                $logout_type = 'player1_left';
                
                $updateStmt = $this->db->prepare("UPDATE games SET player1_id = NULL WHERE id = ?");
                $updateStmt->execute([$game_id]);
            } else {
                // Μόνος του
                $new_status = 'cancelled';
                $logout_type = 'game_cancelled';
                
                $updateStmt = $this->db->prepare("UPDATE games SET player1_id = NULL, status = 'cancelled' WHERE id = ?");
                $updateStmt->execute([$game_id]);
            }
        } 
        // Αν ο παίκτης που βγαίνει είναι ο player2
        elseif ($is_player2) {
            $new_status = ($game['status'] == 'active') ? 'paused' : $game['status'];
            $logout_type = 'player2_left';
            
            $updateStmt = $this->db->prepare("UPDATE games SET player2_id = NULL WHERE id = ?");
            $updateStmt->execute([$game_id]);
        }
        
        // active -> paused
        if ($game['status'] == 'active' && $new_status != 'cancelled') {
            $updateStmt = $this->db->prepare("UPDATE games SET status = 'paused' WHERE id = ?");
            $updateStmt->execute([$game_id]);
            $new_status = 'paused';
        }
        
        return $this->response->send([
            'status' => 'success',
            'message' => 'Successfully logged out from game',
            'game_id' => $game_id,
            'username' => $username,
            'logout_type' => $logout_type,
            'game_status' => $new_status,
            'board_preserved' => true
        ]);
        
    } catch (Exception $e) {
        return $this->response->sendError('Logout failed: ' . $e->getMessage());
    }
}


}
?>