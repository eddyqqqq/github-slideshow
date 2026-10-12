<?php
/**
 * Pavva Products – citirea datelor folosite de câmpurile din admin.
 *
 * @package Pavva_Produse
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_P_Data {

    const META_CULORI = '_pavva_culori_finisaje';
    const META_CARACT = '_pavva_caracteristici';
    const META_DIM    = '_pavva_dimensiuni_specificatii';
    const META_AMEN   = '_pavva_variante_amenajare';
    const META_FISE   = '_pavva_fise_tehnice';
    const META_DECL   = '_pavva_declaratii_performanta';
    const META_DIM_LABELS = '_pavva_dimensiuni_etichete';
    const META_CULORI_COLS = '_pavva_culori_coloane';
    const META_DESCRIERE_SINTEZA = '_pavva_descriere_sinteza';

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
}
