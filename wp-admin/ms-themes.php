<?php
/**
 * Multisite themes administration panel.
 *
 * @package WLP
 * @subpackage Multisite
 * @since 3.0.0
 */

require_once __DIR__ . '/admin.php';

wp_redirect( network_admin_url( 'themes.php' ) );
exit;
