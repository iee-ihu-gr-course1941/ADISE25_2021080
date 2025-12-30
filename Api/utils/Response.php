<?php
class Response {
    public static function success($data = [], $message = 'Success') {
        return [
            'status' => 'Success',
            'message' => $message,
            'timestamp' => time(),
            'data' => $data
        ];
    }
    
    public static function error($message = 'Error', $code = 400, $details = []) {
        http_response_code($code);
        return [
            'status' => 'Error',
            'message' => $message,
            'timestamp' => time(),
            'code' => $code,
            'details' => $details
        ];
    }
    
    public static function validationError($errors) {
        return self::error('Validation Failed', 422, ['Errors' => $errors]);
    }
}
?>