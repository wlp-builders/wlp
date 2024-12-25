<?php
/**
 * Tells WLP to load the WLP theme and output it.
 *
 * @var bool
 */
define( 'WP_USE_THEMES', true );

// <-- WLP: Force the file system method to direct, no ftp, always support deletion own plugins
define('FS_METHOD', 'direct');

/** Loads the WLP Environment and Template */
require __DIR__ . '/wp-blog-header.php';
