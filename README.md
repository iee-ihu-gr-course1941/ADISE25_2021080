# ADISE25_2021080

Τάβλι Πλακωτό υλοποίηση Μακρή Δήμητρα 2021080

Είναι υλοποιημένο σε PHP με βάση δεδομένων MySQL. To API (χωρις GUI) υποστηρίζει παιχνίδι μεταξύ δύο ανθρώπων μέσω curl εντολών.

Kυρια στοιχεία: 
- Ταμπλο 1-24 
- Ασπρος ξεκιναει με ολα του τα πουλια (15) στη θεση 1 και κινειται 1->24, μαζευει όταν δεν εχει κανενα πουλι στο bar και ολα τα πουλια του ειναι στις θεσεις 19-24
- Μαυρος ξεκιναει με ολα του τα πουλια (15) στη θεση 24 και κινειται 24->1, μαζευει όταν δεν εχει κανενα πουλι στο bar και ολα τα πουλια του ειναι στις θεσεις 1-6

-> Στη βάση δεδομένων υπάρχουν τα tables για τους παίχτες, το παιχνίδι και τις κινήσεις

-> Controls:
1. gamecontrol, περιλαμβάνει τις λειτουργίες:
- create game //ένας παίχτης δημιουργεί ένα καινούργιο παιχνίδι
- join game // ένας δεύετερος παίχτης κάνει join ένα ηδη υπάρχον game με το id του game και το δικό του
- getGameState //ανα πάσα στιγμή μπορούμε να δουμε το στατους του παιχνιδιου(ποιοι παιχτες ειναι συνδεδεμένοι, ποιος παιζει, πως ειναι το ταμπλο, τα ζαρια)
- getAvailableGames //βλεπουμε σε ποια game μπορουμε να συνδεθουμε
- rollDice // αυτόματα ρίχνονται τα ζάρια όταν έρχεται η σειρά του επόμενου παίχτη

2. playecontrol, περιλαμβάνει τις λειτουργίες:
- login // ενας χρήστης κανει login χωρις να χρειαζεται κωδικο
- logout //ενας παιχτης κανει logout 
- logoutFromGame // ενας παιχτης κανει logout απο ένα game, γινεται αλλαγη του στατους του game

3. movecontrol, περιλαμβάνει τις λειτουργίες:
- makeMove // παραμετροι username, game_id, dice, απο που παει και προς τα που
- updateBoardStateFromBar //ενημέρωση της κατάστασης του bar αν εγινε κινηση απο αυτό
- updateBoardState // ενημερωση της καταστασης του ταμπλο μετα την κινηση
- useDice // χρήση των available ζαριων
- checkGameEnd //ελεγχει αν το παιχνίδι έχει ολοκληρωθεί
- getPossibleMoves //μπορούμε να δούμε τις κινήσεις που μπορούν να γίνουν από τον παίχτη που έχει σειρά

-> Response: για να εχουν οι απαντήσεις που παίρνουμε μια σταθερή μορφή, αναλογα αν ειναι επιτυχημένο ότι κάναμε ή αν υπάρχει κάποιο ερρορ

-> Rules:
- isValidMove // ελεγχει αν η κινηση που θέλουμε να κναουμε ειναι συμβατη με τους κανονες του παιχνιδιου
- calculateTarget // υπολογισμός του που πανε τα πουλια
- isValidBearOff //ελεγχος αν είναι εκγυρο το "μαζεμα" που παμε να κάνουμε
- canBearOff // ελεγχος αν μπορούμε να αρχίζουμε να "μαζευουμε" τα πούλια
- canReenterFromBar //ελεγχος αν μπορούμε να βαλουμε στο ταμπλο το χτυπημενο μας πουλι
- getBarEntryPoint // αναλογα αν ειναι πουλι του ασπρου ή του μαυρου μπαινει απο διαφορετικο σημειο στο ταμπλο
- isEntryPointAvailable //ελεγχος ότι το σημειο που παμε να το βαλουμε δεν εχει 2+ πουλια του αντιπαλου
- isValidBarMove //έλεγχος ότι όντως μπορουμε να κανουμε κινηση απο το μπαρ
- getPossibleBarMoves //βλεπουμε τι κινησεις μπορουν να κανουν τα πουλια απο το bar με βαση τη ζαρια
- getPossibleMoves //συνολικα τι κινησεις μπορει να κανει ο παιχτης που εχει σειρα με βαση ττα ζαρια που έτυχε

-> database.php: διαχείρηση συνδεσης με τη βαση δεδομένων MySQL

-> index // το κυριο entry point του ΑPI 
- ορισμος http headers
- routing των requests στα αντιστοιχα endpoints
- databse connection initialization
- json responsses
- classes autoloading 

-> Εντολές curl:

//login 
curl -X POST "http://localhost/adise/Api/index.php?endpoint=player/login" -H "Content-Type: application/json" -d "{\"username\":\"player1\"}"

// create game (with id)
curl -X POST "http://localhost/adise/Api/index.php?endpoint=game/create" -H "Content-Type: application/json" -d "{\"player_id\":\"..\"}"

// join game (with id)
curl -X POST "http://localhost/adise/Api/index.php?endpoint=game/join" -H "Content-Type: application/json" -d "{\"game_id\":..,\"player_id\":\"..\"}"

// see available games
curl -X GET "http://localhost/adise/Api/index.php?endpoint=game/list

//make move (with username)
//curl -X POST "http://localhost/adise/Api/index.php?endpoint=move/make" -H "Content-Type: application/json" -d "{\"game_id\":6,\"username\":\"...\",\"from\":..,\"to\":..,\"dice\":..}"

//get game state 
curl -X GET "http://localhost/adise/Api/index.php?endpoint=game/state&game_id=.."

// get possible moves 
curl -X GET "http://localhost/adise/Api/index.php?endpoint=move/possible&game_id=..&username=.."

//bar move
curl -X POST "http://localhost/adise/Api/index.php?endpoint=move/make" -H "Content-Type: application/json" -d "{\"game_id\":..,\"username\":\"..\",\"from\":\"bar\",\"to\":..,\"dice\":..}"

//logout
curl -X POST "http://localhost/adise/Api/index.php?endpoint=player/logout" -H "Content-Type: application/json" -d "{\"username\":\"..\",\"game_id\": ..}"
