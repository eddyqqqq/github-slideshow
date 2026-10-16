<?php
/**
 * Plugin Name:       UMEX Hardening
 * Description:       Închide punctele din raportul de securitate: XML-RPC, enumerarea utilizatorilor prin REST API, acces extern la wp-cron.php și header-ele de securitate (HSTS, nosniff, X-Frame-Options, CSP).
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            UMEX
 * License:           GPL-2.0-or-later
 * Text Domain:       umex-hardening
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UMEX_HARDENING_VERSION', '1.0.0' );
define( 'UMEX_HARDENING_FILE', __FILE__ );
define( 'UMEX_HARDENING_DIR', plugin_dir_path( __FILE__ ) );
define( 'UMEX_HARDENING_OPTION', 'umex_hardening' );

require_once UMEX_HARDENING_DIR . 'includes/options.php';
require_once UMEX_HARDENING_DIR . 'includes/htaccess.php';
require_once UMEX_HARDENING_DIR . 'includes/hardening.php';

// Requests to xmlrpc.php and wp-cron.php are rejected as early as possible,
// while plugins are still loading, so the rest of WordPress never boots for them.
umex_hardening_early_gate();

umex_hardening_register_runtime_hooks();

if ( is_admin() ) {
	require_once UMEX_HARDENING_DIR . 'includes/admin.php';
}

register_activation_hook( __FILE__, 'umex_hardening_activate' );
register_deactivation_hook( __FILE__, 'umex_hardening_deactivate' );

function umex_hardening_activate() {
	$current = get_option( UMEX_HARDENING_OPTION, null );
	$options = umex_hardening_sanitize_options( is_array( $current ) ? $current : umex_hardening_defaults() );
	update_option( UMEX_HARDENING_OPTION, $options );

	if ( $options['htaccess_rules'] ) {
		umex_hardening_write_htaccess( true, $options );
	}
}

function umex_hardening_deactivate() {
	umex_hardening_write_htaccess( false );
}
