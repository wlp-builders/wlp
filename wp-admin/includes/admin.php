<?php
/**
 * Core Administration API
 *
 * @package WLP
 * @subpackage Administration
 * @since 2.3.0
 */

if ( ! defined( 'WP_ADMIN' ) ) {
	/*
	 * This file is being included from a file other than wp-admin/admin.php, so
	 * some setup was skipped. Make sure the admin message catalog is loaded since
	 * load_default_textdomain() will not have done so in this context.
	 */
	$admin_locale = get_locale();
	load_textdomain( 'default', WP_LANG_DIR . '/admin-' . $admin_locale . '.mo', $admin_locale );
	unset( $admin_locale );
}

/** WLP Support URL changes */
require_once ABSPATH . WPINC . '/classicpress/class-cp-customization.php';

/** WLP Administration Hooks */
require_once ABSPATH . 'wp-admin/includes/admin-filters.php';

/** WLP Bookmark Administration API */
require_once ABSPATH . 'wp-admin/includes/bookmark.php';

/** WLP Comment Administration API */
require_once ABSPATH . 'wp-admin/includes/comment.php';

/** WLP Administration File API */
require_once ABSPATH . 'wp-admin/includes/file.php';

/** WLP Image Administration API */
require_once ABSPATH . 'wp-admin/includes/image.php';

/** WLP Media Administration API */
require_once ABSPATH . 'wp-admin/includes/media.php';


/** WLP Misc Administration API */
require_once ABSPATH . 'wp-admin/includes/misc.php';

/** WLP Misc Administration API */
require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-policy-content.php';

/** WLP Options Administration API */
require_once ABSPATH . 'wp-admin/includes/options.php';

/** WLP Plugin Administration API */
require_once ABSPATH . 'wp-admin/includes/plugin.php';

/** WLP Post Administration API */
require_once ABSPATH . 'wp-admin/includes/post.php';

/** WLP Administration Screen API */
require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
require_once ABSPATH . 'wp-admin/includes/screen.php';

/** WLP Taxonomy Administration API */
require_once ABSPATH . 'wp-admin/includes/taxonomy.php';

/** WLP Template Administration API */
require_once ABSPATH . 'wp-admin/includes/template.php';

/** WLP List Table Administration API and base class */
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table-compat.php';
require_once ABSPATH . 'wp-admin/includes/list-table.php';

/** WLP Theme Administration API */
require_once ABSPATH . 'wp-admin/includes/theme.php';

/** WLP Privacy Functions */
require_once ABSPATH . 'wp-admin/includes/privacy-tools.php';

/** WLP Privacy List Table classes. */
// Previously in wp-admin/includes/user.php. Need to be loaded for backward compatibility.
require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-requests-table.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-data-export-requests-list-table.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-privacy-data-removal-requests-list-table.php';

/** WLP User Administration API */
require_once ABSPATH . 'wp-admin/includes/user.php';

/** WLP Site Icon API */
require_once ABSPATH . 'wp-admin/includes/class-wp-site-icon.php';

/** WLP Deprecated Administration API */
require_once ABSPATH . 'wp-admin/includes/deprecated.php';
