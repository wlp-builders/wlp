<?php
/**
 * WLP Administration Template Footer
 *
 * @package WLP
 * @subpackage Administration
 */

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * @global string $hook_suffix
 */
global $hook_suffix;
?>

<div class="clear"></div></div><!-- wpbody-content -->
<div class="clear"></div></div><!-- wpbody -->
<div class="clear"></div></div><!-- wpcontent -->

<div id="wpfooter" role="contentinfo">
	<?php
	/**
	 * Fires after the opening tag for the admin footer.
	 *
	 * @since 2.5.0
	 */
	do_action( 'in_admin_footer' );
	?>
	<p id="footer-left" class="alignleft">
		<?php
			$text = ''; // <-! WLP removed reference to creating with link -->
			echo apply_filters( 'admin_footer_text', '<span id="footer-thankyou">' . $text . '</span>' );
		?>
	</p>
	<p id="footer-upgrade" class="alignright">
		<?php
		// <!-- WLP doesnt show version in the footer either, only on update page -->
		//echo apply_filters( 'update_footer', '' );
		?>
	</p>
	<div class="clear"></div>
</div>
<?php
/**
 * Prints scripts or data before the default footer scripts.
 *
 * @since 1.2.0
 *
 * @param string $data The data to print.
 */
do_action( 'admin_footer', '' );

/**
 * Prints scripts and data queued for the footer.
 *
 * The dynamic portion of the hook name, `$hook_suffix`,
 * refers to the global hook suffix of the current page.
 *
 * @since 4.6.0
 */
do_action( "admin_print_footer_scripts-{$hook_suffix}" ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

/**
 * Prints any scripts and data queued for the footer.
 *
 * @since 2.8.0
 */
do_action( 'admin_print_footer_scripts' );

/**
 * Prints scripts or data after the default footer scripts.
 *
 * The dynamic portion of the hook name, `$hook_suffix`,
 * refers to the global hook suffix of the current page.
 *
 * @since 2.8.0
 */
do_action( "admin_footer-{$hook_suffix}" ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

// get_site_option() won't exist when auto upgrading from <= 2.7.
if ( function_exists( 'get_site_option' )
	&& false === get_site_option( 'can_compress_scripts' )
) {
	compression_test();
}

?>

<div class="clear"></div></div><!-- wpwrap -->

<script>if(typeof wpOnload==='function')wpOnload();</script>

<!-- WLP lastly include the JWT token in all admin forms for CSRF protection in $_POST 
this works because when we $_POST, the wlp_get_jwt() looks for jwt_token in $_POST not $_COOKIE or Auth Header. 
-->
<script>

document.addEventListener('DOMContentLoaded', function () {
    // Get the JWT from localStorage (you would have set this earlier in your app)
      // Lastly Add JWT to all forms on the page before submission
	  <?php 
	  require_once(__DIR__.'/../wlp-core/wlp-functions/wlp_csrf_protection.php');
	  $wlp_csrf_time = time() + 3600; // 60 minute valid
	  ?>
      document.querySelectorAll('form').forEach(function(form) {
              // Create a hidden input field for the JWT
              const jwtInput = document.createElement('input');
              jwtInput.type = 'hidden';
              jwtInput.name = 'wlp_csrf_hash';  // This is the name you'll use to read the hash on the PHP side
              jwtInput.value = "<?php echo wlp_csrf_protection_hash(wlp_get_jwt(),$wlp_csrf_time);?>";

              // Append the JWT input field to the form
              form.appendChild(jwtInput);

			  // Create a hidden input field for the JWT
              const jwtInput2 = document.createElement('input');
              jwtInput2.type = 'hidden';
              jwtInput2.name = 'wlp_csrf_time';  // This is the name you'll use to read the hash on the PHP side
              jwtInput2.value = "<?php echo $wlp_csrf_time;?>";

              // Append the JWT input field to the form
              form.appendChild(jwtInput2);
      });
});
</script>

</body>
</html>
