<?php
/**
 * Plugin Name:       Pavva Widgets
 * Description:       Widgeturile Elementor și câmpurile de produs / categorie create special pentru site-ul Pavva. Creat de imagient.
 * Version:           3.2.4
 * Author:            imagient
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  elementor
 * Text Domain:       pavva-widgets
 * License:           Proprietar – creat de imagient pentru Pavva.
 *
 * @package Pavva_Widgets
 * @author  imagient
 *
 * Plugin realizat de imagient pentru Pavva.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PAVVA_WIDGETS_VERSION', '3.2.4' );
define( 'PAVVA_WIDGETS_URL', plugin_dir_url( __FILE__ ) );
define( 'PAVVA_WIDGETS_PATH', plugin_dir_path( __FILE__ ) );

/* Accesul la datele produselor / categoriilor (aceleași câmpuri ca pluginurile vechi). */
require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-data.php';

/* Formularul de ofertă se completează singur din linkul „Solicită ofertă”. */
require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-quote-prefill.php';
Pavva_W_Quote_Prefill::init();

/* Verificarea widgeturilor vechi (pagina Plugins), doar în admin. */
if ( is_admin() ) {
	require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-migration.php';
	Pavva_W_Migration::init();
}

add_action( 'plugins_loaded', 'pavva_widgets_init' );
add_action( 'plugins_loaded', 'pavva_widgets_init_fields', 20 );

/*
 * Câmpurile din admin (produse + categorii).
 * Pornesc doar când pluginurile vechi „Pavva Products” / „Pavva Categories”
 * sunt dezactivate, ca să nu apară de două ori. Datele sunt aceleași.
 */
function pavva_widgets_init_fields() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	/* Câmp nou (Imagini slider header): activ mereu, nu există în pluginurile vechi. */
	require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-slider-fields.php';
	new Pavva_W_Slider_Fields();

	if ( ! class_exists( 'PAVVA_Produse' ) ) {
		require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-product-fields.php';
		new Pavva_W_Product_Fields();
	}
	if ( ! class_exists( 'PAVVA_Categories' ) ) {
		require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-category-fields.php';
		new Pavva_W_Category_Fields();
	}
	if ( class_exists( 'PAVVA_Produse' ) || class_exists( 'PAVVA_Categories' ) ) {
		add_action( 'admin_notices', 'pavva_widgets_notice_fields_waiting' );
	}
}

function pavva_widgets_notice_fields_waiting() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'plugins' !== $screen->id ) {
		return;
	}
	$old = array();
	if ( class_exists( 'PAVVA_Produse' ) ) $old[] = '<strong>Pavva Products</strong>';
	if ( class_exists( 'PAVVA_Categories' ) ) $old[] = '<strong>Pavva Categories</strong>';
	echo '<div class="notice notice-info"><p><strong>Pavva Widgets:</strong> câmpurile de produs / categorie sunt pregătite și preiau automat locul pluginurilor ' . implode( ' și ', $old ) . ' (aceleași date, nimic de reintrodus). Dezactivează-le abia după ce template-urile folosesc noile widgeturi Pavva.</p></div>'; // phpcs:ignore
}

function pavva_widgets_init() {
	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', 'pavva_widgets_notice_missing_elementor' );
		return;
	}

	/* Vechiul plugin „Carusel Produse” încă activ: doar recomandăm ștergerea lui.
	   Widgeturile Pavva se înregistrează oricum (caruselul Pavva îl înlocuiește pe cel vechi). */
	if ( defined( 'CCP_VERSION' ) ) {
		add_action( 'admin_notices', 'pavva_widgets_notice_old_plugin' );
	}

	add_action( 'elementor/elements/categories_registered', 'pavva_widgets_register_category' );
	add_action( 'elementor/frontend/after_register_styles', 'pavva_widgets_register_styles' );
	add_action( 'elementor/frontend/after_register_scripts', 'pavva_widgets_register_scripts' );
	/* prioritate 20: rulează după vechiul plugin, deci caruselul Pavva are întâietate */
	add_action( 'elementor/widgets/register', 'pavva_widgets_register_widgets', 20 );
	add_action( 'elementor/dynamic_tags/register', 'pavva_widgets_register_dynamic_tags' );
}

/* Câmpurile Pavva selectabile în orice widget Elementor (Dynamic Tags). */
function pavva_widgets_register_dynamic_tags( $dynamic_tags_manager ) {
	if ( ! class_exists( '\\Elementor\\Core\\DynamicTags\\Tag' ) || ! class_exists( '\\Elementor\\Core\\DynamicTags\\Data_Tag' ) ) {
		return;
	}
	try {
		require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-dynamic-tags.php';
		Pavva_W_Dynamic_Tags::register( $dynamic_tags_manager );
	} catch ( \Throwable $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Pavva Widgets – dynamic tags: ' . $e->getMessage() ); // phpcs:ignore
		}
	}
}

function pavva_widgets_notice_missing_elementor() {
	echo '<div class="notice notice-warning"><p><strong>Pavva Widgets</strong> are nevoie de pluginul Elementor activ.</p></div>';
}

function pavva_widgets_notice_old_plugin() {
	echo '<div class="notice notice-warning"><p><strong>Pavva Widgets:</strong> dezactivează și șterge vechiul plugin <strong>Carusel Produse pentru Elementor</strong>. Caruselul este inclus în Pavva Widgets, iar paginile tale rămân neschimbate.</p></div>';
}

