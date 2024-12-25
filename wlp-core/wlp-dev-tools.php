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

    // Execute the callback in the WLP environment.
    $callback();

    // Hook into the 'send_headers' action and set priority to 1 (earliest possible)
    add_action('wlp_admin_header', '_force_remove_x_frame_options_and_fix_cors', 1);

    // Render the WordPress site as usual.
    require_once __DIR__.'../../index.php';
}

// tokenOutputFile is used for storing the admin token somewhere, and re-using it, otherwise the CSRF protection will block when submitting a form ($_POST)
function wlp_instant_test_admin($page='',$callback=null,$tokenOutputFile=null) {
  if(!empty($page)) {
    $_GET['page'] = $page;
  }

  /// in the admin-header.php
  require_once(__DIR__.'../../wlp-core/wlp-functions/wlp_jwt.php' );
  require_once(__DIR__.'../../wlp-core/wlp-functions/wlp_get_jwt.php' );
  require_once(__DIR__.'../../wlp-config.php' );

  if(empty($tokenOutputFile)) {
    throw new Exception('Please set a tokenOutputFile, so the admin token can be stored in a secure private place.');
  }

  // Generate long token for development if not exist
  /// to regenerate simply remove file in $tokenOutputFile
  if(!file_exists($tokenOutputFile)) {
    $generated_token = wlp_jwt_sign(['user_id' => 1], WLP_JWT_SECRET, 6048000); // 6048000=10 week expiration for development
    file_put_contents($tokenOutputFile,$generated_token);
  }

  $token = file_get_contents($tokenOutputFile);

  // Set token for request
  $_SERVER['HTTP_WLP_AUTHORIZATION'] = 'Bearer '.$token;
  $_SERVER['HTTP_HOST'] = 'localhost:8080';

  
  //var_dump(wlp_get_jwt());
  //var_dump(_wlp_get_jwt_type());
  // double check token method
  //$tokenMethod = _wlp_get_jwt_type();
  //var_dump($tokenMethod);
  //var_dump(wp_get_current_user());
  //die();


  require_once(__DIR__.'../../wp-load.php' ); 


// Hook into the 'send_headers' action and set priority to 1 (earliest possible)
add_action('wlp_admin_header', '_force_remove_x_frame_options_and_fix_cors', 1);

  if($callback) {
    $callback();
  }
  


  require_once(__DIR__.'../../wp-admin/index.php' );


  
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
