<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Report-only starting point, built from the third-party hosts umex.ro loads
 * (Google Tag Manager / Analytics, Google Fonts, Font Awesome, YouTube, Google Maps).
 */
function umex_hardening_default_csp_report_only() {
	return implode(
		'; ',
		array(
			"default-src 'self'",
			"script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com https://*.google-analytics.com https://maps.googleapis.com https://use.fontawesome.com https://www.youtube.com",
			"style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://use.fontawesome.com",
			"font-src 'self' data: https://fonts.gstatic.com https://use.fontawesome.com",
			"img-src 'self' data: blob: https:",
			"connect-src 'self' https://*.google-analytics.com https://*.analytics.google.com https://www.googletagmanager.com https://maps.googleapis.com",
			"frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://www.google.com https://www.googletagmanager.com",
			"media-src 'self'",
			"object-src 'none'",
			"base-uri 'self'",
			"form-action 'self'",
			"frame-ancestors 'self'",
		)
	);
}

function umex_hardening_defaults() {
	return array(
		'block_xmlrpc'       => 1,
		'restrict_users_api' => 1,
		'security_headers'   => 1,
		'hsts_subdomains'    => 0,
		'restrict_wp_cron'   => 0,
		'cron_key'           => '',
		'csp_report_only'    => 0,
		'csp_policy'         => umex_hardening_default_csp_report_only(),
		'htaccess_rules'     => 0,
	);
}

function umex_hardening_options() {
	static $cache = null;
	if ( null === $cache ) {
		$stored = get_option( UMEX_HARDENING_OPTION, array() );
		$cache  = array_merge( umex_hardening_defaults(), is_array( $stored ) ? $stored : array() );
	}
	return $cache;
}

function umex_hardening_option( $key ) {
	$options = umex_hardening_options();
	return isset( $options[ $key ] ) ? $options[ $key ] : null;
}

function umex_hardening_sanitize_options( $input ) {
	$input    = is_array( $input ) ? $input : array();
	$defaults = umex_hardening_defaults();
	$clean    = array();

	foreach ( array( 'block_xmlrpc', 'restrict_users_api', 'security_headers', 'hsts_subdomains', 'restrict_wp_cron', 'csp_report_only', 'htaccess_rules' ) as $flag ) {
		$clean[ $flag ] = empty( $input[ $flag ] ) ? 0 : 1;
	}

	// Header values must stay on one line; anything else would let a newline split the header.
	$policy              = isset( $input['csp_policy'] ) ? (string) $input['csp_policy'] : $defaults['csp_policy'];
	$policy              = trim( preg_replace( '/[\x00-\x1F\x7F]+/', ' ', $policy ) );
	$clean['csp_policy'] = '' === $policy ? $defaults['csp_policy'] : $policy;

	// The key is never edited through the form; keep the stored one or create it once.
	$stored             = get_option( UMEX_HARDENING_OPTION, array() );
	$existing_key       = is_array( $stored ) && ! empty( $stored['cron_key'] ) ? $stored['cron_key'] : '';
	$clean['cron_key']  = $existing_key ? $existing_key : wp_generate_password( 32, false, false );

	return $clean;
}
