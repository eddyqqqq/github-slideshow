<?php
/**
 * Pavva Categories – câmpul „Imagini slider header” al categoriei
 *
 * Folosit de widgetul „Pavva – Slider header” (din „Pavva Widgets”).
 *
 * @package Pavva_Categories
 * @author  Imagient <https://imagient.ro/>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_C_Slider {

    const NONCE_ACTION = 'pavva_slider_imagini_salvare';
    const NONCE_NAME   = 'pavva_slider_imagini_nonce';
    const FIELD        = 'pavva_slider_imagini';

    public function __construct() {
        add_action( 'product_cat_add_form_fields', array( $this, 'term_add_field' ), 20 );
        add_action( 'product_cat_edit_form_fields', array( $this, 'term_edit_field' ), 20, 2 );
        add_action( 'created_product_cat', array( $this, 'save_term' ) );
        add_action( 'edited_product_cat', array( $this, 'save_term' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
    }

    public function enqueue_admin( $hook ) {
        $screen     = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $is_cat     = in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) && $screen && 'product_cat' === $screen->taxonomy;
        if ( ! $is_cat ) return;

        wp_enqueue_media();
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_style( 'pavva-categories-slider-images', PAVVA_Categories::url( 'assets/slider-images.css' ), array(), PAVVA_Categories::VERSION );
        wp_enqueue_script( 'pavva-categories-slider-images', PAVVA_Categories::url( 'assets/slider-images.js' ), array( 'jquery', 'jquery-ui-sortable' ), PAVVA_Categories::VERSION, true );
    }

    /* ---------------- Categorie ---------------- */

    public function term_add_field() {
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
        echo '<div class="form-field"><label>Imagini slider header</label>';
        $this->field_html( array() );
        echo '<p>Pentru <strong>Pavva – Slider header</strong> cu „Sursă imagini: Din categorie”. Gol = se folosește Imagine categorie.</p></div>';
    }

    public function term_edit_field( $term ) {
        echo '<tr class="form-field"><th scope="row"><label>Imagini slider header</label></th><td>';
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
        $this->field_html( Pavva_C_Data::term_slider_images( $term->term_id ) );
        echo '<p class="description">Pentru <strong>Pavva – Slider header</strong> cu „Sursă imagini: Din categorie”. Trage de imagini ca să le schimbi ordinea. Gol = se folosește Imagine categorie.</p>';
        echo '</td></tr>';
    }

    public function save_term( $term_id ) {
        if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) return;
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) return;
        if ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'manage_categories' ) ) return;

        $images = $this->posted_images();
        if ( $images ) update_term_meta( $term_id, Pavva_C_Data::META_CAT_SLIDER, $images );
        else delete_term_meta( $term_id, Pavva_C_Data::META_CAT_SLIDER );
    }

    /* ---------------- Comun ---------------- */

    private function posted_images() {
        $raw = isset( $_POST[ self::FIELD ] ) && is_array( $_POST[ self::FIELD ] ) ? wp_unslash( $_POST[ self::FIELD ] ) : array(); // phpcs:ignore -- curățat mai jos
        return Pavva_C_Data::clean_slider_images( array_values( $raw ) );
    }

    private function thumb( $id ) {
        $url = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';
        return $url ? '<img src="' . esc_url( $url ) . '" alt="">' : '';
    }

    private function row_html( $index, $id, $mobile ) {
        $base = self::FIELD . '[' . $index . ']';
        $html  = '<li class="pvsi-item">';
        $html .= '<input type="hidden" class="pvsi-id" name="' . esc_attr( $base . '[id]' ) . '" value="' . esc_attr( $id ) . '">';
        $html .= '<input type="hidden" class="pvsi-mobile" name="' . esc_attr( $base . '[mobile]' ) . '" value="' . esc_attr( $mobile ) . '">';
        $html .= '<span class="pvsi-main" title="Trage ca să schimbi ordinea">' . $this->thumb( $id ) . '</span>';
        $html .= '<span class="pvsi-mob' . ( $mobile ? ' has-image' : '' ) . '">';
        $html .= '<span class="pvsi-mob-thumb">' . $this->thumb( $mobile ) . '</span>';
        $html .= '<button type="button" class="button-link pvsi-pick-mobile">' . ( $mobile ? 'Schimbă mobil' : '+ Imagine mobil' ) . '</button>';
        $html .= '<button type="button" class="button-link-delete pvsi-remove-mobile"' . ( $mobile ? '' : ' hidden' ) . '>Fără mobil</button>';
        $html .= '</span>';
        $html .= '<button type="button" class="pvsi-remove" aria-label="Elimină imaginea">&times;</button>';
        $html .= '</li>';
        return $html;
    }

    private function field_html( $images ) {
        echo '<div class="pvsi" data-name="' . esc_attr( self::FIELD ) . '">';
        echo '<ul class="pvsi-list">';
        foreach ( array_values( $images ) as $i => $img ) {
            echo $this->row_html( $i, $img['id'], $img['mobile'] ); // phpcs:ignore -- escapat în row_html()
        }
        echo '</ul>';
        echo '<p><button type="button" class="button button-secondary pvsi-add">+ Adaugă imagini</button></p>';
        echo '<script type="text/html" class="pvsi-template">' . $this->row_html( '{{INDEX}}', '', '' ) . '</script>'; // phpcs:ignore -- escapat
        echo '</div>';
    }
}
