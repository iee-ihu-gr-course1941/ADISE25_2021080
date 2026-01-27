<?php
class Rules {
    
    
    public function isValidMove($board, $from, $to, $dice, $player_color) {
        
        if ($from === 'bar' || $from === 'Bar' || $from === 'BAR') {
        return $this->isValidBarMove($board, $to, $dice, $player_color);
    }
    
    
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
        if ($player_color == 'white') {// Άσπρος: 1 προς 24 
            $current++;
            if ($current == 25) {
                $current = 24; // μαχ θέση για bearing off
                break; 
            }
        } else {
            // Μαύρος: 24 προς 1 
            $current--;
            if ($current == 0) {
                $current = 1; // min θέση για bearing off
                break; 
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
            // Λευκός μαζευει στα 19-24
            $min_home = 19;
            $max_home = 24;
            
            if ($from < $min_home || $from > $max_home) {
                return false;
            }
            
            // πόσα βήματα για να βγει
            $steps_needed = 25 - $from;
        return $dice >= $steps_needed;
        
    } else {
        // Μαύρος μαζευει στα 1-6
        $min_home = 1;
        $max_home = 6;
        
        if ($from < $min_home || $from > $max_home) {
            return false;
        }
        
        $steps_needed = $from;

        return $dice >= $steps_needed;
    }
}
    
public function canBearOff($board, $player_color) {
    if ($player_color == 'white') {
        $home_start = 19;  
        $home_end = 24;
    } else {
        $home_start = 1; 
        $home_end = 6;
    }
    
    // Έλεγχος για χτυπημένα πούλια 
    if ($board['bar'][$player_color] > 0) {
        return false;
    }
    
    // Έλεγχος για πούλια εκτός ταμπλο
    foreach ($board['points'] as $point => $data) {
        if ($data['color'] == $player_color && $data['count'] > 0) {
            // Έλεγχος αν το πούλι είναι εκτός ταμπλο
            if ($player_color == 'white') {
                // Άσπρος μαζευει στα 19-24
                if ($point < $home_start) {
                    return false;
                }
            } else {
                // Μαύρος μαζευει στα 1-6
                if ($point > $home_end) {
                    return false;
                }
            }
        }
    }
    
    return true;
}
    
    public function canReenterFromBar($board, $player_color, $dice) {
    // Έλεγχος αν έχει πούλια στο bar
    if (($board['bar'][$player_color] ?? 0) == 0) {
        return false;
    }
    
    // Υπολογισμός της θέσης εισόδου
    $entry_point = $this->getBarEntryPoint($dice, $player_color);
    
    // Έλεγχος αν η θέση εισόδου είναι ελεύθερη
    return $this->isEntryPointAvailable($board, $entry_point, $player_color);
}

    private function getBarEntryPoint($dice, $player_color) {
    if ($player_color == 'white') {
        return $dice;
    } else {
        return 25 - $dice;
    }
}

    private function isEntryPointAvailable($board, $entry_point, $player_color) {
    if (!isset($board['points'][$entry_point])) {
        return true; // Κενή θέση
    }
    
    $point_data = $board['points'][$entry_point];
    
    if ($point_data['color'] == $player_color) {
        return true; //  δικά του πούλι
    } elseif ($point_data['count'] == 1) {
        return true; // μόνο 1 αντίπαλο πούλι
    }
    
    return false; // Δεν μπορεί να μπει
}

    public function isValidBarMove($board, $to, $dice, $player_color) {
    // Έλεγχος αν έχει πούλια στο bar
    if (($board['bar'][$player_color] ?? 0) == 0) {
        return false;
    }
    
    // Υπολογισμός της σωστής θέσης εισόδου
    $correct_entry_point = $this->getBarEntryPoint($dice, $player_color);
    
    // Έλεγχος αν η θέση προορισμού είναι σωστή
    if ($to != $correct_entry_point) {
        return false;
    }
    
    // Έλεγχος διαθεσιμότητας θέσης
    return $this->isEntryPointAvailable($board, $to, $player_color);
}


    public function getPossibleBarMoves($board, $player_color) {
    $possible_moves = [];
    
    if (($board['bar'][$player_color] ?? 0) == 0) {
        return $possible_moves;
    }
    
    foreach ($board['available_dice'] as $dice) {
        $entry_point = $this->getBarEntryPoint($dice, $player_color);
        
        if ($this->isEntryPointAvailable($board, $entry_point, $player_color)) {
            $possible_moves[] = [
                'from' => 'bar', 
                'to' => $entry_point,
                'dice' => $dice,
                'type' => 'bar_reentry'
            ];
        }
    }
    
    return $possible_moves;
}

    public function getPossibleMoves($board, $player_color) {
    $possible_moves = [];
    
    if (empty($board['available_dice'])) {
        return $possible_moves;
    }
     $bar_moves = $this->getPossibleBarMoves($board, $player_color);
    if (!empty($bar_moves)) {
        // Αν έχει πούλια στο bar, πρέπει πρώτα να τα βγάλει
        return $bar_moves;
    }
    
    foreach ($board['points'] as $point => $data) {
        if ($data['color'] == $player_color && $data['count'] > 0) {
            foreach ($board['available_dice'] as $dice) {
                $target = $this->calculateTarget($point, $dice, $player_color);
                
                // Έλεγχος για bearing off 
                $is_bearing_off = false;
                
                if ($player_color == 'white') {
                    // Άσπρος bearing off 
                    if ($target > 24 || ($target == 24 && $this->isValidBearOff($board, $point, $dice, $player_color))) {
                        $is_bearing_off = true;
                    }
                } else {
                    // Μαύρος bearing off
                    if ($target < 1 || ($target == 1 && $this->isValidBearOff($board, $point, $dice, $player_color))) {
                        $is_bearing_off = true;
                    }
                }
                
                if ($is_bearing_off) {
                    if ($this->isValidBearOff($board, $point, $dice, $player_color)) {
                        $possible_moves[] = [
                            'from' => $point,
                            'to' => 0, // 0 -> bearing off
                            'dice' => $dice,
                            'type' => 'bearing_off'
                        ];
                    }
                } elseif ($this->isValidMove($board, $point, $target, $dice, $player_color)) {
                    $possible_moves[] = [
                        'from' => $point,
                        'to' => $target,
                        'dice' => $dice,
                        'type' => 'normal'
                    ];
                }
            }
        }
    }
    
    return $possible_moves;
}
}   
?>