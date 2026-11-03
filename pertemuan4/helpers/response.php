<?php

function sendResponse($success, $message, $data = null, $code = 200)
{
    http_response_code($code);
    header("Content-Type: application/json");

    echo json_encode([
        "status" => $success,
        "message" => $message,
        "data" => $data
    ]);

    exit();
}