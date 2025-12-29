<?php
class Response {
    public function send($data, $status_code = 200) {
        http_response_code($status_code);
        return json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => time()
        ]);
    }
    
    public function sendError($message, $status_code = 400) {
        http_response_code($status_code);
        return json_encode([
            'success' => false,
            'error' => $message,
            'timestamp' => time()
        ]);
    }
}
?>