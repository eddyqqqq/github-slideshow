<?php
/**
 * Pavva Categories – citirea datelor folosite de câmpurile din admin.
 *
 * @package Pavva_Categories
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_C_Data {

    const META_HERO_ID          = '_pavva_cat_hero_image_id';
    const META_INTRO            = '_pavva_cat_intro';
    const META_BENEFITS_TITLE   = '_pavva_cat_benefits_title';
    const META_BENEFITS_CONTENT = '_pavva_cat_benefits_content';
    const META_WHY_TITLE        = '_pavva_cat_why_title';
    const META_WHY_CONTENT      = '_pavva_cat_why_content';

    const META_CAT_SLIDER = '_pavva_cat_slider_imagini';

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
}
