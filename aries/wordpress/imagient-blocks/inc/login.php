<?php
/**
 * The login screen at /cms. wp-login.php and wp-admin answer visitors who are not logged in with the site's 404 page
 * (they no longer point to where the login is), WordPress's own shortcuts (/login, /admin) are switched off, and every
 * link WordPress makes to wp-login.php (log out, lost password, the password reset email, a session that ran out in
 * the editor) is written to /cms. Password-protected pages still post to wp-login.php?action=postpass. Kept in the
 * plugin rather than the theme, so it holds whatever theme is active.
 */

defined( 'ABSPATH' ) || exit;

const ARIES_LOGIN_SLUG = 'cms';

/** The request's path below the site's own address, without slashes at its ends ("cms", "wp-login.php" …). */
function aries_login_request_path() {
	$path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( '' !== $home && 0 === strpos( $path, $home ) ) {
		$path = substr( $path, strlen( $home ) );
	}
	return trim( rawurldecode( $path ), '/' );
}

/** A link to wp-login.php, written to /cms (a password-protected page's form keeps going to wp-login.php). */
function aries_login_url( $url ) {
	if ( ! is_string( $url ) || false === strpos( $url, 'wp-login.php' ) || false !== strpos( $url, 'action=postpass' ) ) {
		return $url;
	}
	return str_replace( 'wp-login.php', ARIES_LOGIN_SLUG, $url );
}
add_filter( 'site_url', 'aries_login_url', 100 );
add_filter( 'network_site_url', 'aries_login_url', 100 );
add_filter( 'wp_redirect', 'aries_login_url', 100 );

// /login, /admin, /dashboard would send a visitor on to the login screen
remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );

/** The site's 404 page, never cached. */
function aries_login_not_found() {
	nocache_headers();
	wp_safe_redirect( home_url( '/404/' ) );
	exit;
}

add_action( 'plugins_loaded', function () {
	$GLOBALS['aries_cms'] = ( ARIES_LOGIN_SLUG === aries_login_request_path() );
}, 1 );

add_action( 'wp_loaded', function () {
	global $pagenow;
	if ( ! empty( $GLOBALS['aries_cms'] ) ) {
		// the login screen: WordPress's own, run from here; no page cache keeps it
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		do_action( 'litespeed_control_set_nocache', 'login screen' );
		$pagenow = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		aries_login_screen();
	}
	$postpass     = isset( $_GET['action'] ) && 'postpass' === $_GET['action']; // phpcs:ignore WordPress.Security.NonceVerification
	$direct_login = 'wp-login.php' === $pagenow && ! $postpass;
	$guest_admin  = is_admin() && ! is_user_logged_in() && ! wp_doing_ajax() && ! wp_doing_cron()
		&& ! in_array( $pagenow, array( 'admin-post.php', 'admin-ajax.php' ), true );
	if ( $direct_login || $guest_admin ) {
		aries_login_not_found();
	}
}, 1 );

/** WordPress's login screen, with the variables its functions read as globals. */
function aries_login_screen() {
	global $error, $interim_login, $action, $user_login;
	$user_login = ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	$error      = ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	require_once ABSPATH . 'wp-login.php';
	exit;
}
