<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UMEX_HARDENING_CSP_REPORTS_OPTION', 'umex_hardening_csp_reports' );
define( 'UMEX_HARDENING_CSP_REPORTS_MAX', 100 );

/**
 * Runs while plugins load, before the rest of WordPress boots.
 */
function umex_hardening_early_gate() {
	if ( umex_hardening_option( 'block_xmlrpc' ) && defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		/** Return true to keep XML-RPC reachable (for example for Jetpack or the mobile app). */
		if ( ! apply_filters( 'umex_hardening_allow_xmlrpc', false ) ) {
			umex_hardening_deny( 'XML-RPC is disabled on this site.' );
		}
	}

	if ( umex_hardening_option( 'restrict_wp_cron' ) && umex_hardening_is_direct_cron_request() && ! umex_hardening_cron_request_allowed() ) {
		umex_hardening_deny( 'Forbidden.' );
	}
}

function umex_hardening_deny( $message ) {
	if ( ! headers_sent() ) {
		status_header( 403 );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
	}
	echo esc_html( $message );
	exit;
}

function umex_hardening_is_direct_cron_request() {
	if ( ! defined( 'DOING_CRON' ) || ! DOING_CRON ) {
		return false;
	}
	$script = isset( $_SERVER['SCRIPT_FILENAME'] ) ? wp_basename( (string) $_SERVER['SCRIPT_FILENAME'] ) : '';
	return 'wp-cron.php' === $script;
}

function umex_hardening_cron_request_allowed() {
	if ( 'cli' === PHP_SAPI ) {
		return true;
	}
	$key = (string) umex_hardening_option( 'cron_key' );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the shared key is the authentication.
	$given = isset( $_GET['umex_cron_key'] ) ? (string) wp_unslash( $_GET['umex_cron_key'] ) : '';
	return '' !== $key && '' !== $given && hash_equals( $key, $given );
}

function umex_hardening_cron_url() {
	return add_query_arg( 'umex_cron_key', rawurlencode( (string) umex_hardening_option( 'cron_key' ) ), site_url( 'wp-cron.php' ) );
}

function umex_hardening_register_runtime_hooks() {
	if ( umex_hardening_option( 'block_xmlrpc' ) ) {
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'xmlrpc_methods', '__return_empty_array' );
		add_filter( 'wp_headers', 'umex_hardening_remove_pingback_header' );
		remove_action( 'wp_head', 'rsd_link' );
	}

	if ( umex_hardening_option( 'restrict_users_api' ) ) {
		add_filter( 'rest_pre_dispatch', 'umex_hardening_block_users_route', 10, 3 );
		add_filter( 'oembed_response_data', 'umex_hardening_strip_oembed_author' );
		add_filter( 'wp_sitemaps_add_provider', 'umex_hardening_drop_users_sitemap', 10, 2 );
		add_action( 'template_redirect', 'umex_hardening_block_author_query', 1 );
	}

	if ( umex_hardening_option( 'restrict_wp_cron' ) ) {
		// WordPress spawns wp-cron.php over HTTP itself; give those loopback requests the key.
		add_filter( 'cron_request', 'umex_hardening_sign_cron_request' );
	}

	if ( umex_hardening_option( 'security_headers' ) || umex_hardening_option( 'csp_report_only' ) ) {
		add_action( 'send_headers', 'umex_hardening_send_security_headers' );
		add_action( 'login_init', 'umex_hardening_send_security_headers' );
		add_action( 'admin_init', 'umex_hardening_send_security_headers' );
		add_filter( 'rest_pre_serve_request', 'umex_hardening_send_headers_for_rest' );
	}

	if ( umex_hardening_option( 'csp_report_only' ) ) {
		add_action( 'rest_api_init', 'umex_hardening_register_csp_report_route' );
	}
}

/* -------------------------------------------------------------------------
 * XML-RPC
 * ---------------------------------------------------------------------- */

function umex_hardening_remove_pingback_header( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
}

/* -------------------------------------------------------------------------
 * User enumeration
 * ---------------------------------------------------------------------- */

