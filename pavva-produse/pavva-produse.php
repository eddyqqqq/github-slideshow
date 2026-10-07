<?php
/**
 * Plugin Name:       Pavva Products
 * Description:       Câmpurile produselor Pavva (Caracteristici, Culori și finisaje, Dimensiuni, Variante de amenajare, Fișe tehnice, Declarații de performanță, Imagini slider header). Afișarea pe site o fac widgeturile din „Pavva Widgets”. Creat de imagient.
 * Version:           5.0.0
 * Author:            imagient
 * Author URI:        http://imagient.com/
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Text Domain:       pavva-produse
 * License:           Proprietar – creat de imagient pentru Pavva.
 *
 * @package Pavva_Produse
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Același nume de clasă ca vechiul plugin (4.x), ca să-l recunoască „Pavva Widgets”.
 * De la 5.0.0 pluginul conține doar câmpurile din admin; widgeturile sunt în „Pavva Widgets”.
 * Datele (cheile din baza de date) sunt aceleași ca în 4.10.30.
 */
final class PAVVA_Produse {
    const VERSION = '5.0.0';

    const META_CULORI = '_pavva_culori_finisaje';
    const META_CARACT = '_pavva_caracteristici';
    const META_DIM    = '_pavva_dimensiuni_specificatii';
    const META_AMEN   = '_pavva_variante_amenajare';
    const META_FISE   = '_pavva_fise_tehnice';
    const META_DECL   = '_pavva_declaratii_performanta';
    const META_DIM_LABELS = '_pavva_dimensiuni_etichete';
    const META_CULORI_COLS = '_pavva_culori_coloane';
    const META_DESCRIERE_SINTEZA = '_pavva_descriere_sinteza';

    public static function url( $path = '' ) {
        return plugin_dir_url( __FILE__ ) . ltrim( $path, '/' );
    }

    public static function init() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( __CLASS__, 'notice_woocommerce' ) );
            return;
        }
        require_once __DIR__ . '/includes/class-pavva-p-data.php';
        require_once __DIR__ . '/includes/class-pavva-p-fields.php';
        require_once __DIR__ . '/includes/class-pavva-p-slider.php';
        new Pavva_P_Fields();
        /* „Pavva Widgets” 3.x avea încă acest câmp: îl pornim doar dacă nu e deja acolo. */
        if ( ! class_exists( 'Pavva_W_Slider_Fields' ) ) {
            new Pavva_P_Slider();
        }
    }

    public static function notice_woocommerce() {
        echo '<div class="notice notice-error"><p><strong>Pavva Products</strong> are nevoie de WooCommerce activ.</p></div>';
    }
}

/* prioritate 30: după „Pavva Widgets” (care își încarcă propriile clase la 20) */
add_action( 'plugins_loaded', array( 'PAVVA_Produse', 'init' ), 30 );
