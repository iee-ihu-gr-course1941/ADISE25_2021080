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

spl_autoload_register(function ($class_name) {
    $directories = [
        'controllers/','utils/','config/'
    ];
    
    foreach ($directories as $directory) {
        $file = __DIR__ . '/' . $directory . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
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

$params = array_merge($_GET, $input);

try {
    // Database connection
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->connect();
// routing
switch ($endpoint) {
    case '':
    case 'home':
        echo json_encode([
            'status' => 'success',
            'message' => 'API is running',
            'endpoints' => ['player/login (POST)',
                'player/logout (POST)', 
                'game/create (POST)',
                'game/join (POST)',
                'game/state (GET)',
                'move/make (POST)'],
        ], JSON_PRETTY_PRINT);
        break;
        
        
    case 'player/login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                require_once 'controllers/playercontrol.php';
                $controller = new playercontrol($db);
                echo json_encode($controller->login($input), JSON_PRETTY_PRINT);
            } else {
                http_response_code(405);
                echo json_encode(['status' => 'Error', 'message' => 'Method not allowed'], JSON_PRETTY_PRINT);
            }
            break;
            

    case 'player/logout':
             if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                require_once 'controllers/playercontrol.php';
                $controller = new playercontrol($db);
                echo json_encode($controller->logout($input), JSON_PRETTY_PRINT);
            } else {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_PRETTY_PRINT);
            }
            break;
            

        case 'game/create':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                require_once 'controllers/gamecontrol.php';
                $controller = new gamecontrol($db);
                echo json_encode($controller->createGame($input), JSON_PRETTY_PRINT);
            } else {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_PRETTY_PRINT);
            }
            break;
        
    case 'game/join':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                require_once 'controllers/gamecontrol.php';
                $controller = new gamecontrol($db);
                echo json_encode($controller->joinGame($input), JSON_PRETTY_PRINT);
            } else {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_PRETTY_PRINT);
            }
            break;
        
    case 'game/state':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                require_once 'controllers/gamecontrol.php';
                $controller = new gamecontrol($db);
                echo json_encode($controller->getGameState($params), JSON_PRETTY_PRINT);
            } else {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_PRETTY_PRINT);
            }
            break;
            
     case 'game/list':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                require_once 'controllers/gamecontrol.php';
                $controller = new gamecontrol($db);
                echo json_encode($controller->getAvailableGames(), JSON_PRETTY_PRINT);
            } else {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_PRETTY_PRINT);
            }
            break;
        
    case 'move/make':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                require_once 'controllers/movecontrol.php';
                $controller = new movecontrol($db);
                echo json_encode($controller->makeMove($input), JSON_PRETTY_PRINT);
            } else {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_PRETTY_PRINT);
            }
            break;
            
    case 'move/possible':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                require_once 'controllers/movecontrol.php';
                $controller = new movecontrol($db);
                echo json_encode($controller->getPossibleMoves($params), JSON_PRETTY_PRINT);
            } else {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_PRETTY_PRINT);
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
          } } 
catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?>