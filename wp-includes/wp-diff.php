<?php
/**
 * WLP Diff bastard child of old MediaWiki Diff Formatter.
 *
 * Basically all that remains is the table structure and some method names.
 *
 * @package WLP
 * @subpackage Diff
 */

if ( ! class_exists( 'Text_Diff', false ) ) {
	/** Text_Diff class */
	require_once ABSPATH . WPINC . '/Text/Diff.php';
	/** Text_Diff_Renderer class */
	require_once ABSPATH . WPINC . '/Text/Diff/Renderer.php';
	/** Text_Diff_Renderer_inline class */
	require_once ABSPATH . WPINC . '/Text/Diff/Renderer/inline.php';
}

require_once ABSPATH . WPINC . '/class-wp-text-diff-renderer-table.php';
require_once ABSPATH . WPINC . '/class-wp-text-diff-renderer-inline.php';
