<?php
/**
 * Credits administration panel.
 *
 * @package WLP
 * @subpackage Administration
 */

/** WLP Administration Bootstrap */
require_once __DIR__ . '/admin.php';

// Used in the HTML title tag.
$title = __( 'Credits' );

list( $display_version ) = explode( '-', get_bloginfo( 'version' ) );

require_once ABSPATH . 'wp-admin/admin-header.php';

?>
<div class="wrap about-wrap full-width-layout">

<h1><?php _e( 'Welcome to WLP!' ); ?></h1>

<p class="about-text">
	<?php printf( __( 'Version %s' ), classicpress_version() ); ?>
	<?php classicpress_dev_version_info(); ?>
</p>
<p class="about-text">
	<?php
	printf(
		/* translators: link to WLP website */
		__( 'Thank you for using WLP, the <a href="%s">CMS for Creators & Hosts</a>.' ),
		'https://whitelabelpress.org'
	);
	?>
	<br>
	<?php _e( 'Stable. Lightweight. Instantly Familiar.' ); ?>
</p>

<div class="wp-badge"></div>

<h2 class="nav-tab-wrapper wp-clearfix">
	<a href="about.php" class="nav-tab"><?php _e( 'About' ); ?></a>
	<a href="credits.php" class="nav-tab nav-tab-active"><?php _e( 'Credits' ); ?></a>
	<a href="freedoms.php" class="nav-tab"><?php _e( 'Freedoms' ); ?></a>
	<a href="freedoms.php?privacy-notice" class="nav-tab"><?php _e( 'Privacy' ); ?></a>
</h2>

<div class="about-wrap-content">
<?php

echo '<p class="about-description">' . sprintf(
	__( 'WLP is created by a <a href="%1$s">worldwide team</a> of passionate individuals about building the best open-source plugin ecosystem.' ),
	'https://whitelabelpress.org/'
) . '</p>';

echo '<p class="about-description">' . sprintf(
	__( 'Interested in helping out with development? <a href="%s">Get involved in WLP</a>.' ),
	'https://whitelabelpress.org/'
) . '</p>';

?>
</div>
</div>
<?php

require ABSPATH . 'wp-admin/admin-footer.php';

return;
