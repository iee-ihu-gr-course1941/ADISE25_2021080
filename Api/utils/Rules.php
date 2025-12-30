<?php
class Rules {
    
    
    public function isValidMove($board, $from, $to, $dice, $player_color) {
        // Έλεγχος αν είναι σειρά του παίκτη
        if ($board['current_turn'] !== $player_color) {
            return false;
        } // Έλεγχος κίνησης
        
       
        if (!in_array($dice, $board['available_dice'])) {
            return false;
        } // Έλεγχος για ζάρι
        
        
        if (!isset($board['points'][$from]) || 
            $board['points'][$from]['color'] !== $player_color ||
            $board['points'][$from]['count'] == 0) {
            return false;
        } // Έλεγχος αν εκεί που παμε έχει πούλια του ιδιου παίκτη
        
      
        $direction = ($player_color == 'white') ? 1 : -1;
        $target = $from + ($dice * $direction);
        
        
        if ($this->canBearOff($board, $player_color)) {
            if ($target > 24 || $target < 1) {
                return $this->isValidBearOff($board, $from, $dice, $player_color);
            }
        } //bearing off
        
        
        if ($target < 1 || $target > 24) {
            return false;
        } // Ελεγχος προορισμου
        
       
        if (isset($board['points'][$target])) {
            if ($board['points'][$target]['color'] == $player_color) {
                return true; // Μπορούμε να παμε
            } elseif ($board['points'][$target]['count'] == 1) {
                return true; // Κανουμε 'επιθεση'
            } else {
                return false; //Δεν μπορούμε να παμε
            }
        }
        
        return true; 
    }
    
    // Έλεγχος για bearing off
    public function canBearOff($board, $player_color) {
        $home_start = ($player_color == 'white') ? 1 : 19;
        $home_end = ($player_color == 'white') ? 6 : 24;
        
        // Έλεγχος αν όλα τα πούλια είναι στο παιχνίδι
        foreach ($board['points'] as $point => $data) {
            if ($data['color'] == $player_color && $data['count'] > 0) {
                if ($player_color == 'white' && ($point < $home_start || $point > $home_end)) {
                    return false;
                }
                if ($player_color == 'black' && ($point < $home_start || $point > $home_end)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
  
    public function hasValidMoves($board, $player_color, $dice) {  
        $possible_moves = $this->calculatePossibleMoves($board, $player_color, $dice);
        return !empty($possible_moves);
    }   
    private function calculatePossibleMoves($board, $player_color, $dice) {
        $moves = [];
        return $moves;
    }  
    private function isValidBearOff($board, $from, $dice, $player_color) {
        return true;
    }
}
?>