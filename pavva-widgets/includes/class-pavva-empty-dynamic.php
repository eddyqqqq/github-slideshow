<?php
/**
 * Pavva Widgets – secțiuni goale și pentru câmpurile dinamice
 *
 * Un widget Elementor obișnuit (Text Editor, Heading…) care afișează un câmp
 * dinamic (ex.: Pavva → Descriere produs) și pentru produsul curent câmpul e gol
 * primește aceeași clasă ca widgeturile Pavva fără date (`pavva-w-is-empty`).
 * Astfel containerul lui se ascunde cu tot cu titlul pus separat
 * (vezi assets/sectiuni-goale.css).
 *
 * La fel pentru widgeturile Elementor Pro „Product Content” / „Post Content”
 * (descrierea produsului) și „Short Description” / „Post Excerpt”.
 *
 * Se aplică doar pe site, nu în editor, și doar widgeturilor care chiar
 * au un câmp dinamic (textele scrise de mână nu sunt atinse).
 *
 * @package Pavva_Widgets
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_W_Empty_Dynamic {

    /* Widgeturile care afișează descrierea / descrierea scurtă a produsului direct. */
    const CONTENT_WIDGETS = array( 'woocommerce-product-content', 'theme-post-content' );
    const EXCERPT_WIDGETS = array( 'woocommerce-product-short-description', 'theme-post-excerpt' );

    public static function init() {
        add_action( 'elementor/frontend/widget/before_render', array( __CLASS__, 'mark' ) );
    }

    private static function is_editor(): bool {
        if ( ! class_exists( '\Elementor\Plugin' ) ) return false;
        $p = \Elementor\Plugin::$instance;
        return ( isset( $p->editor ) && $p->editor->is_edit_mode() ) || ( isset( $p->preview ) && $p->preview->is_preview_mode() );
    }

    /* Text gol: fără litere / cifre și fără imagini, video sau iframe. */
    private static function blank( $value ): bool {
        if ( is_array( $value ) ) {
            /* ex.: imagine dinamică { id, url } */
            return empty( $value['url'] ) && empty( $value['id'] );
        }
        $html = (string) $value;
        if ( preg_match( '/<(img|video|iframe|svg|picture|audio|object|embed)\b/i', $html ) ) return false;
        $text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );
        return '' === trim( preg_replace( '/[\s\x{00A0}\x{200B}]+/u', '', $text ) );
    }

    public static function mark( $widget ) {
        if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) || self::is_editor() ) return;

        $name  = $widget->get_name();
        $empty = null;

        if ( in_array( $name, self::CONTENT_WIDGETS, true ) || in_array( $name, self::EXCERPT_WIDGETS, true ) ) {
            $pid = class_exists( 'Pavva_W_Data' ) ? (int) Pavva_W_Data::resolve_product_id() : 0;
            if ( ! $pid ) return;
            $field = in_array( $name, self::CONTENT_WIDGETS, true ) ? 'post_content' : 'post_excerpt';
            $empty = self::blank( get_post_field( $field, $pid ) );
        } else {
            /* doar câmpurile dinamice ale widgetului (setările __dynamic__) */
            $dynamic = $widget->get_settings( '__dynamic__' );
            if ( empty( $dynamic ) || ! is_array( $dynamic ) ) return;
            /* doar conținutul (text, imagine); linkurile dinamice nu contează */
            $keys = array_values( array_filter( array_keys( array_filter( $dynamic ) ), function ( $k ) {
                return false === strpos( $k, 'link' ) && false === strpos( $k, 'url' ) && 0 !== strpos( $k, 'pavva_cond' );
            } ) );
            if ( ! $keys ) return;
            $empty = true;
            foreach ( $keys as $key ) {
                if ( ! self::blank( $widget->get_settings_for_display( $key ) ) ) {
                    $empty = false;
                    break;
                }
            }
        }

        if ( $empty ) {
            $widget->add_render_attribute( '_wrapper', 'class', 'pavva-w-is-empty' );
            wp_enqueue_style( 'pavva-sectiuni-goale' );
            wp_enqueue_script( 'pavva-sectiuni-goale' );
        }
    }
}
