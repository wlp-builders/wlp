<?php
// test
/*
// simple test with nothing set, expect cookie method and value
$token = wlp_get_jwt();
var_dump($token);
$tokenMethod = _wlp_get_jwt_type();
var_dump($tokenMethod);

// simple test with $_POST set, expect post method and no value
$_SERVER["REQUEST_METHOD"] = "POST";
$token = wlp_get_jwt();
var_dump($token);
$tokenMethod = _wlp_get_jwt_type();
var_dump($tokenMethod);

// simple test with $_POST set, expect post method and jwt_token value
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST['jwt_token'] = 'jwtgoeshere';
$token = wlp_get_jwt();
var_dump($token);
$tokenMethod = _wlp_get_jwt_type();
var_dump($tokenMethod);


// simple test with $_COOKIE set
$_SERVER["REQUEST_METHOD"] = "GET";
$_COOKIE['jwt_token'] = 'jwtgoeshere';
$token = wlp_get_jwt();
var_dump($token);
$tokenMethod = _wlp_get_jwt_type();
var_dump($tokenMethod);

// simple test with HTTP_AUTHORIZATION set
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["HTTP_AUTHORIZATION"] = "Bearer jwtgoeshereforheader";
$_COOKIE = [];
$token = wlp_get_jwt();
var_dump($token);
$tokenMethod = _wlp_get_jwt_type();
var_dump($tokenMethod);
//*/

/**
 * Retrieve the JWT for the current request.
 *
 * Logic:
 * - For all requests:
 *   1. First, check for the Authorization header (`Bearer <token>`).
 *   2. If not present check the cookie (`auth_token`).
 * - Return null if no valid token is found.
 *
 * Note:
 * - POST requests require jwt_token_hash to prevent CSRF vulnerabilities.
 * - This approach ensures flexibility while enforcing security rules for write operations.
 *
 * @return string|null The JWT token if found, or null otherwise.
 */
function wlp_get_jwt() {
    return _wlp_get_jwt_type()[1];
}

// helper function
function _wlp_get_jwt_type() {
    // Check for Authorization header, this is for headless apps
    if(isset($_SERVER['HTTP_WLP_AUTHORIZATION'])) {
      if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_WLP_AUTHORIZATION'], $matches)) {
          return ['Header WLP_AUTHORIZATION', $matches[1]]; // Return token from Authorization header
      } else {
          return ['Header WLP_AUTHORIZATION', null]; // Return empty if auth header doesnt match Bearer JWT pattern
      }
    }
    
    else {
      // Fallback to cookie-based token for non-POST requests
      if (!empty($_COOKIE['jwt_token'])) {
          return ['COOKIE jwt_token', $_COOKIE['jwt_token']]; // Return token from cookie
      } else {
         return ['COOKIE jwt_token', null]; // Return token from cookie
      }
    }
}