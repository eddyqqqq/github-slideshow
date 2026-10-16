<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UMEX_HARDENING_HTACCESS_MARKER', 'UMEX Hardening' );

/**
 * Server-level copy of the rules, so they also cover static files and pages
 * served from a page cache, where PHP never runs.
 */
function umex_hardening_htaccess_lines( $options ) {
	$lines = array();

	if ( ! empty( $options['block_xmlrpc'] ) ) {
		$lines = array_merge(
			$lines,
			array(
				'<Files "xmlrpc.php">',
				'  <IfModule mod_authz_core.c>',
				'    Require all denied',
				'  </IfModule>',
				'  <IfModule !mod_authz_core.c>',
				'    Order allow,deny',
				'    Deny from all',
				'  </IfModule>',
				'</Files>',
			)
		);
	}

	if ( ! empty( $options['security_headers'] ) ) {
		$https   = 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME );
		$csp     = "frame-ancestors 'self'; object-src 'none'; base-uri 'self'" . ( $https ? '; upgrade-insecure-requests' : '' );
		$headers = array(
			'X-Content-Type-Options'  => 'nosniff',
			'X-Frame-Options'         => 'SAMEORIGIN',
			'Referrer-Policy'         => 'strict-origin-when-cross-origin',
			'Permissions-Policy'      => 'camera=(), microphone=(), payment=(), usb=()',
			'Content-Security-Policy' => $csp,
		);
		if ( $https ) {
			$headers = array( 'Strict-Transport-Security' => 'max-age=31536000' . ( ! empty( $options['hsts_subdomains'] ) ? '; includeSubDomains' : '' ) ) + $headers;
		}

		$lines[] = '<IfModule mod_headers.c>';
		foreach ( $headers as $name => $value ) {
			// Drop the copy PHP already sent, so each header appears exactly once.
			$lines[] = '  Header unset ' . $name;
			$lines[] = '  Header always set ' . $name . ' "' . $value . '"';
		}
		$lines[] = '  Header unset X-Powered-By';
		$lines[] = '  Header always unset X-Powered-By';
		$lines[] = '</IfModule>';
	}

	return $lines;
}

function umex_hardening_htaccess_path() {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	return get_home_path() . '.htaccess';
}

function umex_hardening_htaccess_has_block() {
	$file = umex_hardening_htaccess_path();
	if ( ! is_readable( $file ) ) {
		return false;
	}
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	return array() !== extract_from_markers( $file, UMEX_HARDENING_HTACCESS_MARKER );
}

/**
 * Writes (or empties) the plugin's block in .htaccess.
 *
 * @return true|WP_Error
 */
function umex_hardening_write_htaccess( $enable, $options = null ) {
	global $is_apache;

	require_once ABSPATH . 'wp-admin/includes/misc.php';
	$file = umex_hardening_htaccess_path();

	if ( ! $enable ) {
		if ( ! file_exists( $file ) || ! umex_hardening_htaccess_has_block() ) {
			return true;
		}
		return insert_with_markers( $file, UMEX_HARDENING_HTACCESS_MARKER, array() )
			? true
			: new WP_Error( 'umex_htaccess_not_writable', 'Fișierul .htaccess nu poate fi modificat. Șterge manual blocul „UMEX Hardening”.' );
	}

	if ( ! $is_apache ) {
		return new WP_Error( 'umex_not_apache', 'Serverul nu pare să fie Apache sau LiteSpeed, deci .htaccess nu e folosit. Cere hostingului să adauge regulile în configurația serverului (de exemplu Nginx).' );
	}

	$writable = file_exists( $file ) ? is_writable( $file ) : is_writable( dirname( $file ) );
	if ( ! $writable ) {
		return new WP_Error( 'umex_htaccess_not_writable', 'Fișierul .htaccess nu poate fi scris. Copiază manual regulile afișate pe această pagină.' );
	}

	$options = null === $options ? umex_hardening_options() : $options;
	return insert_with_markers( $file, UMEX_HARDENING_HTACCESS_MARKER, umex_hardening_htaccess_lines( $options ) )
		? true
		: new WP_Error( 'umex_htaccess_failed', 'Scrierea în .htaccess a eșuat.' );
}
