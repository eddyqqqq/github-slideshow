<?php
/**
 * Plugin Name:       Pavva Imagini
 * Plugin URI:        https://imagient.ro/
 * Description:       Convertește imaginile din Media Library în WebP și AVIF și le livrează automat fiecărui browser (AVIF → WebP → original). Fișierele originale rămân neatinse. Media → Pavva – Imagini. Creat de Imagient (imagient.ro).
 * Version:           1.0.1
 * Author:            Imagient
 * Author URI:        https://imagient.ro/
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Text Domain:       pavva-imagini
 *
 * @package Pavva_Imagini
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'PVI_VERSION', '1.0.1' );
define( 'PVI_FILE', __FILE__ );
define( 'PVI_PATH', plugin_dir_path( __FILE__ ) );
define( 'PVI_URL', plugin_dir_url( __FILE__ ) );

require_once PVI_PATH . 'includes/class-pvi-settings.php';
require_once PVI_PATH . 'includes/class-pvi-converter.php';
require_once PVI_PATH . 'includes/class-pvi-delivery.php';
require_once PVI_PATH . 'includes/class-pvi-admin.php';

/* livrarea (regulile din uploads/.htaccess) se pune la activare și se scoate la dezactivare */
register_activation_hook( __FILE__, array( 'PVI_Delivery', 'write_rules' ) );
register_deactivation_hook( __FILE__, array( 'PVI_Delivery', 'remove_rules' ) );

add_action( 'plugins_loaded', function () {
	PVI_Converter::init();
	PVI_Admin::init();
} );

/* semnătura agenției în subsolul paginii Media → Pavva – Imagini */
add_filter( 'admin_footer_text', function ( $text ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'media_page_pavva-imagini' === $screen->id ) {
		return 'Pavva Imagini – creat de <a href="https://imagient.ro/" target="_blank" rel="noopener">Imagient</a>';
	}
	return $text;
} );
