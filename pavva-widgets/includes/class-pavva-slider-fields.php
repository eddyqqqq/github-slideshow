<?php
/**
 * Pavva Widgets – câmpul „Imagini slider header” (produs + categorie)
 *
 * Câmp nou (nu exista în pluginurile vechi), deci e activ mereu, chiar și cât
 * timp „Pavva Products” / „Pavva Categories” sunt încă active.
 * Slider header îl folosește când „Sursă imagini” = Din produs / Din categorie.
 *
 * @package Pavva_Widgets
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_W_Slider_Fields {

    const NONCE_ACTION = 'pavva_slider_imagini_salvare';
    const NONCE_NAME   = 'pavva_slider_imagini_nonce';
    const FIELD        = 'pavva_slider_imagini';

    public function __construct() {
        add_action( 'add_meta_boxes_product', array( $this, 'add_metabox' ) );
        add_action( 'save_post_product', array( $this, 'save_product' ), 10, 2 );

        add_action( 'product_cat_add_form_fields', array( $this, 'term_add_field' ), 20 );
        add_action( 'product_cat_edit_form_fields', array( $this, 'term_edit_field' ), 20, 2 );
        add_action( 'created_product_cat', array( $this, 'save_term' ) );
        add_action( 'edited_product_cat', array( $this, 'save_term' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
    }

    public function enqueue_admin( $hook ) {
        $screen     = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $is_product = in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && $screen && 'product' === $screen->post_type;
        $is_cat     = in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) && $screen && 'product_cat' === $screen->taxonomy;
        if ( ! $is_product && ! $is_cat ) return;

        wp_enqueue_media();
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_style( 'pavva-w-slider-images', PAVVA_WIDGETS_URL . 'assets/admin/slider-images.css', array(), PAVVA_WIDGETS_VERSION );
        wp_enqueue_script( 'pavva-w-slider-images', PAVVA_WIDGETS_URL . 'assets/admin/slider-images.js', array( 'jquery', 'jquery-ui-sortable' ), PAVVA_WIDGETS_VERSION, true );
    }

    /* ---------------- Produs ---------------- */

    public function add_metabox() {
        add_meta_box( 'pavva-slider-imagini', 'Imagini slider header', array( $this, 'box_product' ), 'product', 'normal', 'high' );
    }

    public function box_product( $post ) {
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
        echo '<p class="description">Imaginile din partea de sus a paginii produsului (widgetul <strong>Pavva – Slider header</strong>, cu „Sursă imagini: Din produs”). '
            . 'Trage de imagini ca să le schimbi ordinea. Gol = se folosesc imaginea produsului și galeria WooCommerce.</p>';
        $this->field_html( Pavva_W_Data::product_slider_images( $post->ID ) );
    }

    public function save_product( $post_id, $post ) {
        if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) return;
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( wp_is_post_revision( $post_id ) ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $images = $this->posted_images();
        if ( $images ) update_post_meta( $post_id, Pavva_W_Data::META_SLIDER, $images );
        else delete_post_meta( $post_id, Pavva_W_Data::META_SLIDER );
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
        $this->field_html( Pavva_W_Data::term_slider_images( $term->term_id ) );
        echo '<p class="description">Pentru <strong>Pavva – Slider header</strong> cu „Sursă imagini: Din categorie”. Trage de imagini ca să le schimbi ordinea. Gol = se folosește Imagine categorie.</p>';
        echo '</td></tr>';
    }

    public function save_term( $term_id ) {
        if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) return;
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) return;
        if ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'manage_categories' ) ) return;

        $images = $this->posted_images();
        if ( $images ) update_term_meta( $term_id, Pavva_W_Data::META_CAT_SLIDER, $images );
        else delete_term_meta( $term_id, Pavva_W_Data::META_CAT_SLIDER );
    }

    /* ---------------- Comun ---------------- */

    private function posted_images() {
        $raw = isset( $_POST[ self::FIELD ] ) && is_array( $_POST[ self::FIELD ] ) ? wp_unslash( $_POST[ self::FIELD ] ) : array(); // phpcs:ignore -- curățat mai jos
        return Pavva_W_Data::clean_slider_images( array_values( $raw ) );
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
