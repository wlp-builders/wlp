<?php
/**
 * Loads the WLP environment and template.
 *
 * @package WLP
 */

if ( ! isset( $wp_did_header ) ) {

	$wp_did_header = true;

	// Load the WLP library.
	require_once __DIR__ . '/wp-load.php';

	// Set up the WLP query.
	wp();

	// Load the theme template.
	require_once ABSPATH . WPINC . '/template-loader.php';

}
