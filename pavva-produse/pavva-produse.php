<?php
/**
 * Plugin Name:       Pavva Products
 * Plugin URI:        https://imagient.ro/
 * Description:       Câmpurile produselor Pavva (Caracteristici, Culori și finisaje, Dimensiuni, Variante de amenajare, Fișe tehnice, Declarații de performanță) și informații la imaginile din galeria WooCommerce. Afișarea pe site o fac widgeturile din „Pavva Widgets”. Creat de Imagient (imagient.ro).
 * Version:           5.1.1
 * Author:            Imagient
 * Author URI:        https://imagient.ro/
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Text Domain:       pavva-produse
 * License:           Proprietar – creat de Imagient pentru Pavva.
 *
 * @package Pavva_Produse
 * @author  Imagient <https://imagient.ro/>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Același nume de clasă ca vechiul plugin (4.x), ca să-l recunoască „Pavva Widgets”.
 * De la 5.0.0 pluginul conține doar câmpurile din admin; widgeturile sunt în „Pavva Widgets”.
 * Datele (cheile din baza de date) sunt aceleași ca în 4.10.30.
 */
final class PAVVA_Produse {
    const VERSION = '5.1.1';

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
        require_once __DIR__ . '/includes/class-pavva-p-gallery-info.php';
        new Pavva_P_Fields();
        /* Galeria WooCommerce: nume fișier, ID și link Media Library la fiecare imagine. */
        new Pavva_P_Gallery_Info();
    }

    public static function notice_woocommerce() {
        echo '<div class="notice notice-error"><p><strong>Pavva Products</strong> are nevoie de WooCommerce activ.</p></div>';
    }
}

/* prioritate 30: după „Pavva Widgets” (care își încarcă propriile clase la 20) */
add_action( 'plugins_loaded', array( 'PAVVA_Produse', 'init' ), 30 );
