<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/htaccess.php';
umex_hardening_write_htaccess( false );

delete_option( 'umex_hardening' );
delete_option( 'umex_hardening_csp_reports' );
