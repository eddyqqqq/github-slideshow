<?php
/**
 * Plugin Name:       Pavva Imagini
 * Description:       Convertește imaginile din Media Library în WebP și AVIF și le livrează automat fiecărui browser (AVIF → WebP → original). Fișierele originale rămân neatinse. Media → Pavva – Imagini.
 * Version:           1.0.0
 * Author:            imagient
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Text Domain:       pavva-imagini
 *
 * @package Pavva_Imagini
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'PVI_VERSION', '1.0.0' );
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
