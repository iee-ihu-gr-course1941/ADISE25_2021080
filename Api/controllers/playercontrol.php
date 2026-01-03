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
            
            return $this->response->send([
                'player_id' => $player_id,
                'message' => 'Logout successful'
            ]);
            
        } catch (Exception $e) {
            return $this->response->sendError('Logout failed: ' . $e->getMessage());
        }
    }
}
?>