function umex_hardening_block_users_route( $result, $server, $request ) {
	// WordPress matches REST routes case-insensitively, so this check must too.
	if ( ! is_user_logged_in() && preg_match( '#^/wp/v2/users(?:/|$)#i', $request->get_route() ) ) {
		return new WP_Error( 'rest_user_cannot_view', 'Acces interzis.', array( 'status' => 401 ) );
	}
	return $result;
}

function umex_hardening_strip_oembed_author( $data ) {
	unset( $data['author_name'], $data['author_url'] );
	return $data;
}

function umex_hardening_drop_users_sitemap( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}

function umex_hardening_block_author_query() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check.
	if ( isset( $_GET['author'] ) && ! is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}

/* -------------------------------------------------------------------------
 * wp-cron
 * ---------------------------------------------------------------------- */

function umex_hardening_sign_cron_request( $cron_request ) {
	if ( ! empty( $cron_request['url'] ) ) {
		$cron_request['url'] = add_query_arg( 'umex_cron_key', rawurlencode( (string) umex_hardening_option( 'cron_key' ) ), $cron_request['url'] );
	}
	return $cron_request;
}

/* -------------------------------------------------------------------------
 * Security headers
 * ---------------------------------------------------------------------- */

function umex_hardening_is_https() {
	if ( is_ssl() ) {
		return true;
	}
	// Sites behind a TLS-terminating proxy see plain HTTP; trust the proxy's header.
	return isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( (string) $_SERVER['HTTP_X_FORWARDED_PROTO'] );
}

/**
 * The enforced policy only covers directives that cannot break Divi or third-party scripts.
 */
function umex_hardening_enforced_csp() {
	$policy = "frame-ancestors 'self'; object-src 'none'; base-uri 'self'";
	if ( umex_hardening_is_https() ) {
		$policy .= '; upgrade-insecure-requests';
	}
	return $policy;
}

function umex_hardening_hsts_value() {
	return 'max-age=31536000' . ( umex_hardening_option( 'hsts_subdomains' ) ? '; includeSubDomains' : '' );
}

function umex_hardening_security_headers() {
	$headers = array();

	if ( umex_hardening_option( 'security_headers' ) ) {
		if ( umex_hardening_is_https() ) {
			$headers['Strict-Transport-Security'] = umex_hardening_hsts_value();
		}
		$headers['X-Content-Type-Options']  = 'nosniff';
		$headers['X-Frame-Options']         = 'SAMEORIGIN';
		$headers['Referrer-Policy']         = 'strict-origin-when-cross-origin';
		$headers['Permissions-Policy']      = 'camera=(), microphone=(), payment=(), usb=()';
		$headers['Content-Security-Policy'] = umex_hardening_enforced_csp();
	}

	// Report-only is meant for visitor-facing pages; admin screens would only add noise.
	if ( umex_hardening_option( 'csp_report_only' ) && ! is_admin() && ! did_action( 'login_init' ) ) {
		$policy = (string) umex_hardening_option( 'csp_policy' );
		if ( '' !== $policy ) {
			$headers['Content-Security-Policy-Report-Only'] = $policy . '; report-uri ' . esc_url_raw( rest_url( 'umex-hardening/v1/csp-report' ) );
		}
	}

	/** Filters the security headers before they are sent. */
	return apply_filters( 'umex_hardening_headers', $headers );
}

function umex_hardening_send_security_headers() {
	if ( headers_sent() ) {
		return;
	}
	foreach ( umex_hardening_security_headers() as $name => $value ) {
		header( $name . ': ' . $value );
	}
	header_remove( 'X-Powered-By' );
}

function umex_hardening_send_headers_for_rest( $served ) {
	umex_hardening_send_security_headers();
	return $served;
}

/* -------------------------------------------------------------------------
 * CSP violation reports (report-only mode)
 * ---------------------------------------------------------------------- */

function umex_hardening_register_csp_report_route() {
	register_rest_route(
		'umex-hardening/v1',
		'/csp-report',
		array(
			'methods'             => 'POST',
			'callback'            => 'umex_hardening_receive_csp_report',
			'permission_callback' => '__return_true',
		)
	);
}

