<?php
/**
 * Pagina de autentificare: fără logo-ul WordPress deasupra formularului.
 *
 * @package Pavva_Widgets
 */

defined( 'ABSPATH' ) || exit;

class Pavva_W_Login {

	public static function init() {
		add_action( 'login_enqueue_scripts', array( __CLASS__, 'css' ) );
		/* dacă logo-ul apare totuși (ex. CSS blocat), nu mai duce la wordpress.org */
		add_filter( 'login_headerurl', 'home_url' );
		add_filter( 'login_headertext', function () { return get_bloginfo( 'name' ); } );
	}

	public static function css() {
		echo '<style id="pavva-login">#login h1, .login h1.wp-login-logo { display: none !important; }</style>' . "\n";
	}
}

Pavva_W_Login::init();
