<?php

/*
// test 
$token = wlp_jwt_sign(['user_id' => 1], 'secret', 3000000);  // 30000 seconds expiration
var_dump($token);

// this one should return the user obj with exp in int
$tokenv1 = wlp_jwt_verify($token, 'secret');
var_dump($tokenv1);
var_dump($tokenv1['user_id']);

// this one should be false
$tokenv2 = wlp_jwt_verify($token . 'x', 'secret');
var_dump($tokenv2);

// just double check that exp is not overwriteable by user
$token3 = wlp_jwt_sign(['user' => 'example',"exp"=>123], 'secret', 3000);  // 3000 seconds expiration
var_dump($token3);
$tokenv3 = wlp_jwt_verify($token3, 'secret');
var_dump($tokenv3); // should output longer exp than 123
//*/

function wlp_jwt_sign($payload, $secretKey, $expirationTime=72000) { // 20h expiration by default
  
    // Set expiration based on the argument
    $expirationTime = time() + $expirationTime; // Expiration time in seconds from now

    // Add the expiration claim to the payload
    $payload['exp'] = $expirationTime;

    // Header
    $header = [
        'alg' => 'HS512',
        'typ' => 'JWT'
    ];

    // Encode Header
    $encodedHeader = _wlp_jwt_base64UrlEncode(json_encode($header));

    // Encode Payload
    $encodedPayload = _wlp_jwt_base64UrlEncode(json_encode($payload));

    // Create the signature
    $signature = _wlp_jwt_base64UrlEncode(hash_hmac('sha512', "$encodedHeader.$encodedPayload", $secretKey, true));

    // Concatenate the parts
    $jwt = "$encodedHeader.$encodedPayload.$signature";

    return $jwt;
}


function wlp_jwt_verify($jwt, $secretKey) {
  
    // just in case trim the payload
    $jwt = trim($jwt);
  
    // Split the JWT into its parts
    $parts = explode('.', $jwt);

    if (count($parts) !== 3) {
        return false; // Invalid JWT format
    }

    list($encodedHeader, $encodedPayload, $signature) = $parts;

    // Verify the signature
    $expectedSignature = _wlp_jwt_base64UrlEncode(hash_hmac('sha512', "$encodedHeader.$encodedPayload", $secretKey, true));

    if ($signature !== $expectedSignature) {
        return false; // Signature does not match
    }

    // Decode the payload and check the expiration
    $decodedPayload = json_decode(_wlp_jwt_base64UrlDecode($encodedPayload), true);

    // Check if the token is expired
    if (isset($decodedPayload['exp']) && time() > $decodedPayload['exp']) {
        return false; // Token has expired
    }

    return $decodedPayload;
}

// Helper function to base64Url decode data
function _wlp_jwt_base64UrlDecode($data) {
    $padding = strlen($data) % 4;
    if ($padding) {
        $data .= str_repeat('=', 4 - $padding);
    }

    return base64_decode(strtr($data, '-_', '+/'));
}

// Helper function to base64Url encode data
function _wlp_jwt_base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