function umex_hardening_receive_csp_report( WP_REST_Request $request ) {
	$body = (string) $request->get_body();
	if ( '' === $body || strlen( $body ) > 16384 ) {
		return new WP_REST_Response( null, 204 );
	}

	$decoded = json_decode( $body, true );
	if ( ! is_array( $decoded ) ) {
		return new WP_REST_Response( null, 204 );
	}

	// report-uri sends {"csp-report": {...}}; the Reporting API sends a list of {"type", "body"}.
	$reports = array();
	if ( isset( $decoded['csp-report'] ) && is_array( $decoded['csp-report'] ) ) {
		$reports[] = $decoded['csp-report'];
	} else {
		foreach ( array_slice( $decoded, 0, 20 ) as $item ) {
			if ( is_array( $item ) && isset( $item['body'] ) && is_array( $item['body'] ) ) {
				$reports[] = $item['body'];
			}
		}
	}

	foreach ( $reports as $report ) {
		umex_hardening_store_csp_report( $report );
	}

	return new WP_REST_Response( null, 204 );
}

function umex_hardening_report_field( $report, $keys ) {
	foreach ( $keys as $key ) {
		if ( isset( $report[ $key ] ) && is_scalar( $report[ $key ] ) && '' !== (string) $report[ $key ] ) {
			return (string) $report[ $key ];
		}
	}
	return '';
}

/**
 * Reduces a blocked URL to what a policy needs: an origin, or a keyword such as inline/eval/data.
 */
function umex_hardening_normalize_blocked( $blocked ) {
	$blocked = trim( $blocked );
	if ( '' === $blocked ) {
		return 'inline';
	}
	if ( ! preg_match( '#^[a-z][a-z0-9+.-]*://#i', $blocked ) ) {
		// "data:image/png;base64,..." becomes "data"; keywords such as "inline" or "eval" stay as they are.
		$keyword = preg_match( '/^([a-z][a-z0-9+.-]*):/i', $blocked, $m ) ? $m[1] : $blocked;
		return strtolower( substr( preg_replace( '/[^a-z0-9-]/i', '', $keyword ), 0, 20 ) );
	}
	$parts = wp_parse_url( $blocked );
	if ( empty( $parts['host'] ) || empty( $parts['scheme'] ) || ! preg_match( '/^[a-z0-9.-]{1,253}$/i', $parts['host'] ) ) {
		return 'unknown';
	}
	return strtolower( $parts['scheme'] . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . (int) $parts['port'] : '' ) );
}

function umex_hardening_store_csp_report( $report ) {
	$directive = umex_hardening_report_field( $report, array( 'effective-directive', 'effectiveDirective', 'violated-directive', 'violatedDirective' ) );
	$directive = strtolower( substr( preg_replace( '/[^a-z-].*$/i', '', $directive ), 0, 40 ) );
	if ( '' === $directive ) {
		return;
	}

	$blocked  = umex_hardening_normalize_blocked( umex_hardening_report_field( $report, array( 'blocked-uri', 'blockedURL' ) ) );
	$document = umex_hardening_report_field( $report, array( 'document-uri', 'documentURL' ) );
	$path     = $document ? (string) wp_parse_url( $document, PHP_URL_PATH ) : '';

	$stored = get_option( UMEX_HARDENING_CSP_REPORTS_OPTION, array() );
	$stored = is_array( $stored ) ? $stored : array();
	$id     = md5( $directive . '|' . $blocked );
	$now    = time();

	if ( isset( $stored[ $id ] ) ) {
		// Write at most once an hour per entry so a busy page cannot hammer the database.
		if ( $now - (int) $stored[ $id ]['last'] < HOUR_IN_SECONDS ) {
			return;
		}
		$stored[ $id ]['last'] = $now;
		++$stored[ $id ]['hours'];
	} else {
		if ( count( $stored ) >= UMEX_HARDENING_CSP_REPORTS_MAX ) {
			return;
		}
		$stored[ $id ] = array(
			'directive' => $directive,
			'blocked'   => $blocked,
			'page'      => substr( $path, 0, 200 ),
			'first'     => $now,
			'last'      => $now,
			'hours'     => 1,
		);
	}

	update_option( UMEX_HARDENING_CSP_REPORTS_OPTION, $stored, false );
}
