<?php
/**
 * Pavva Widgets – verificare înainte de ștergerea pluginurilor vechi
 *
 * Pe pagina Plugins arată în ce pagini / template-uri Elementor mai sunt folosite
 * widgeturile vechi din „Pavva Products” și „Pavva Categories”. După ștergerea
 * pluginurilor, acele widgeturi nu mai apar, deci trebuie înlocuite înainte.
 *
 * @package Pavva_Widgets
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_W_Migration {

    const CACHE_KEY = 'pavva_w_old_widgets_usage';

    /* Widgeturile vechi și ce le înlocuiește în Pavva Widgets. */
    public static function replacements(): array {
        return array(
            'pavva-galerie-produs'            => array( 'Galerie produs', 'Slider header, „Sursă imagini: Din produs”' ),
            'pavva-descriere-scurta'          => array( 'Descriere scurtă', 'Text Editor / Heading cu Pavva → Descriere scurtă' ),
            'pavva-descriere-sinteza'         => array( 'Descriere sinteza', 'Text Editor cu Pavva → Descriere sinteză' ),
            'pavva-culori-card'               => array( 'Culori card', 'Grilă produse (culori +N)' ),
            'pavva-caracteristici'            => array( 'Caracteristici', 'Pavva – Caracteristici' ),
            'pavva-culori-finisaje'           => array( 'Culori și finisaje', 'Pavva – Culori și finisaje' ),
            'pavva-dimensiuni'                => array( 'Dimensiuni și specificații', 'Pavva – Dimensiuni și specificații' ),
            'pavva-amenajare'                 => array( 'Variante de amenajare', 'Pavva – Acordeon produs' ),
            'pavva-fise-tehnice'              => array( 'Fișe tehnice', 'Pavva – Acordeon produs' ),
            'pavva-declaratii-performanta'    => array( 'Declarații de performanță', 'Pavva – Acordeon produs' ),
            'pavva-descriere-produs'          => array( 'Descriere produs', 'Text Editor cu Pavva → Descriere produs' ),
            'pavva-documente'                 => array( 'Documente produs', 'Pavva – Acordeon produs' ),
            'pavva-category-hero'             => array( 'Imagine categorie', 'Image cu Pavva → Imagine categorie' ),
            'pavva-category-title'            => array( 'Titlu categorie', 'Heading cu Pavva → Nume categorie' ),
            'pavva-category-intro'            => array( 'Text intro categorie', 'Text Editor cu Pavva → Text intro categorie' ),
            'pavva-category-benefits-title'   => array( 'Titlu secțiune Avantaje', 'Heading cu Pavva → Titlu secțiune Avantaje' ),
            'pavva-category-benefits-content' => array( 'Conținut Avantaje', 'Text Editor cu Pavva → Conținut Avantaje' ),
            'pavva-category-why-title'        => array( 'Titlu secțiune De ce să alegi', 'Heading cu Pavva → Titlu De ce să alegi' ),
            'pavva-category-why-content'      => array( 'Conținut De ce să alegi', 'Text Editor cu Pavva → Conținut De ce să alegi' ),
            'pavva-category-benefits'         => array( 'Avantaje categorie', 'Heading + Text Editor cu Pavva → Avantaje' ),
            'pavva-category-why'              => array( 'De ce să alegi categoria', 'Heading + Text Editor cu Pavva → De ce să alegi' ),
        );
    }

    public static function init() {
        add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
        /* lista se recalculează când se salvează o pagină / un template */
        add_action( 'save_post', array( __CLASS__, 'flush' ) );
        add_action( 'deleted_post', array( __CLASS__, 'flush' ) );
    }

    public static function flush() {
        delete_transient( self::CACHE_KEY );
    }

    /* array( post_id => array( widget_type, ... ) ) */
    public static function usage(): array {
        $cached = get_transient( self::CACHE_KEY );
        if ( is_array( $cached ) ) return $cached;

        global $wpdb;
        $known = self::replacements();
        $rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->prepare(
                "SELECT pm.post_id, pm.meta_value FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_elementor_data'
                   AND pm.meta_value LIKE %s
                   AND p.post_type <> 'revision'
                   AND p.post_status NOT IN ( 'trash', 'auto-draft', 'inherit' )",
                '%' . $wpdb->esc_like( '"widgetType":"pavva-' ) . '%'
            )
        );

        $out = array();
        foreach ( (array) $rows as $row ) {
            if ( ! preg_match_all( '/"widgetType":"(pavva-[a-z0-9-]+)"/', (string) $row->meta_value, $m ) ) continue;
            $types = array_values( array_intersect( array_unique( $m[1] ), array_keys( $known ) ) );
            if ( $types ) $out[ (int) $row->post_id ] = $types;
        }

        set_transient( self::CACHE_KEY, $out, 10 * MINUTE_IN_SECONDS );
        return $out;
    }

    public static function notice() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'plugins' !== $screen->id || ! current_user_can( 'activate_plugins' ) ) return;

        $old_active = class_exists( 'PAVVA_Produse' ) || class_exists( 'PAVVA_Categories' );
        $usage      = self::usage();

        if ( ! $usage ) {
            if ( $old_active ) {
                echo '<div class="notice notice-success"><p><strong>Pavva Widgets:</strong> nicio pagină și niciun template nu mai folosește widgeturile vechi din <strong>Pavva Products</strong> / <strong>Pavva Categories</strong>. Le poți dezactiva și șterge în siguranță.</p></div>';
            }
            return;
        }

        $known = self::replacements();
        $class = $old_active ? 'notice-warning' : 'notice-error';
        echo '<div class="notice ' . esc_attr( $class ) . '"><p><strong>Pavva Widgets:</strong> ';
        echo $old_active
            ? 'înainte să ștergi <strong>Pavva Products</strong> / <strong>Pavva Categories</strong>, înlocuiește widgeturile vechi din paginile de mai jos (altfel acele secțiuni dispar):'
            : 'paginile de mai jos folosesc widgeturi din pluginurile vechi, care nu mai sunt active, deci acele secțiuni nu apar. Înlocuiește-le:';
        echo '</p><ul style="list-style:disc;margin-left:20px">';
        foreach ( $usage as $post_id => $types ) {
            $title = get_the_title( $post_id );
            $title = '' !== $title ? $title : '#' . $post_id;
            $edit  = admin_url( 'post.php?post=' . $post_id . '&action=elementor' );
            $list  = array();
            foreach ( $types as $type ) {
                $list[] = esc_html( $known[ $type ][0] ) . ' → <em>' . esc_html( $known[ $type ][1] ) . '</em>';
            }
            echo '<li><a href="' . esc_url( $edit ) . '"><strong>' . esc_html( $title ) . '</strong></a>: ' . implode( '; ', $list ) . '</li>'; // phpcs:ignore -- escapat mai sus
        }
        echo '</ul></div>';
    }
}
