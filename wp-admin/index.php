<?php
// just redirect to admin.php with core plugin ('dashboard2' by default)
require_once __DIR__ . '/../wp-load.php';
$redirect_url = admin_url( 'admin.php?page=dashboard2' );
wp_redirect( $redirect_url );
exit;