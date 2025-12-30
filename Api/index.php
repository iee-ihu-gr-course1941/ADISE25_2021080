<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// προσθεσα συγκεκριμένη παραμετρο γτ ειχα κπ δυσκολιες με τα commands
$endpoint = $_GET['endpoint'] ?? '';

if (empty($endpoint)) {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Remove script name from request URI
    if (strpos($request_uri, $script_name) === 0) {
        $rest = substr($request_uri, strlen($script_name));
        $endpoint = ltrim($rest, '/');
    } else {
        $endpoint = $_SERVER['PATH_INFO'] ?? '';
        $endpoint = ltrim($endpoint, '/');
    }
}

// JSON input
$input = json_decode(file_get_contents('php://input'), true);
if ($input === null) {
    $input = [];
}

// Debug
$debug = [
    'endpoint' => $endpoint,
    'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? '',
    'path_info' => $_SERVER['PATH_INFO'] ?? '',
    'method' => $_SERVER['REQUEST_METHOD']
];

// routing
switch ($endpoint) {
    case '':
    case 'home':
        echo json_encode([
            'status' => 'success',
            'message' => 'API is running',
            'endpoints' => ['system/health', 'player/login'],
            'debug' => $debug
        ], JSON_PRETTY_PRINT);
        break;
        
        
    case 'player/login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $input['username'] ?? '';
            
            if (empty($username)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Username required'
                ], JSON_PRETTY_PRINT);
                break;
            }
            
            echo json_encode([
                'status' => 'success',
                'player_id' => 'player_' . uniqid(),
                'username' => $username,
                'message' => 'Login successful'
            ], JSON_PRETTY_PRINT);
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Use POST']);
        }
        break;

          case 'player/logout':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $player_id = $input['player_id'] ?? '';
            
            if (empty($player_id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Player ID required'
                ], JSON_PRETTY_PRINT);
                break;
            }
            
            echo json_encode([
                'status' => 'success',
                'player_id' => $player_id,
                'message' => 'Logout successful'
            ], JSON_PRETTY_PRINT);
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Use POST']);
        }
        break;

        case 'game/create':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $player_id = $input['player_id'] ?? '';
            
            if (empty($player_id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Player ID required'
                ], JSON_PRETTY_PRINT);
                break;
            }
            
            // game creation
            $game_id = rand(1000, 9999);
            
            echo json_encode([
                'Status' => 'Success',
                'Game_id' => $game_id,
                'player_id' => $player_id,
                'message' => 'Game created successfully',
                'board_state' => [
                    'points' => [
                        '1' => ['count' => 2, 'color' => 'white'],
                        '6' => ['count' => 5, 'color' => 'black'],
                        '12' => ['count' => 5, 'color' => 'white'],
                        '13' => ['count' => 5, 'color' => 'black'],
                        '17' => ['count' => 3, 'color' => 'white'],
                        '19' => ['count' => 5, 'color' => 'white'],
                        '24' => ['count' => 2, 'color' => 'black']
                    ],
                    'current_turn' => 'white'
                ]
            ], JSON_PRETTY_PRINT);
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'Error', 'message' => 'Use POST']);
        }
        break;
        
    case 'game/join':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $game_id = $input['game_id'] ?? '';
            $player_id = $input['player_id'] ?? '';
            
            if (empty($game_id) || empty($player_id)) {
                echo json_encode([
                    'status' => 'Error',
                    'message' => 'Game ID and Player ID required'
                ], JSON_PRETTY_PRINT);
                break;
            }
            
            echo json_encode([
                'status' => 'Success',
                'game_id' => $game_id,
                'player_id' => $player_id,
                'message' => 'Joined game successfully',
                'dice' => [rand(1,6), rand(1,6)]
            ], JSON_PRETTY_PRINT);
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Use POST']);
        }
        break;
        
    case 'game/state':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $game_id = $_GET['game_id'] ?? '';
            
            if (empty($game_id)) {
                echo json_encode([
                    'status' => 'Error',
                    'message' => 'Game ID required'
                ], JSON_PRETTY_PRINT);
                break;
            }
            
            echo json_encode([
                'status' => 'Success',
                'game_id' => $game_id,
                'board_state' => [
                    'points' => [
                        '1' => ['count' => 1, 'color' => 'white'],
                        '5' => ['count' => 1, 'color' => 'white'],
                        '6' => ['count' => 4, 'color' => 'black'],
                        '12' => ['count' => 5, 'color' => 'white'],
                        '13' => ['count' => 5, 'color' => 'black'],
                        '17' => ['count' => 3, 'color' => 'white'],
                        '19' => ['count' => 5, 'color' => 'white'],
                        '24' => ['count' => 2, 'color' => 'black']
                    ],
                    'current_turn' => 'white',
                    'dice' => [3, 4]
                ],
                'status' => 'active'
            ], JSON_PRETTY_PRINT);
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'Error', 'message' => 'Use GET']);
        }
        break;
        
    case 'move/make':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $game_id = $input['game_id'] ?? '';
            $player_id = $input['player_id'] ?? '';
            $from = $input['from'] ?? 0;
            $to = $input['to'] ?? 0;
            $dice = $input['dice'] ?? 0;
            
            if (empty($game_id) || empty($player_id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Game ID and Player ID required'
                ], JSON_PRETTY_PRINT);
                break;
            }
            
            // validation
            $is_valid = ($from >= 1 && $from <= 24 && $to >= 1 && $to <= 24);
            
            echo json_encode([
                'status' => $is_valid ? 'Success' : 'Error',
                'game_id' => $game_id,
                'player_id' => $player_id,
                'move' => [
                    'from' => $from,
                    'to' => $to,
                    'dice_used' => $dice
                ],
                'message' => $is_valid ? 'Move executed successfully' : 'Invalid move',
                'new_board_state' => [
                    'points' => [
                        '1' => ['count' => 0, 'color' => 'white'],
                        '5' => ['count' => 2, 'color' => 'white'],
                        '6' => ['count' => 4, 'color' => 'black'],
                    ],
                    'current_turn' => 'black'
                ]
            ], JSON_PRETTY_PRINT);
        } else {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Use POST']);
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode([
            'status' => 'Error',
            'message' => 'Endpoint not found: ' . $endpoint,
            'available_endpoints' => [
                'player/login (POST)',
                'player/logout (POST)', 
                'game/create (POST)',
                'game/join (POST)',
                'game/state (GET)',
                'move/make (POST)'
            ]
        ], JSON_PRETTY_PRINT);
}
?>