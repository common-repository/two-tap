<?php

function tt_send_json_success_message($message, $data = null)
{
    $response = [
        'success' => true,
        'message' => $message
    ];
    if(!is_null($data)){
        $response['data'] = $data;
    }
    return wp_send_json($response);
}

function tt_send_json_error_message($message, $data = null)
{
    $response = [
        'success' => false,
        'message' => $message
    ];
    if(!is_null($data)){
        $response['data'] = $data;
    }
    return wp_send_json($response);
}