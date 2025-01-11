<?php
define('WLP_DEV_MODE',1);
// Ensure the WLP environment is loaded.

/**
 * Easily instant test plugins by running a custom anonymous function in the WLP environment.
 *
 * @param callable $callback The custom logic to execute.
 */
function wlp_instant_test(callable $callback) {
    require_once __DIR__.'../../wp-load.php';

    $token = wlp_instant_test_get_token();

    // Set token for request
    $_SERVER['HTTP_WLP_AUTHORIZATION'] = 'Bearer '.$token;
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['PHP_SELF'] = '/';


    // Execute the callback in the WLP environment.
    $callback();

    // Hook into the 'send_headers' action and set priority to 1 (earliest possible)
    add_action('wlp_admin_header', '_force_remove_x_frame_options_and_fix_cors', 1);

    // Render the WordPress site as usual.
    require_once __DIR__.'../../index.php';
}

function wlp_instant_test_get_token(){
  /// in the admin-header.php
  require_once(__DIR__.'../../wlp-core/wlp-functions/wlp_jwt.php' );
  require_once(__DIR__.'../../wlp-core/wlp-functions/wlp_get_jwt.php' );
  require_once(__DIR__.'../../wlp-config.php' );

  // create login cookie here and redirect to wp_admin
  $expiration = 72000; // 10 hours by default
  $token = wlp_jwt_sign(['user_id' => 1], WLP_JWT_SECRET,$expiration);
  return $token;
}

// tokenOutputFile is used for storing the admin token somewhere, and re-using it, otherwise the CSRF protection will block when submitting a form ($_POST)
function wlp_instant_test_admin($callback=null,$page='dashboard2') {
  if(!empty($page)) {
    $_GET['page'] = $page;
  }

  $token = wlp_instant_test_get_token();

  // Set token for request
  $_SERVER['HTTP_WLP_AUTHORIZATION'] = 'Bearer '.$token;
  $_SERVER['HTTP_HOST'] = 'localhost:8080';
  $_SERVER['PHP_SELF'] = '/wp-admin/admin.php';

  /// in the admin-header.php
  require_once(__DIR__.'../../wlp-core/wlp-functions/wlp_jwt.php' );
  require_once(__DIR__.'../../wlp-core/wlp-functions/wlp_get_jwt.php' );
  require_once(__DIR__.'../../wlp-config.php' );



  


  require_once(__DIR__.'../../wp-load.php' ); 

  /*
  var_dump(json_encode(["token"=>$token]));
  var_dump(wlp_get_jwt());
  var_dump(_wlp_get_jwt_type());
   
  $tokenMethod = _wlp_get_jwt_type(); // double check token method
  var_dump($tokenMethod);
  var_dump(wp_get_current_user());
  die();
  //*/


  // Hook into the 'send_headers' action and set priority to 1 (earliest possible)
  add_action('wlp_admin_header', '_force_remove_x_frame_options_and_fix_cors', 1);

  if($callback) {
    $callback();
  }
  


  require_once(__DIR__.'../../wp-admin/admin.php' );


  
}


    // Remove X-Frame-Options and fix CORS early in the request cycle
    function _force_remove_x_frame_options_and_fix_cors() {
      header('X-Frame-Options:');  // This sets the header to an empty value

            // Allow requests from any origin
      header("Access-Control-Allow-Origin: *");

      // Allow specific HTTP methods
      header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

      // Allow specific headers
      header("Access-Control-Allow-Headers: Content-Type, Authorization");

      // Allow credentials if needed
      // header("Access-Control-Allow-Credentials: true");

      // Handle preflight requests
      if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
          http_response_code(200);
          exit();
      }

    }