/* Autor vizibil în lista de pluginuri. */
add_filter( 'plugin_row_meta', 'pavva_widgets_row_meta', 10, 2 );
function pavva_widgets_row_meta( $links, $file ) {
	if ( plugin_basename( __FILE__ ) === $file ) {
		$links[] = 'Creat de <strong>imagient</strong> pentru Pavva';
	}
	return $links;
}

function pavva_widgets_register_category( $elements_manager ) {
	$elements_manager->add_category(
		'pavva',
		array(
			'title' => 'Pavva',
			'icon'  => 'eicon-apps',
		)
	);
}

/* Versiunea fișierelor = data ultimei modificări, ca să nu rămână vechi în cache. */
function pavva_widgets_asset_version( $relative_path ) {
	$file = PAVVA_WIDGETS_PATH . $relative_path;
	return file_exists( $file ) ? (string) filemtime( $file ) : PAVVA_WIDGETS_VERSION;
}

function pavva_widgets_register_styles() {
	wp_register_style( 'pavva-carusel', PAVVA_WIDGETS_URL . 'assets/carusel.css', array(), pavva_widgets_asset_version( 'assets/carusel.css' ) );
	wp_register_style( 'pavva-hero-slider', PAVVA_WIDGETS_URL . 'assets/hero-slider.css', array(), pavva_widgets_asset_version( 'assets/hero-slider.css' ) );
	wp_register_style( 'pavva-feature-tabs', PAVVA_WIDGETS_URL . 'assets/feature-tabs.css', array(), pavva_widgets_asset_version( 'assets/feature-tabs.css' ) );
	wp_register_style( 'pavva-culori', PAVVA_WIDGETS_URL . 'assets/culori.css', array(), pavva_widgets_asset_version( 'assets/culori.css' ) );
	wp_register_style( 'pavva-dimensiuni', PAVVA_WIDGETS_URL . 'assets/dimensiuni.css', array(), pavva_widgets_asset_version( 'assets/dimensiuni.css' ) );
	wp_register_style( 'pavva-acordeon', PAVVA_WIDGETS_URL . 'assets/acordeon.css', array(), pavva_widgets_asset_version( 'assets/acordeon.css' ) );
	wp_register_style( 'pavva-grila-produse', PAVVA_WIDGETS_URL . 'assets/grila-produse.css', array(), pavva_widgets_asset_version( 'assets/grila-produse.css' ) );
	wp_register_style( 'pavva-caracteristici', PAVVA_WIDGETS_URL . 'assets/caracteristici.css', array(), pavva_widgets_asset_version( 'assets/caracteristici.css' ) );
	wp_register_style( 'pavva-sectiuni-goale', PAVVA_WIDGETS_URL . 'assets/sectiuni-goale.css', array(), pavva_widgets_asset_version( 'assets/sectiuni-goale.css' ) );
}

function pavva_widgets_register_scripts() {
	wp_register_script( 'pavva-carusel', PAVVA_WIDGETS_URL . 'assets/carusel.js', array( 'jquery', 'elementor-frontend' ), pavva_widgets_asset_version( 'assets/carusel.js' ), true );
	wp_register_script( 'pavva-hero-slider', PAVVA_WIDGETS_URL . 'assets/hero-slider.js', array( 'jquery', 'elementor-frontend' ), pavva_widgets_asset_version( 'assets/hero-slider.js' ), true );
	wp_register_script( 'pavva-feature-tabs', PAVVA_WIDGETS_URL . 'assets/feature-tabs.js', array( 'jquery', 'elementor-frontend' ), pavva_widgets_asset_version( 'assets/feature-tabs.js' ), true );
	wp_register_script( 'pavva-culori', PAVVA_WIDGETS_URL . 'assets/culori.js', array( 'jquery', 'elementor-frontend' ), pavva_widgets_asset_version( 'assets/culori.js' ), true );
	wp_register_script( 'pavva-acordeon', PAVVA_WIDGETS_URL . 'assets/acordeon.js', array( 'jquery', 'elementor-frontend' ), pavva_widgets_asset_version( 'assets/acordeon.js' ), true );
	wp_register_script( 'pavva-sectiuni-goale', PAVVA_WIDGETS_URL . 'assets/sectiuni-goale.js', array(), pavva_widgets_asset_version( 'assets/sectiuni-goale.js' ), true );
}

function pavva_widgets_register_widgets( $widgets_manager ) {
	$widgets = array(
		'widgets/class-pavva-carusel-widget.php'     => 'Pavva_Carusel_Widget',
		'widgets/class-pavva-hero-slider-widget.php' => 'Pavva_Hero_Slider_Widget',
		'widgets/class-pavva-feature-tabs-widget.php' => 'Pavva_Feature_Tabs_Widget',
		/* widgeturile de produs / categorie (citesc câmpurile Pavva ale paginii curente) */
		'widgets/class-pavva-caracteristici-widget.php' => 'Pavva_Caracteristici_Widget',
		'widgets/class-pavva-culori-widget.php'        => 'Pavva_Culori_Widget',
		'widgets/class-pavva-dimensiuni-widget.php'    => 'Pavva_Dimensiuni_Widget',
		'widgets/class-pavva-acordeon-widget.php'      => 'Pavva_Acordeon_Widget',
		'widgets/class-pavva-grila-produse-widget.php' => 'Pavva_Grila_Produse_Widget',
	);

	/* fiecare widget separat: dacă unul are o problemă, celălalt tot apare */
	foreach ( $widgets as $file => $class ) {
		try {
			require_once PAVVA_WIDGETS_PATH . $file;
			if ( class_exists( $class ) ) {
				$widgets_manager->register( new $class() );
			}
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Pavva Widgets – ' . $class . ': ' . $e->getMessage() ); // phpcs:ignore
			}
		}
	}
}
