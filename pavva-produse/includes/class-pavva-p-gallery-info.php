<?php
/**
 * Pavva Products – informații la imaginile din galeria WooCommerce (admin)
 *
 * Sub fiecare imagine din caseta WooCommerce „Galerie produs”: numele fișierului,
 * Attachment ID și link către imaginea din Media Library.
 *
 * Galeria rămâne exact a WooCommerce: aceleași date (`_product_image_gallery`,
 * ID-uri separate prin virgulă), același selector wp.media, aceeași reordonare
 * și eliminare. Aici doar se afișează informații în plus.
 *
 * @package Pavva_Produse
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_P_Gallery_Info {

    /** Locul în care se înlocuiește ID-ul în linkurile pentru script. */
    const ID_TOKEN = '__PVP_ID__';

    public function __construct() {
        /* imaginile deja salvate (WooCommerce apelează acțiunea la fiecare imagine) */
        add_action( 'woocommerce_admin_after_product_gallery_item', array( $this, 'item_info' ), 10, 2 );
        /* imaginile adăugate acum, înainte de salvare: completate de script */
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
    }

    /* Media Library în modul grilă deschide imaginea cu ?item=ID; în modul listă, pagina imaginii. */
    private static function library_is_grid(): bool {
        $mode = get_user_option( 'media_library_mode', get_current_user_id() );
        return ! $mode || 'grid' === $mode;
    }

    /* Linkul către attachment în Media Library ('' dacă utilizatorul nu are acces). */
    private static function media_link( $id ): string {
        if ( ! current_user_can( 'upload_files' ) ) return '';
        if ( self::library_is_grid() ) {
            return admin_url( 'upload.php?item=' . absint( $id ) );
        }
        $edit = get_edit_post_link( $id, 'raw' );
        return $edit ? (string) $edit : '';
    }

    public function item_info( $post_id, $attachment_id ) {
        $id  = absint( $attachment_id );
        $att = $id ? get_post( $id ) : null;

        /* attachment șters / ID invalid: fără link */
        if ( ! $att || 'attachment' !== $att->post_type ) {
            echo '<div class="pvp-ginfo is-missing"><span class="pvp-ginfo-name">Imagine indisponibilă</span><span class="pvp-ginfo-meta">attachment lipsă</span></div>';
            return;
        }

        $file = get_attached_file( $id );
        $name = $file ? wp_basename( $file ) : '';
        if ( '' === $name ) {
            $url  = wp_get_attachment_url( $id );
            $name = $url ? wp_basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) : '#' . $id;
        }

        $note = '';
        if ( ! wp_attachment_is_image( $id ) ) {
            $note = 'nu e imagine';
        } elseif ( ! $file || ! file_exists( $file ) ) {
            $note = 'fișier lipsă';
        }

        $link = self::media_link( $id );

        echo '<div class="pvp-ginfo' . ( $note ? ' has-note' : '' ) . '" data-pvp-id="' . esc_attr( $id ) . '">';
        echo '<span class="pvp-ginfo-name" title="' . esc_attr( $name . ( $note ? ' – ' . $note : '' ) ) . '">' . esc_html( $name ) . '</span>';
        echo '<span class="pvp-ginfo-meta"><span class="pvp-ginfo-id">ID: ' . esc_html( $id ) . '</span>';
        if ( $note ) {
            echo '<span class="pvp-ginfo-note dashicons dashicons-warning" title="' . esc_attr( ucfirst( $note ) ) . '" aria-label="' . esc_attr( ucfirst( $note ) ) . '"></span>';
        }
        if ( $link ) {
            echo '<a class="pvp-ginfo-link" href="' . esc_url( $link ) . '" target="_blank" rel="noopener" title="Vezi în Media Library" aria-label="' . esc_attr( 'Vezi în Media Library: ' . $name ) . '"><span class="dashicons dashicons-external" aria-hidden="true"></span></a>';
        }
        echo '</span></div>';
    }

    public function enqueue( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) return;
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'product' !== $screen->post_type ) return;

        wp_enqueue_style( 'pavva-produse-gallery-info', PAVVA_Produse::url( 'assets/gallery-info.css' ), array( 'dashicons' ), PAVVA_Produse::VERSION );
        wp_enqueue_script( 'pavva-produse-gallery-info', PAVVA_Produse::url( 'assets/gallery-info.js' ), array( 'jquery' ), PAVVA_Produse::VERSION, true );

        $can  = current_user_can( 'upload_files' );
        $grid = self::library_is_grid();
        wp_localize_script(
            'pavva-produse-gallery-info',
            'PavvaGalleryInfo',
            array(
                'token'   => self::ID_TOKEN,
                /* modul grilă: Media Library cu imaginea deschisă; modul listă: linkul de editare primit de la WordPress */
                'link'    => ( $can && $grid ) ? admin_url( 'upload.php?item=' . self::ID_TOKEN ) : '',
                'useEdit' => ( $can && ! $grid ) ? 1 : 0,
                'missing' => 'Imagine indisponibilă',
                'noAtt'   => 'attachment lipsă',
                'notImg'  => 'Nu e imagine',
                'title'   => 'Vezi în Media Library',
            )
        );
    }
}
