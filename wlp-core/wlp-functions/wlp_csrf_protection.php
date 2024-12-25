<?php
require_once('wlp_get_jwt.php' );

/*
// test
try {
  $time = time() + 3600; // 60 minutes valid
  _wlp_csrf_protection('goodhashjwt',wlp_csrf_protection_hash('goodhashjwt',$time),$time);
} catch(Exception $err) {
  echo 'Bad Error '.$err.PHP_EOL;
}

// test bad expired
try {
  $time = time() - 100;
  _wlp_csrf_protection('goodhashjwt',wlp_csrf_protection_hash('goodhashjwt',$time),$time);
} catch(Exception $err) {
  echo 'Good Error '.$err.PHP_EOL;
}

// test bad wrong hash
try {
  $time = time() + 3600; // 60 minutes valid
  _wlp_csrf_protection('badhashjwt','badhash',$time);
} catch(Exception $err) {
  echo 'Good Error '.$err.PHP_EOL;
}



//wlp_csrf_protection_checker();
//*/

// jwt = token, time=time when expired (ex. time() + 3600 = 1h valid)
function wlp_csrf_protection_hash($jwt_token,$time) {
  // sha3 + hmac to prevent length extension attacks
  // SHA-3 uses the Keccak sponge construction, which is inherently resistant to length extension attacks
  // HMAC (Hash-based Message Authentication Code) adds an extra layer of security by combining the hash function (like SHA-3) with a secret key in a way that makes it resistant to tampering.
  // Last value time is also casted to int to further prevent length extension attacks
  
  $action = _get_full_url(); // URL is used as action. This ensures that even if the csrf_hash is compromised through XSS, then it only would be valid for one url, not site wide.
  return hash('sha3-512', $action.$jwt_token.(int)$time);
}

// Helper function to get the full URL
function _get_full_url() {
  $host = $_SERVER['HTTP_HOST'] ?? '';
  $uri = $_SERVER['REQUEST_URI'] ?? '';
  $result = $host . $uri;

  // Replace core action URLs for where there are redirects
  if(strpos($result, '/wp-admin/post')) {
    $result = 'action:wp-post.php';
  }

  // Replace core action URLs for where there are redirects
  if(strpos($result, '/wp-admin/edit-tags') || 
  strpos($result, '/wp-admin/admin-ajax')) {
    $result = 'action:admin-ajax.php';
  }

  // Replace core action URLs for where there are redirects
  if(strpos($result, '/wp-admin/media-new') || 
  strpos($result, '/wp-admin/async-upload')) {
    $result = 'action:async-upload.php';
  }


  return $result;
}

function wlp_csrf_protection_checker() {
  $requestMethod = $_SERVER["REQUEST_METHOD"] ?? 'GET';
  if($requestMethod !== "GET") {
    $jwt = wlp_get_jwt();
    $hash = $_POST['wlp_csrf_hash'] ?? '';
    $time = $_POST['wlp_csrf_time'] ?? '0';
    $time = (int)$time;
    try { 
      _wlp_csrf_protection($jwt,$hash,$time);
    } catch(Exception $err) {
      wlp_csrf_error();
      /*
      // for WLP core devs: uncomment if needed to debug
      var_dump('Exception received post:');
      var_dump($_POST);
      var_dump('_get_full_url:');
      var_dump(_get_full_url());
      var_dump('jwt:');
      var_dump($jwt);
      var_dump(' _wlp_get_jwt_type():');
      var_dump( _wlp_get_jwt_type());
      var_dump($err->getMessage());
      */
      die();
    }
  }
}


function wlp_csrf_error() {
   http_response_code(403);
   die('The link you followed has expired.');
}



// helper functions

// Helper function to check 
function _wlp_csrf_protection($jwt,$hash,$time) {
  if(wlp_csrf_protection_hash($jwt,$time) !== $hash) {
    throw new Exception('CSRF protection: Hash does not match.');
  }

  if($time < time()) {
    throw new Exception('CSRF protection: Hash expired.');
  }
}

