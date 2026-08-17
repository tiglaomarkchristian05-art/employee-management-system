<?php

class Controller {
    protected function view($viewPath, $data = []) {
        extract($data);
        $file = APP_PATH . "Views/{$viewPath}.php";
        if (file_exists($file)) {
            require $file;
        } else {
            die("View file not found: Views/{$viewPath}.php");
        }
    }

    protected function json($status, $message = '', $data = [], $httpCode = 200) {
        json_response($status, $message, $data, $httpCode);
    }
}
