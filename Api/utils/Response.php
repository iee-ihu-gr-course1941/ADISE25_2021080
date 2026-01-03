<?php
class Response {
    public function send($data) {
        return [
            'status' => 'success',
            'data' => $data,
            'timestamp' => date('D-m-y H:i:s')
        ];
    }
    
    public function sendError($message, $code = 400) {
        return [
            'status' => 'error',
            'message' => $message,
            'code' => $code,
            'timestamp' => date('D-m-y H:i:s')
        ];
    }
}
?>