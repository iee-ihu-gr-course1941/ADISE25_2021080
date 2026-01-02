<?php
require_once '../config/database.php';
require_once '../utils/Response.php';

class playercontrol {
    private $db;
    private $response;
    
    public function __construct($db) {
        $this->db = $db
        $this->response = new Response();
    }
    
   
    public function login($data) {
         try {
            $username = $data['username'] ?? '';
            
            if (empty($username)) {
                return $this->response->sendError('Username is required');
            }
            
            // Check if player exists
            $stmt = $this->db->prepare("SELECT id FROM players WHERE username = ?");
            $stmt->execute([$username]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $player_id = $existing['id'];
            } else {
                // Create new player
                $player_id = 'player_' . uniqid();
                $stmt = $this->db->prepare("INSERT INTO players (id, username) VALUES (?, ?)");
                $stmt->execute([$player_id, $username]);
            }
            
            return $this->response->send([
                'player_id' => $player_id,
                'username' => $username,
                'message' => 'Login successful'
            ]);
            
        } }
        
        return $this->response->send($games);


     public function logout($username) {
        try {
            $player_id = $data['player_id'] ?? '';
            
            if (empty($player_id)) {
                return $this->response->sendError('Player ID is required');
            }
            
            // Verify player exists
            $stmt = $this->db->prepare("SELECT id FROM players WHERE id = ?");
            $stmt->execute([$player_id]);
            
            if (!$stmt->fetch()) {
                return $this->response->sendError('Player not found');
            }
            
            return $this->response->send([
                'player_id' => $player_id,
                'message' => 'Logout successful'
            ]);
            
        }
    } }
?>