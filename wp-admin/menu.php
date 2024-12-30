<?php
/**
 * Build Administration Menu.
 *
 * @package WLP
 * @subpackage Administration
 */

/**
 * Constructs the admin menu.
 *
 * The elements in the array are:
 *     0: Menu item name.
 *     1: Minimum level or capability required.
 *     2: The URL of the item's file.
 *     3: Page title.
 *     4: Classes.
 *     5: ID.
 *     6: Icon for top level menu.
 *
 * @global array $menu
 */



$menu[4] = array( '', 'read', 'separator1', '', 'wp-menu-separator' );

// $menu[5] = Posts.


$menu[15]                           = array( __( 'Links' ), 'manage_links', 'link-manager.php', '', 'menu-top menu-icon-links', 'menu-links', 'dashicons-admin-links' );
	$submenu['link-manager.php'][5] = array( _x( 'All Links', 'admin menu' ), 'manage_links', 'link-manager.php' );
	/* translators: Add new links. */
	$submenu['link-manager.php'][10] = array( _x( 'Add New', 'link' ), 'manage_links', 'link-add.php' );
	$submenu['link-manager.php'][15] = array( __( 'Link Categories' ), 'manage_categories', 'edit-tags.php?taxonomy=link_category' );

// $menu[20] = Pages.

$_wp_last_object_menu = 25; // The index of the last top-level menu in the object menu group.


$menu[59] = array( '', 'read', 'separator2', '', 'wp-menu-separator' );


/**
 * Adds the (theme) 'Editor' link to the bottom of the Appearance menu.
 *
 * @access private
 * @since 3.0.0
 */
function _add_themes_utility_last() {
	// Must use API on the admin_menu hook, direct modification is only possible on/before the _admin_menu hook
	add_submenu_page( 'themes.php', _x( 'Editor', 'theme editor' ), _x( 'Editor', 'theme editor' ), 'edit_themes', 'theme-editor.php' );
}

$count = '';

/* translators: %s: Number of available plugin updates. */
//$menu[65] = array( sprintf( __( 'Plugins %s' ), $count ), 'activate_plugins', 'plugins.php', '', 'menu-top menu-icon-plugins', 'menu-plugins', 'dashicons-admin-plugins' );

// <! WLP: Removed Default Plugins Menu for the Decentralized Plugin System  -->
//$submenu['plugins.php'][5] = array( __( 'Installed Plugins' ), 'activate_plugins', 'plugins.php' );



$_wp_last_utility_menu = 80; // The index of the last top-level menu in the utility menu group.

$menu[99] = array( '', 'read', 'separator-last', '', 'wp-menu-separator' );

// Back-compat for old top-levels.
$_wp_real_parent_file['post.php']       = 'edit.php';
$_wp_real_parent_file['post-new.php']   = 'edit.php';
$_wp_real_parent_file['edit-pages.php'] = 'edit.php?post_type=page';
$_wp_real_parent_file['page-new.php']   = 'edit.php?post_type=page';
$_wp_real_parent_file['wpmu-admin.php'] = 'tools.php';
$_wp_real_parent_file['ms-admin.php']   = 'tools.php';

// Ensure backward compatibility.
$compat = array(
	'index'           => 'dashboard',
	'edit'            => 'posts',
	'post'            => 'posts',
	'upload'          => 'media',
	'link-manager'    => 'links',
	'edit-pages'      => 'pages',
	'page'            => 'pages',
	'options-general' => 'settings',
	'themes'          => 'appearance',
);

require_once ABSPATH . 'wp-admin/includes/menu.php';
