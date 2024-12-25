<?php
/**
 * Themes administration panel.
 *
 * @package WLP
 * @subpackage Administration
 */

/** WLP Administration Bootstrap */
require_once __DIR__ . '/admin.php';

// Redirect to the plugins page
wp_redirect( admin_url( 'plugins.php' ) );
exit;

