<?php
/**
 * Pagina de autentificare:
 * - fără logo-ul WordPress deasupra formularului;
 * - adresa de login e /cms; wp-login.php și /wp-admin (pentru cine nu e logat) dau 404.
 *
 * Oprire de urgență (dacă nu mai poți intra): în wp-config.php adaugă
 *     define( 'PAVVA_CMS_LOGIN', false );
 * și login-ul revine la /wp-login.php. Sau redenumește folderul pluginului din hosting.
 *
 * @package Pavva_Widgets
 */

defined( 'ABSPATH' ) || exit;

class Pavva_W_Login {

	const SLUG = 'cms';

	/** cererea curentă e pentru /cms */
	private static $is_cms = false;
	/** cererea curentă e pentru wp-login.php direct (se blochează) */
	private static $is_wp_login = false;

	public static function init() {
		add_action( 'login_enqueue_scripts', array( __CLASS__, 'css' ) );
		/* dacă logo-ul apare totuși (ex. CSS blocat), nu mai duce la wordpress.org */
		add_filter( 'login_headerurl', function () { return home_url( '/' ); } );
		add_filter( 'login_headertext', function () { return get_bloginfo( 'name' ); } );

		if ( defined( 'PAVVA_CMS_LOGIN' ) && ! PAVVA_CMS_LOGIN ) return;
		if ( defined( 'WP_CLI' ) && WP_CLI ) return;

		self::detect();
		add_action( 'wp_loaded', array( __CLASS__, 'route' ), 1 );
		foreach ( array( 'site_url', 'network_site_url', 'wp_redirect' ) as $f ) {
			add_filter( $f, array( __CLASS__, 'rewrite_url' ), 10, 1 );
		}
		/* /login, /admin, /dashboard duceau automat la formular: nu mai dezvăluim adresa */
		remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
	}

	public static function css() {
		echo '<style id="pavva-login">#login h1, .login h1.wp-login-logo { display: none !important; }</style>' . "\n";
	}

	/** calea cererii, fără slash final, relativă la rădăcina site-ului */
	private static function request_path() {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = rawurldecode( (string) wp_parse_url( $uri, PHP_URL_PATH ) );
		$home = (string) wp_parse_url( (string) get_option( 'home' ), PHP_URL_PATH );
		if ( $home && 0 === strpos( $path, $home ) ) $path = substr( $path, strlen( $home ) );
		return '/' . trim( $path, '/' );
	}

	private static function detect() {
		$path = self::request_path();
		if ( '/' . self::SLUG === $path ) {
			self::$is_cms = true;
			$GLOBALS['pagenow'] = 'wp-login.php';
			$_SERVER['SCRIPT_NAME'] = '/' . self::SLUG;
		} elseif ( '/wp-login.php' === $path || '/wp-register.php' === $path || '/wp-signup.php' === $path ) {
			/* parola paginilor protejate se trimite tot prin wp-login.php: o lăsăm să treacă */
			$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
			if ( 'postpass' !== $action ) {
				self::$is_wp_login = true;
				$GLOBALS['pagenow'] = 'index.php';
			}
		}
	}

	public static function cms_url( $query = '' ) {
		$url = untrailingslashit( set_url_scheme( (string) get_option( 'home' ) ) ) . '/' . self::SLUG;
		return '' !== $query ? $url . '?' . $query : $url;
	}

	/** orice link spre wp-login.php (formular, delogare, resetare parolă) devine /cms */
	public static function rewrite_url( $url ) {
		if ( ! is_string( $url ) || false === strpos( $url, 'wp-login.php' ) ) return $url;
		if ( false !== strpos( $url, 'action=postpass' ) ) return $url;
		$parts = explode( '?', $url, 2 );
		return self::cms_url( isset( $parts[1] ) ? $parts[1] : '' );
	}

	private static function not_found() {
		nocache_headers();
		wp_safe_redirect( home_url( '/404/' ), 302 );
		exit;
	}

	public static function route() {
		global $pagenow;

		if ( self::$is_wp_login ) self::not_found();

		/* /wp-admin pentru cine nu e logat: 404, nu trimitere la formular */
		if ( is_admin() && ! is_user_logged_in() && ! wp_doing_ajax() && ! wp_doing_cron()
			&& ! in_array( $pagenow, array( 'admin-post.php', 'admin-ajax.php' ), true ) ) {
			self::not_found();
		}

		if ( self::$is_cms ) {
			/* deja logat și fără o acțiune anume: direct în admin */
			if ( is_user_logged_in() && empty( $_REQUEST['action'] ) && empty( $_REQUEST['reauth'] ) && empty( $_REQUEST['interim-login'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				wp_safe_redirect( admin_url() );
				exit;
			}
			/* formularul de login nu se pune niciodată în cache */
			nocache_headers();
			do_action( 'litespeed_control_set_nocache', 'pavva cms login' );
			if ( ! defined( 'DONOTCACHEPAGE' ) ) define( 'DONOTCACHEPAGE', true );

			global $error, $interim_login, $action, $user_login, $user, $redirect_to;
			$pagenow = 'wp-login.php';
			@require_once ABSPATH . 'wp-login.php'; // phpcs:ignore WordPress.PHP.NoSilencedErrors
			exit;
		}
	}
}

Pavva_W_Login::init();
