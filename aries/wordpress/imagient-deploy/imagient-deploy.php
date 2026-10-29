<?php
/**
 * Plugin Name:       Imagient Deploy
 * Description:       Updates the Aries theme and the Imagient Blocks plugin from their GitHub repository: only the files that changed, each checked against the published list before it is written. Run it from Tools → Imagient Deploy, or by an administrator through its REST route.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Imagient
 * Author URI:        https://imagient.ro
 * License:           GPL-2.0-or-later
 * Text Domain:       imagient-deploy
 *
 * How it works: the build (tools/build_wp.py) publishes aries/deploy/manifest.json in the repository, with every file
 * of each package and its SHA-256. A run reads the branch's latest commit, fetches that commit's manifest, downloads
 * the files whose hash differs from the copy on this server (from the same commit, so nothing is stale), checks each
 * against the manifest, and only when all of them check out writes them in place. Files are deleted only if an
 * earlier run installed them and the new manifest no longer lists them: anything else on the server (the licensed
 * fonts, uploads) is never touched.
 */

defined( 'ABSPATH' ) || exit;

define( 'IMAGIENT_DEPLOY_REPO', 'eddyqqqq/github-slideshow' );
define( 'IMAGIENT_DEPLOY_BRANCH', 'claude/exciting-heisenberg-rm02gn' );
define( 'IMAGIENT_DEPLOY_MANIFEST', 'aries/deploy/manifest.json' );

/** Where each package lives on this server. */
function imagient_deploy_dest( $type, $slug ) {
	if ( 'theme' === $type ) {
		return trailingslashit( get_theme_root() ) . $slug;
	}
	return trailingslashit( WP_PLUGIN_DIR ) . $slug;
}

/** A path from the manifest: relative, plain characters, no way out of its folder. */
function imagient_deploy_safe_path( $rel ) {
	return is_string( $rel ) && '' !== $rel && preg_match( '#^[A-Za-z0-9._\-/]+$#', $rel ) && false === strpos( $rel, '..' ) && '/' !== $rel[0];
}

function imagient_deploy_get( $url ) {
	$r = wp_remote_get( $url, array( 'timeout' => 60, 'headers' => array( 'User-Agent' => 'imagient-deploy', 'Accept' => 'application/vnd.github+json' ) ) );
	if ( is_wp_error( $r ) ) {
		return $r;
	}
	if ( 200 !== wp_remote_retrieve_response_code( $r ) ) {
		return new WP_Error( 'imagient_deploy_http', sprintf( 'HTTP %d for %s', wp_remote_retrieve_response_code( $r ), $url ) );
	}
	return wp_remote_retrieve_body( $r );
}

/**
 * One run. $dry: report what would change without writing.
 *
 * @return array|WP_Error
 */
