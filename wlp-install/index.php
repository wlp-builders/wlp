<?php
// Check if wp-config is already installed
if (file_exists(__DIR__ . '/../wlp-config.php')) {
    die('wlp-config is already created.');
    exit;
}

require_once __DIR__.'/php-verifyInstallKey.php';

// protocol fix for when you're behind mod_proxy, ex. using Podman container
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    // Set $_SERVER['HTTPS'] to 'on' to emulate HTTPS environment
    $_SERVER['HTTPS'] = 'on';
    
    // Adjust other related variables as needed
    $_SERVER['SERVER_PORT'] = 443; // Standard HTTPS port
    $_SERVER['REQUEST_SCHEME'] = 'https'; // Define scheme if your app uses it
}

$domain = $_SERVER['SERVER_NAME'];
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
if($secure) {
    $url = 'https://'.$domain;
} else {
    $url = 'http://'.$domain;
}

/*
 // for core devs to debug
 * var_dump($url);
var_dump($_SERVER);
die();
 */

// generate placeholder suggestive names
// Get the server name
$serverName = $_SERVER['SERVER_NAME'] ?? 'domain.tld';

// Replace dots in the server name with underscores to create valid identifiers
$processedName = str_replace('.', '__', $serverName);

// Define variables based on the processed server name
$placeholder_dbName = $processedName . '_db';
$placeholder_dbUser = $processedName . '_user';
//////

if(!isset($_POST['submit']) && !isset($_GET['install_key'])) {
    //var_dump('submit not isset');
    exit;
}



if(isset($_GET['install_key'])) {
	$valid = verifyInstallKey($_GET['install_key']);
	if(!$valid) {
		die('install-key not valid for install via $_GET.');
	}

	$db_name = $_GET['db_name']; // The name of your database
	$db_user = $_GET['db_user']; // Database username
	$db_pass = $_GET['db_pass']; // Database password

} else {
	// assume post
	// Database connection variables (you may want to customize these)
	$db_name = $_POST['db_name']; // The name of your database
	$db_user = $_POST['db_user']; // Database username
	$db_pass = $_POST['db_pass']; // Database password
}


$db_host = 'localhost'; // Database host, usually 'localhost'

$prefix = 'wlp_'; // Table prefix

// Securely generate the authentication keys and salts
$JWT_KEY =   bin2hex(random_bytes(64));
$AUTH_KEY =   bin2hex(random_bytes(32));
$SECURE_AUTH_KEY= bin2hex(random_bytes(32));
$LOGGED_IN_KEY= bin2hex(random_bytes(32));
$NONCE_KEY=   bin2hex(random_bytes(32));
$AUTH_SALT=     bin2hex(random_bytes(32));
$SECURE_AUTH_SALT= bin2hex(random_bytes(32));
$LOGGED_IN_SALT=   bin2hex(random_bytes(32));
$NONCE_SALT=      bin2hex(random_bytes(32));

