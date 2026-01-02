<?php
class Rules {
    
    
    public function isValidMove($board, $from, $to, $dice, $player_color) {
        // Έλεγχος αν είναι σειρά του παίκτη
        if ($board['current_turn'] !== $player_color) {
            return false;
        } 
        
       
        if (!in_array($dice, $board['available_dice'])) {
            return false;
        } // Έλεγχος για ζάρι
        
        
        if (!isset($board['points'][$from]) || 
            $board['points'][$from]['color'] !== $player_color ||
            $board['points'][$from]['count'] == 0) {
            return false;
        } // Έλεγχος αν εκεί που παμε έχει πούλια του ιδιου παίκτη
        
      
        //υπολογισμος κατευθυνσης
        $target = $this->calculateTarget($from, $dice, $player_color);
        
        // Έλεγχος εγκυρου προορισμου
        if ($target != $to) {
            return false;
        }
        
        //Έλεγχος bearing off 
        if ($target < 1 || $target > 24) {
            return $this->isValidBearOff($board, $from, $dice, $player_color);
        }
              
        if (isset($board['points'][$target])) {
            if ($board['points'][$target]['color'] == $player_color) {
                return true; // Μπορούμε να παμε
            } elseif ($board['points'][$target]['count'] == 1) {
                return true; // Κανουμε 'επιθεση'
            } else {
                return false; //Δεν μπορούμε να παμε
            }
        }
        
        return true; //κενο σημείο
    }

     private function calculateTarget($from, $dice, $player_color) {
      $current = $from;
        
        for ($i = 0; $i < $dice; $i++) {
            if ($player_color == 'white') {
                $current--;
                if ($current == 0) {
                    $current = 24; // Μετά το 1 πάει στο 24
                }
            } else {
                $current++;
                if ($current == 25) {
                    $current = 1; // Μετά το 24 πάει στο 1
                }
            }
        }
        
        return $current;
    }
    private function isValidBearOff($board, $from, $dice, $player_color) {
        //δεν μαζευουμε αν κπ πουλι του παιχτη είναι "πιασμνεο"
        
        if (!$this->canBearOff($board, $player_color)) {
            return false;
        }
        
        // Υπολογισμός αν μαζευουμε
        $target = $this->calculateTarget($from, $dice, $player_color);
        
        if ($player_color == 'white') {
            // Λευκός μαζευει στα 13-18 
            $min_home = 13;
            $max_home = 18;
            
            if ($from < $min_home || $from > $max_home) {
                return false;
            }
            
            // πόσα βήματα για να βγει
            $steps_to_exit = 0;
            $temp = $from;
            while ($temp >= $min_home) {
                $steps_to_exit++;
                $temp--;
                if ($temp == 0) $temp = 24;
            }
            
            return $dice >= $steps_to_exit;
            
        } else {
            // Μαύρος μαζεύει στα 7-12
            $min_home = 7;
            $max_home = 12;
            
            if ($from < $min_home || $from > $max_home) {
                return false;
            }
            
            // πόσα βήματα για να βγει
            $steps_to_exit = 0;
            $temp = $from;
            while ($temp <= $max_home) {
                $steps_to_exit++;
                $temp++;
                if ($temp == 25) $temp = 1;
            }
            
            return $dice >= $steps_to_exit;
        }
    }
    
    public function canBearOff($board, $player_color) {
        
        $home_start = ($player_color == 'white') ? 13 : 7;
        $home_end = ($player_color == 'white') ? 18 : 12;
        
        // Έλεγχος για πούλια εκτος ταμπλο λογω χτυπηματτος
        if ($board['bar'][$player_color] > 0) {
            return false;
        }
        
        // Έλεγχος για πούλια εκτός home board
        foreach ($board['points'] as $point => $data) {
            if ($data['color'] == $player_color && $data['count'] > 0) {
                if ($point < $home_start || $point > $home_end) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    public function getPossibleMoves($board, $player_color) {
        $possible_moves = [];
        
        if (empty($board['available_dice'])) {
            return $possible_moves;
        }
        
        foreach ($board['points'] as $point => $data) {
            if ($data['color'] == $player_color && $data['count'] > 0) {
                foreach ($board['available_dice'] as $dice) {
                    $target = $this->calculateTarget($point, $dice, $player_color);
                    
                    // Έλεγχος για bearing off
                    $is_bearing_off = ($target < 1 || $target > 24);
                    
                    if ($is_bearing_off) {
                        if ($this->isValidBearOff($board, $point, $dice, $player_color)) {
                            $possible_moves[] = [
                                'from' => $point,
                                'to' => 0, // 0 για bearing off
                                'dice' => $dice,
                                'type' => 'bearing_off'
                            ];
                        }
                    } elseif ($this->isValidMove($board, $point, $target, $dice, $player_color)) {
                        $possible_moves[] = [
                            'from' => $point,
                            'to' => $target,
                            'dice' => $dice,
                            'type' => 'normal' ];
                    }
                }
            }
        }  return $possible_moves;
    }
}   
?>