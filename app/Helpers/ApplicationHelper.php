<?php


function sendResponse($status, $status_code, $message, $data, $code){
    return response()->json([
        'status'   => $status,
        'status_code'   => $status_code,
        'message'   => $message,
        'data'      => $data,
    ], $code);
}


function generateClientId($account_id)
{
    $account_id = strval($account_id);
    $acc = '';
    if (strlen($account_id) == 1) {
        $acc = 'FM100' . $account_id;
    } elseif (strlen($account_id) == 2) {
        $acc = 'FM10' . $account_id;
    } elseif (strlen($account_id) == 3) {
        $acc = 'FM1' . $account_id;
    } elseif (strlen($account_id) == 4) {
        $acc = 'FM' . $account_id;
    }
    return $acc;
}

function encrypt_value($value)
{
    $ciphering = "AES-128-CTR";
    $options = 0;
    $encryption_iv = '1234567891011121';
    $encryption_key = 'H%$^&%!@)(*)^%0';
    $value = openssl_encrypt($value, $ciphering, $encryption_key, $options, $encryption_iv);
    $value = str_replace('/', '_', $value);
    return $value;
}

function decrypt_value($value)
{
    $ciphering = "AES-128-CTR";
    $options = 0;
    $encryption_iv = '1234567891011121';
    $encryption_key = 'H%$^&%!@)(*)^%0';
    $value = str_replace('_', '/', $value);
    $value = openssl_decrypt($value, $ciphering, $encryption_key, $options, $encryption_iv);
    return $value;
}