// Content of the wp-config.php file
$wp_config_content = <<<EOL
<?php
// ** MySQL settings ** //
define( 'DB_NAME', '$db_name' );
define( 'DB_USER', '$db_user' );
define( 'DB_PASSWORD', '$db_pass' );
define( 'DB_HOST', '$db_host' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

// ** Authentication Unique Keys and Salts ** //
define( 'AUTH_KEY',         '$AUTH_KEY' );
define( 'SECURE_AUTH_KEY',  '$SECURE_AUTH_KEY' );
define( 'LOGGED_IN_KEY',    '$LOGGED_IN_KEY' );
define( 'NONCE_KEY',        '$NONCE_KEY' );
define( 'AUTH_SALT',        '$AUTH_SALT' );
define( 'SECURE_AUTH_SALT', '$SECURE_AUTH_SALT' );
define( 'LOGGED_IN_SALT',   '$LOGGED_IN_SALT' );
define( 'NONCE_SALT',       '$NONCE_SALT' );

// The File Editor for plugins and themes is disabled (deprecated)
define( 'DISALLOW_FILE_EDIT', true );

// ** Absolute path to the WordPress directory ** //
if ( !defined('ABSPATH') )
    define( 'ABSPATH', __DIR__ . '/' );

// essential setup 
define('WLP_JWT_SECRET','$JWT_KEY'); // (64 byte key)

\$localCoreDevTestMode = false; // set to false in production, if enabled logs to app.errors.log & app.debug.log + more dev freedom, less security
if(\$localCoreDevTestMode) {

    // WP ideal setup for core devs
    define( 'WP_DEBUG', true );

    // Log errors to app.errors.log file in the root directory (same as ABSPATH)
    define( 'WP_DEBUG_LOG', ABSPATH . 'app.errors.log' );

    // Disable error display on the frontend (ideal for production environments)
    define( 'WP_DEBUG_DISPLAY', false );

    // Set the PHP error reporting level to show all errors, including notices and warnings
    error_reporting( E_ALL );

    // Ensure that errors are logged, even if they are not displayed
    @ini_set( 'display_errors', 0 );

    // WLP ideal setup for core devs
    define('WLP_ALLOW_OWN_DID_REQUESTS', true); // Set to false when you don't want to allow own DID requests
    define('WLP_DISABLE_POW_PROTECTION_REQUESTS', true); // Set to false when you don't want to allow own DID requests
    define('WLP_LOGGER_FILE',__DIR__.'/app.debug.log');
    define('WLP_HEADLESS_CORS',1);// Enable Cross Domain Requests for Headless Apps
    define('WLP_ENABLE_IFRAME',1); // Disable SAMEORIGIN
} else {
    define( 'WP_DEBUG', false );
}

EOL;

// Write the wp-config.php file
$res = file_put_contents(__DIR__ . '/../wlp-config.php', $wp_config_content);
//var_dump($res);

// WLP CONFIG

// admin creds
$username = 'admin';  // defaults
$password = $db_pass; // defaults

$file = 'install.sql';


// Create an associative array with the input values
$db_config = [
    'db_host' => $db_host,
    'db_user' => $db_user,
    'db_pass' => $db_pass,
    'db_name' => $db_name,
];

// Output the JSON representation of the configuration
//echo "Input to mysqli:\n";
//echo json_encode($db_config, JSON_PRETTY_PRINT);


// Create a new connection to MySQL
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check if connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read the SQL file
$sql = file_get_contents($file);

$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Line Replacements go here..

$esc_username = "'".$conn->real_escape_string($username)."'";
$esc_password_hash = "'".$conn->real_escape_string($password_hash)."'";
$esc_domain = "'".$conn->real_escape_string($domain)."'";
$esc_domain_email = "'noreply@".$conn->real_escape_string($domain)."'";
$esc_url = "'".$conn->real_escape_string($url)."'";
$replacements = [
    [
    /// Replace default USER admin:admin
    '(1, \'admin\', \'$2y$12$hKYyZn5WRv0b6s4fXBcr/uJlQxxk9.EMe0iZJz/p8c.SVXxa9keha\', \'admin\', \'admin@wlp.local\', \'http://wlp1.local\', \'2024-12-12 15:00:59\', \'\', 0, \'admin\');',
    "(1, $esc_username, $esc_password_hash, $esc_username, $esc_domain_email, $esc_url, '2024-12-12 15:00:59', '', 0, $esc_username);"
    ],
    ['wlp1.local',$domain],
    ['http://'.$domain,$url],
    ['twentyseventeen','whitelabel24'],
];
//var_dump($replacements);
foreach($replacements as $r) {
    $sql = str_replace($r[0],$r[1],$sql);
}

// Check if the file was successfully read
if ($sql === false) {
    die("Error reading SQL file.");
}

//var_dump($sql);


// Execute the SQL commands
if ($conn->multi_query($sql)) {
    //echo "SQL file executed successfully.";
} else {
    die("Error executing SQL: " . $conn->error);
}

sleep(3); // just wait for queries to fully process
require_once __DIR__.'/../wlp-core/wlp-functions/wlp_jwt.php';
require_once __DIR__.'/../wlp-config.php';

// create login cookie here and redirect to wp_admin
$expiration = 72000; // 10 hours by default
$auth_cookie = wlp_jwt_sign(['user_id' => 1], WLP_JWT_SECRET,$expiration);
$cookie_options = array(
    'expires' => time() + $expiration,
    'path' => '',
    'domain' => $domain,
    'secure' => $secure,
    'httponly' => true,
);

// WLP JWT, simplied
$cookie_options['path'] = '/';
setcookie( 'jwt_token', $auth_cookie, $cookie_options );

header('Location: ../wp-admin');
exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installer WLP</title>
</head>
<body>


<style>
body{
background:black;
padding:3rem;
color:white;
}
a,li { color:white}
</style>
<link rel="stylesheet" id="style-css" href="/wp-content/themes/whitelabel-24/style.css?ver=cp_e3a3128e" media="all">

<h2>WLP - Easy 3 Step Install</h2>
<li>1. Installer opened! (completed)</li>
<li>2. Create your Database <br>or run <b>sudo bash wlp-install/autoinstall-as-root.sh</b></li>
<li>3. Connect Your Database:</li>
 

<form method="post">
<h5>Database Name</h5>
    <input type="text" value="<?php echo $placeholder_dbName;?>" placeholder="Db name.." name="db_name" />

    <h5>Database User</h5>
    <input type="text" value="<?php echo $placeholder_dbUser;?>" placeholder="Db user.." name="db_user" />

<h5>Database Password</h5>
    <input type="text" placeholder="Db password.." name="db_pass" />


    <input type="hidden" name="submit" value="1" />
    <button type="submit">Create Site</button>
</form>
</body>
</html>

