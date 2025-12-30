<?php
require_once '../config/database.php';
require_once '../utils/Response.php';

class PlayerControl {
    private $db;
    private $response;
    
    public function __construct() {
        $this->db = (new Database())->connect();
        $this->response = new Response();
    }
    
   
    public function login($username) {
        $stmt = $this->db->prepare("INSERT INTO players (id, username) VALUES (?, ?) 
                                   ON DUPLICATE KEY UPDATE username = VALUES(username)");
        $player_id = uniqid('player_');
        $stmt->execute([$player_id, $username]);
        
        return $this->response->send([
            'player_id' => $player_id,
            'username' => $username,
            'message' => 'Successful Login'
        ]);
    }  // Login χωρίς password
    
   
    public function getAvailableGame() {
        $stmt = $this->db->prepare("SELECT * FROM games WHERE status = 'waiting'");
        $stmt->execute();
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $this->response->send($games);
    }  // Available games

     public function logout($username) {
        $player_id = $username['player_id'] ?? '';
        
        if (empty($player_id)) {
            return $this->response->sendError('Player username required', 400);
        }
        
        // Έλεγχος if player exists
        $stmt = $this->db->prepare("SELECT id FROM players WHERE id = ?");
        $stmt->execute([$player_id]);
        $player = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$player) {
            return $this->response->sendError('Player not found', 404);
        }"
        
        return $this->response->send([
            'player_id' => $player_id,
            'message' => 'Successful Logout', ]);
    }
}
?>