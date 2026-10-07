<?php
/**
 * Plugin Name:       Pavva Categories
 * Description:       Câmpurile categoriilor de produse Pavva (Imagine categorie, Text intro, Avantaje, De ce să alegi, Imagini slider header). Afișarea pe site o fac widgeturile și etichetele dinamice din „Pavva Widgets”. Creat de imagient.
 * Version:           2.0.0
 * Author:            imagient
 * Author URI:        http://imagient.com/
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Text Domain:       pavva-categories
 * License:           Proprietar – creat de imagient pentru Pavva.
 *
 * @package Pavva_Categories
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Același nume de clasă ca vechiul plugin (1.x), ca să-l recunoască „Pavva Widgets”.
 * De la 2.0.0 pluginul conține doar câmpurile din admin; widgeturile sunt în „Pavva Widgets”.
 * Datele (cheile din baza de date) sunt aceleași ca în 1.0.2.
 */
final class PAVVA_Categories {
    const VERSION = '2.0.0';

    const META_HERO_ID          = '_pavva_cat_hero_image_id';
    const META_INTRO            = '_pavva_cat_intro';
    const META_BENEFITS_TITLE   = '_pavva_cat_benefits_title';
    const META_BENEFITS_CONTENT = '_pavva_cat_benefits_content';
    const META_WHY_TITLE        = '_pavva_cat_why_title';
    const META_WHY_CONTENT      = '_pavva_cat_why_content';

    public static function url( $path = '' ) {
        return plugin_dir_url( __FILE__ ) . ltrim( $path, '/' );
    }

    public static function init() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( __CLASS__, 'notice_woocommerce' ) );
            return;
        }
        require_once __DIR__ . '/includes/class-pavva-c-data.php';
        require_once __DIR__ . '/includes/class-pavva-c-fields.php';
        require_once __DIR__ . '/includes/class-pavva-c-slider.php';
        new Pavva_C_Fields();
        /* „Pavva Widgets” 3.x avea încă acest câmp: îl pornim doar dacă nu e deja acolo. */
        if ( ! class_exists( 'Pavva_W_Slider_Fields' ) ) {
            new Pavva_C_Slider();
        }
    }

    public static function notice_woocommerce() {
        echo '<div class="notice notice-error"><p><strong>Pavva Categories</strong> are nevoie de WooCommerce activ.</p></div>';
    }
}

/* prioritate 30: după „Pavva Widgets” (care își încarcă propriile clase la 20) */
add_action( 'plugins_loaded', array( 'PAVVA_Categories', 'init' ), 30 );
