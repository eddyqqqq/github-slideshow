<?php
/**
 * Pavva Widgets – acces la datele produselor și categoriilor
 *
 * Aceleași câmpuri (meta keys) ca pluginurile „Pavva Products” și
 * „Pavva Categories”, deci datele existente se citesc fără nicio conversie.
 * Funcțiile de mai jos sunt copiate identic din acele pluginuri.
 *
 * @package Pavva_Widgets
 * @author  Imagient <https://imagient.ro/>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_W_Data {

    /* ---------- Produs (identice cu Pavva Products 4.10.30) ---------- */
    const META_CULORI = '_pavva_culori_finisaje';
    const META_CARACT = '_pavva_caracteristici';
    const META_DIM    = '_pavva_dimensiuni_specificatii';
    const META_AMEN   = '_pavva_variante_amenajare';
    const META_FISE   = '_pavva_fise_tehnice';
    const META_DECL   = '_pavva_declaratii_performanta';
    const META_DIM_LABELS = '_pavva_dimensiuni_etichete';
    const META_CULORI_COLS = '_pavva_culori_coloane';
    const META_DESCRIERE_SINTEZA = '_pavva_descriere_sinteza';

    /* ---------- Imagini Slider header (noi în Pavva Widgets 3.1.0) ---------- */
    const META_CAT_SLIDER = '_pavva_cat_slider_imagini';  // categorie (term meta)

    /* ---------- Categorie (identice cu Pavva Categories 1.0.2) ---------- */
    const META_HERO_ID          = '_pavva_cat_hero_image_id';
    const META_INTRO            = '_pavva_cat_intro';
    const META_BENEFITS_TITLE   = '_pavva_cat_benefits_title';
    const META_BENEFITS_CONTENT = '_pavva_cat_benefits_content';
    const META_WHY_TITLE        = '_pavva_cat_why_title';
    const META_WHY_CONTENT      = '_pavva_cat_why_content';

    /* Lista unui câmp repetabil al produsului (array gol dacă nu există). */
    public static function items( $post_id, $key ) {
        $v = get_post_meta( $post_id, $key, true );
        return is_array( $v ) ? $v : array();
    }

    public static function dimension_label_defaults(): array {
        return array(
            'dimensiuni' => 'Dimensiuni (cm)',
            'grosime'    => 'Grosime (cm)',
            'utilizare'  => 'Utilizare recomandată',
            'greutate'   => 'Greutate (kg/mp)',
            'necesar'    => 'Necesar pe mp cca (buc)',
            'cantitate'  => 'Cantitate (mp/palet)',
        );
    }

    public static function dimension_labels( $product_id = 0 ): array {
        $product_id = $product_id ? absint( $product_id ) : self::resolve_product_id();
        $defaults = self::dimension_label_defaults();

        if ( ! $product_id ) return $defaults;

        $saved = get_post_meta( $product_id, self::META_DIM_LABELS, true );
        if ( ! is_array( $saved ) ) return $defaults;

        foreach ( $defaults as $key => $default_label ) {
            if ( ! isset( $saved[ $key ] ) || '' === trim( (string) $saved[ $key ] ) ) {
                $saved[ $key ] = $default_label;
            }
        }

        return array_merge( $defaults, $saved );
    }

    public static function item_is_visible( $item ): bool {
        if ( ! is_array( $item ) ) return true;
        if ( ! array_key_exists( 'vizibil', $item ) ) return true;

        return ! in_array( $item['vizibil'], array( 0, '0', false, 'nu', 'no' ), true );
    }

    public static function visible_items( $items ): array {
        if ( ! is_array( $items ) ) return array();

        return array_values( array_filter( $items, function( $item ) {
            return self::item_is_visible( $item );
        } ) );
    }

    public static function dimension_item_specs( $item, $product_id = 0 ): array {
        if ( ! is_array( $item ) ) return array();

        // Structura nouă: număr nelimitat de perechi etichetă / valoare.
        if ( isset( $item['specificatii'] ) && is_array( $item['specificatii'] ) ) {
            $out = array();

            foreach ( $item['specificatii'] as $spec ) {
                if ( ! is_array( $spec ) ) continue;

                $label = isset( $spec['eticheta'] ) ? (string) $spec['eticheta'] : '';
                $value = isset( $spec['valoare'] ) ? (string) $spec['valoare'] : '';

                if ( '' === trim( $label ) && '' === trim( $value ) ) continue;

                $out[] = array(
                    'eticheta' => $label,
                    'valoare'  => $value,
                );
            }

            return $out;
        }

        // Compatibilitate 100% cu produsele create înainte de 4.10.23.
        $labels = self::dimension_labels( $product_id );
        $legacy_keys = array( 'dimensiuni', 'grosime', 'utilizare', 'greutate', 'necesar', 'cantitate' );
        $out = array();

        foreach ( $legacy_keys as $key ) {
            if ( ! isset( $item[ $key ] ) || '' === trim( (string) $item[ $key ] ) ) continue;

            $out[] = array(
                'eticheta' => isset( $labels[ $key ] ) ? $labels[ $key ] : $key,
                'valoare'  => (string) $item[ $key ],
            );
        }

        return $out;
    }

    /* Lista curată de imagini slider: array( array( 'id' => int, 'mobile' => int ), ... ). */
    public static function clean_slider_images( $raw ): array {
        if ( ! is_array( $raw ) ) return array();
        $out = array();
        foreach ( $raw as $row ) {
            if ( ! is_array( $row ) ) continue;
            $id = isset( $row['id'] ) ? absint( $row['id'] ) : 0;
            if ( ! $id ) continue;
            $out[] = array(
                'id'     => $id,
                'mobile' => isset( $row['mobile'] ) ? absint( $row['mobile'] ) : 0,
            );
        }
        return $out;
    }

    public static function term_slider_images( $term_id ): array {
        return $term_id ? self::clean_slider_images( get_term_meta( $term_id, self::META_CAT_SLIDER, true ) ) : array();
    }

    /* Doar galeria WooCommerce a produsului (fără imaginea principală), în ordinea din galerie. */
    public static function product_gallery_images( $product_id ): array {
        if ( ! $product_id ) return array();
        $ids = array();
        foreach ( explode( ',', (string) get_post_meta( $product_id, '_product_image_gallery', true ) ) as $id ) {
            $id = absint( $id );
            if ( $id && ! in_array( $id, $ids, true ) ) $ids[] = $id;
        }
        return array_map( function ( $id ) { return array( 'id' => $id, 'mobile' => 0 ); }, $ids );
    }

    public static function instance_data( $meta_key ) {
        $id = self::resolve_product_id();
        if ( ! $id ) return array();
        $value = get_post_meta( $id, $meta_key, true );
        return is_array( $value ) ? $value : array();
    }

    public static function resolve_product_id() {
        global $product, $post;

        if ( $product instanceof \WC_Product ) return $product->get_id();

        $queried = get_queried_object_id();
        if ( $queried && 'product' === get_post_type( $queried ) ) return $queried;

        if ( $post instanceof \WP_Post && 'product' === $post->post_type ) return $post->ID;

        $id = get_the_ID();
        if ( $id && 'product' === get_post_type( $id ) ) return $id;

        // În editorul Elementor, documentul poate fi template-ul. Folosim produsul setat pentru Preview.
        if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->documents ) ) {
            $preview_id = isset( $_GET['preview_id'] ) ? absint( $_GET['preview_id'] ) : 0;
            if ( $preview_id && 'product' === get_post_type( $preview_id ) ) return $preview_id;
        }

        return 0;
    }

    public static function current_term_id() {
        $queried = get_queried_object();

        if ( $queried instanceof WP_Term && 'product_cat' === $queried->taxonomy ) {
            return absint( $queried->term_id );
        }

        $slug = get_query_var( 'product_cat' );
        if ( $slug ) {
            $term = get_term_by( 'slug', $slug, 'product_cat' );
            if ( $term instanceof WP_Term ) return absint( $term->term_id );
        }

        return 0;
    }
}