function imagient_deploy_run( $dry = false ) {
	@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- not allowed on some hosts

	// the branch's latest commit: files are then read from that exact commit (raw files of a branch can lag behind)
	$head = imagient_deploy_get( 'https://api.github.com/repos/' . IMAGIENT_DEPLOY_REPO . '/commits/' . rawurlencode( IMAGIENT_DEPLOY_BRANCH ) );
	if ( is_wp_error( $head ) ) {
		return $head;
	}
	$head = json_decode( $head, true );
	$sha  = isset( $head['sha'] ) && preg_match( '/^[0-9a-f]{40}$/', $head['sha'] ) ? $head['sha'] : '';
	if ( ! $sha ) {
		return new WP_Error( 'imagient_deploy_head', 'Could not read the branch\'s latest commit.' );
	}
	$raw      = 'https://raw.githubusercontent.com/' . IMAGIENT_DEPLOY_REPO . '/' . $sha . '/';
	$manifest = imagient_deploy_get( $raw . IMAGIENT_DEPLOY_MANIFEST );
	if ( is_wp_error( $manifest ) ) {
		return $manifest;
	}
	$manifest = json_decode( $manifest, true );
	if ( empty( $manifest['packages'] ) || ! is_array( $manifest['packages'] ) ) {
		return new WP_Error( 'imagient_deploy_manifest', 'The manifest is empty or unreadable.' );
	}

	$previous = get_option( 'imagient_deploy_installed', array() );
	$plan     = array();
	foreach ( $manifest['packages'] as $slug => $pkg ) {
		if ( ! preg_match( '/^[a-z0-9\-]+$/', (string) $slug ) || empty( $pkg['src'] ) || ! imagient_deploy_safe_path( $pkg['src'] ) || ! in_array( $pkg['type'] ?? '', array( 'plugin', 'theme' ), true ) ) {
			return new WP_Error( 'imagient_deploy_package', 'A package in the manifest is malformed: ' . $slug );
		}
		$dest  = imagient_deploy_dest( $pkg['type'], $slug );
		$files = (array) ( $pkg['files'] ?? array() );
		$get   = array();
		foreach ( $files as $rel => $hash ) {
			if ( ! imagient_deploy_safe_path( $rel ) || ! preg_match( '/^[0-9a-f]{64}$/', (string) $hash ) ) {
				return new WP_Error( 'imagient_deploy_path', 'A file in the manifest is malformed: ' . $rel );
			}
			$local = $dest . '/' . $rel;
			if ( ! is_file( $local ) || hash_file( 'sha256', $local ) !== $hash ) {
				$get[ $rel ] = $hash;
			}
		}
		// removed since the last run: only files an earlier run installed
		$gone = array_values( array_diff( (array) ( $previous[ $slug ] ?? array() ), array_keys( $files ) ) );
		$plan[ $slug ] = array( 'dest' => $dest, 'src' => $pkg['src'], 'get' => $get, 'gone' => array_filter( $gone, 'imagient_deploy_safe_path' ), 'all' => array_keys( $files ) );
	}

	$report = array( 'commit' => $sha, 'version' => $manifest['version'] ?? '', 'packages' => array() );
	foreach ( $plan as $slug => $p ) {
		$report['packages'][ $slug ] = array( 'changed' => array_keys( $p['get'] ), 'deleted' => $p['gone'] );
	}
	if ( $dry ) {
		return $report;
	}

	// download and check everything first; nothing is written unless every file checks out
	$data = array();
	foreach ( $plan as $slug => $p ) {
		foreach ( $p['get'] as $rel => $hash ) {
			$body = imagient_deploy_get( $raw . $p['src'] . '/' . $rel );
			if ( is_wp_error( $body ) ) {
				return $body;
			}
			if ( hash( 'sha256', $body ) !== $hash ) {
				return new WP_Error( 'imagient_deploy_hash', 'A downloaded file does not match the manifest: ' . $slug . '/' . $rel );
			}
			$data[ $slug ][ $rel ] = $body;
		}
	}
	foreach ( $plan as $slug => $p ) {
		foreach ( $data[ $slug ] ?? array() as $rel => $body ) {
			$file = $p['dest'] . '/' . $rel;
			if ( ! wp_mkdir_p( dirname( $file ) ) || false === file_put_contents( $file, $body ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
				return new WP_Error( 'imagient_deploy_write', 'Could not write ' . $file );
			}
			if ( function_exists( 'opcache_invalidate' ) && '.php' === substr( $rel, -4 ) ) {
				opcache_invalidate( $file, true );
			}
		}
		foreach ( $p['gone'] as $rel ) {
			$file = $p['dest'] . '/' . $rel;
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
		$previous[ $slug ] = $p['all'];
	}
	update_option( 'imagient_deploy_installed', $previous, false );
	update_option( 'imagient_deploy_last', array( 'time' => time(), 'commit' => $sha, 'version' => $report['version'] ), false );
	return $report;
}

function imagient_deploy_can() {
	return current_user_can( 'update_plugins' ) && current_user_can( 'update_themes' );
}

// REST: POST /wp-json/imagient-deploy/v1/run (?dry=1), for a logged-in administrator (cookie + nonce, or an application password)
add_action( 'rest_api_init', function () {
	register_rest_route( 'imagient-deploy/v1', '/run', array(
		'methods'             => 'POST',
		'permission_callback' => 'imagient_deploy_can',
		'args'                => array( 'dry' => array( 'type' => 'boolean', 'default' => false ) ),
		'callback'            => function ( WP_REST_Request $req ) {
			$r = imagient_deploy_run( (bool) $req['dry'] );
			return is_wp_error( $r ) ? new WP_REST_Response( array( 'error' => $r->get_error_message() ), 500 ) : rest_ensure_response( $r );
		},
	) );
} );

// Tools → Imagient Deploy: the last run, and a button
add_action( 'admin_menu', function () {
	add_management_page( 'Imagient Deploy', 'Imagient Deploy', 'update_plugins', 'imagient-deploy', 'imagient_deploy_page' );
} );

function imagient_deploy_page() {
	if ( ! imagient_deploy_can() ) {
		return;
	}
	$out = null;
	if ( isset( $_POST['imagient_deploy'] ) && check_admin_referer( 'imagient_deploy' ) ) {
		$out = imagient_deploy_run( ! empty( $_POST['dry'] ) );
	}
	$last = get_option( 'imagient_deploy_last' );
	echo '<div class="wrap"><h1>Imagient Deploy</h1>';
	echo '<p>Updates the Aries theme and the Imagient Blocks plugin from <code>' . esc_html( IMAGIENT_DEPLOY_REPO ) . '</code>, branch <code>' . esc_html( IMAGIENT_DEPLOY_BRANCH ) . '</code>.</p>';
	if ( $last ) {
		echo '<p>Last update: ' . esc_html( wp_date( 'j F Y, H:i', $last['time'] ) ) . ' · version ' . esc_html( $last['version'] ) . ' · commit <code>' . esc_html( substr( $last['commit'], 0, 7 ) ) . '</code></p>';
	}
	if ( is_wp_error( $out ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $out->get_error_message() ) . '</p></div>';
	} elseif ( $out ) {
		echo '<div class="notice notice-success"><p>Version ' . esc_html( $out['version'] ) . ' (commit <code>' . esc_html( substr( $out['commit'], 0, 7 ) ) . '</code>):</p><ul>';
		foreach ( $out['packages'] as $slug => $p ) {
			echo '<li><strong>' . esc_html( $slug ) . '</strong>: ' . count( $p['changed'] ) . ' file(s) updated, ' . count( $p['deleted'] ) . ' removed</li>';
		}
		echo '</ul></div>';
	}
	echo '<form method="post">';
	wp_nonce_field( 'imagient_deploy' );
	echo '<p><label><input type="checkbox" name="dry" value="1"> Only show what would change</label></p>';
	submit_button( 'Update from GitHub', 'primary', 'imagient_deploy' );
	echo '</form></div>';
}
