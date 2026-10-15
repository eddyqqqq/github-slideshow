<?php
/**
 * Plugin Name:       Pavva Widgets
 * Plugin URI:        https://imagient.ro/
 * Description:       Widgeturile Elementor, etichetele dinamice și formularele create special pentru site-ul Pavva. Câmpurile produselor / categoriilor sunt în „Pavva Products” și „Pavva Categories”. Creat de Imagient (imagient.ro).
 * Version:           4.12.8
 * Author:            Imagient
 * Author URI:        https://imagient.ro/
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  elementor
 * Text Domain:       pavva-widgets
 * License:           Proprietar – creat de Imagient pentru Pavva.
 *
 * @package Pavva_Widgets
 * @author  Imagient <https://imagient.ro/>
 *
 * Plugin realizat de imagient pentru Pavva.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PAVVA_WIDGETS_VERSION', '4.12.8' );
define( 'PAVVA_WIDGETS_URL', plugin_dir_url( __FILE__ ) );
define( 'PAVVA_WIDGETS_PATH', plugin_dir_path( __FILE__ ) );

/* la dezactivare: regulile de cache din .htaccess (Setări → Pavva – Viteză) se scot */
register_deactivation_hook( __FILE__, function () {
	require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-perf.php';
	Pavva_W_Perf::sync_htaccess( false );
	delete_option( 'pavva_perf_htaccess_v' );
	/* fișierul llms.txt creat de plugin */
	require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-llms.php';
	Pavva_W_Llms::remove();
} );

/* Accesul la datele produselor / categoriilor (aceleași câmpuri ca pluginurile vechi). */
require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-data.php';

/* Formularul de ofertă se completează singur din linkul „Solicită ofertă”. */
require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-quote-form.php';
Pavva_W_Quote_Form::init();

/* Lista de ofertă: clientul adaugă mai multe produse și le trimite într-o singură cerere. */
require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-lista.php';
Pavva_W_Lista::init();

/* Verificarea widgeturilor vechi (pagina Plugins), doar în admin. */
if ( is_admin() ) {
	require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-migration.php';
	Pavva_W_Migration::init();
}

add_action( 'plugins_loaded', 'pavva_widgets_init' );
add_action( 'plugins_loaded', 'pavva_widgets_init_fields', 20 );

/*
 * Câmpurile din admin sunt în pluginurile separate „Pavva Products” (5.0+)
 * și „Pavva Categories” (2.0+). Aici doar anunțăm dacă lipsesc.
 */
function pavva_widgets_init_fields() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	add_action( 'admin_notices', 'pavva_widgets_notice_fields' );
}

function pavva_widgets_notice_fields() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'product', 'edit-product', 'edit-product_cat' ), true ) ) {
		return;
	}
	$msg = array();
	if ( ! class_exists( 'PAVVA_Produse' ) ) {
		$msg[] = 'activează <strong>Pavva Products</strong> (câmpurile produselor: Caracteristici, Culori, Dimensiuni, Fișe tehnice…)';
	} elseif ( version_compare( PAVVA_Produse::VERSION, '5.0.0', '<' ) ) {
		$msg[] = 'actualizează <strong>Pavva Products</strong> la 5.1.0 (versiunea veche aduce înapoi widgeturile vechi)';
	}
	if ( ! class_exists( 'PAVVA_Categories' ) ) {
		$msg[] = 'activează <strong>Pavva Categories</strong> (câmpurile categoriilor: Imagine, Intro, Avantaje, De ce să alegi)';
	} elseif ( version_compare( PAVVA_Categories::VERSION, '2.0.0', '<' ) ) {
		$msg[] = 'actualizează <strong>Pavva Categories</strong> la 2.0.0 (versiunea veche aduce înapoi widgeturile vechi și nu are câmpul „Imagini slider header”)';
	}
	if ( ! $msg ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>Pavva Widgets:</strong> ' . implode( '; ', $msg ) . '. Datele deja completate nu se pierd.</p></div>'; // phpcs:ignore -- text fix
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

	/* Câmp dinamic gol (ex.: Descriere produs) → secțiunea lui se ascunde, cu tot cu titlul. */
	require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-empty-dynamic.php';
	Pavva_W_Empty_Dynamic::init();

	/* Advanced → „Pavva – Afișare condiționată” pe orice element (ascunde dacă un câmp e gol). */
	require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-conditional.php';
	Pavva_W_Conditional::init();

	/* Pavva Motion: animații la scroll (General / Advanced → „Pavva – Animație”; Setări → Pavva – Animații). */
	require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-motion.php';
	Pavva_W_Motion::init();

	/* Viteză: imaginea din header preîncărcată, fonturi, WooCommerce doar pe magazin (Setări → Pavva – Viteză). */
	require_once PAVVA_WIDGETS_PATH . 'includes/class-pavva-perf.php';
	Pavva_W_Perf::init();
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
		/* lista de ofertă */
		'widgets/class-pavva-oferta-buton-widget.php'  => 'Pavva_Oferta_Buton_Widget',
		'widgets/class-pavva-oferta-contor-widget.php' => 'Pavva_Oferta_Contor_Widget',
		'widgets/class-pavva-oferta-lista-widget.php'  => 'Pavva_Oferta_Lista_Widget',
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

/* semnătura agenției în subsolul paginilor de setări ale pluginului */
add_filter( 'admin_footer_text', function ( $text ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && in_array( $screen->id, array( 'settings_page_pavva-perf', 'settings_page_pavva-motion' ), true ) ) {
		return 'Pavva Widgets – creat de <a href="https://imagient.ro/" target="_blank" rel="noopener">Imagient</a>';
	}
	return $text;
} );
