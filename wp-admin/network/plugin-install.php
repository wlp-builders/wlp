<?php
/**
 * Install plugin network administration panel.
 *
 * @package WLP
 * @subpackage Multisite
 * @since 3.1.0
 */

if ( isset( $_GET['tab'] ) && ( 'plugin-information' === $_GET['tab'] ) ) {
	define( 'IFRAME_REQUEST', true );
}

/** Load WLP Administration Bootstrap */
require_once __DIR__ . '/admin.php';

require ABSPATH . 'wp-admin/plugin-install.php';
