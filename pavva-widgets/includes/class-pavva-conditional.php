<?php
/**
 * Pavva Widgets – Afișare condiționată pe orice element Elementor
 *
 * În Advanced, la orice widget, container, secțiune sau coloană:
 * „Pavva – Afișare condiționată”: alegi un câmp dinamic (ex.: Descriere produs,
 * Descriere sinteză, câmpuri din alte pluginuri, imagine categorie…) și regula:
 *   – Ascunde dacă e gol
 *   – Afișează doar dacă e gol
 * Pe site, elementul ascuns nu mai este trimis deloc în pagină (cu tot conținutul
 * lui). În editor rămâne vizibil, ca să-l poți lucra.
 *
 * Folosește filtrele oficiale Elementor: setările se adaugă după secțiunea
 * „Responsive” din Advanced, iar decizia se ia în
 * `elementor/frontend/{widget|container|section|column}/should_render`.
 *
 * @package Pavva_Widgets
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;

final class Pavva_W_Conditional {

    /* elementele Elementor care primesc setarea (widgeturile prin „common”) */
    const STACKS = array( 'common', 'common-optimized', 'container', 'section', 'column' );
    const TYPES  = array( 'widget', 'container', 'section', 'column' );

    public static function init() {
        foreach ( self::STACKS as $stack ) {
            add_action( "elementor/element/{$stack}/_section_responsive/after_section_end", array( __CLASS__, 'controls' ), 10, 2 );
        }
        foreach ( self::TYPES as $type ) {
            add_filter( "elementor/frontend/{$type}/should_render", array( __CLASS__, 'should_render' ), 10, 2 );
        }
    }

    public static function controls( $element, $args ) {
        /* o singură dată pe element (protecție dacă un hook rulează de două ori) */
        if ( $element->get_controls( 'pavva_cond_on' ) ) return;

        $categories = array( 'text' );
        if ( class_exists( '\Elementor\Modules\DynamicTags\Module' ) ) {
            $m          = '\Elementor\Modules\DynamicTags\Module';
            $categories = array( $m::TEXT_CATEGORY, $m::URL_CATEGORY, $m::NUMBER_CATEGORY, $m::POST_META_CATEGORY );
        }
        $image_categories = class_exists( '\Elementor\Modules\DynamicTags\Module' ) ? array( \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY ) : array( 'image' );

        $element->start_controls_section(
            'pavva_cond_section',
            array(
                'label' => 'Pavva – Afișare condiționată',
                'tab'   => Controls_Manager::TAB_ADVANCED,
            )
        );

        $element->add_control(
            'pavva_cond_on',
            array(
                'label'        => 'Activează condiția',
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => 'Da',
                'label_off'    => 'Nu',
                'return_value' => 'yes',
                'default'      => '',
            )
        );

        $element->add_control(
            'pavva_cond_source',
            array(
                'label'     => 'Ce verific',
                'type'      => Controls_Manager::SELECT,
                'default'   => 'text',
                'options'   => array(
                    'text'  => 'Un text / câmp',
                    'image' => 'O imagine',
                ),
                'condition' => array( 'pavva_cond_on' => 'yes' ),
            )
        );

        $element->add_control(
            'pavva_cond_value',
            array(
                'label'       => 'Câmpul verificat',
                'description' => 'Apasă iconița de câmpuri dinamice și alege câmpul (ex.: Pavva → Descriere produs).',
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'dynamic'     => array(
                    'active'     => true,
                    'categories' => $categories,
                ),
                'condition'   => array( 'pavva_cond_on' => 'yes', 'pavva_cond_source' => 'text' ),
            )
        );

        $element->add_control(
            'pavva_cond_image',
            array(
                'label'       => 'Imaginea verificată',
                'description' => 'Alege imaginea dinamic (ex.: Pavva → Imagine categorie, Featured Image).',
                'type'        => Controls_Manager::MEDIA,
                'dynamic'     => array(
                    'active'     => true,
                    'categories' => $image_categories,
                ),
                'default'     => array( 'url' => '', 'id' => 0 ),
                'condition'   => array( 'pavva_cond_on' => 'yes', 'pavva_cond_source' => 'image' ),
            )
        );

        $element->add_control(
            'pavva_cond_rule',
            array(
                'label'     => 'Regulă',
                'type'      => Controls_Manager::SELECT,
                'default'   => 'hide_empty',
                'options'   => array(
                    'hide_empty' => 'Ascunde dacă e gol',
                    'show_empty' => 'Afișează doar dacă e gol',
                ),
                'condition' => array( 'pavva_cond_on' => 'yes' ),
            )
        );

        $element->add_control(
            'pavva_cond_note',
            array(
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => 'Condiția se aplică pe site (și în Preview). În editor elementul rămâne vizibil, ca să-l poți lucra. Elementul ascuns dispare cu tot ce conține.',
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                'condition'       => array( 'pavva_cond_on' => 'yes' ),
            )
        );

        $element->end_controls_section();
    }

    private static function is_editor(): bool {
        if ( ! class_exists( '\Elementor\Plugin' ) ) return false;
        $p = \Elementor\Plugin::$instance;
        return isset( $p->editor ) && $p->editor->is_edit_mode();
    }

    /* Gol: fără litere / cifre și fără imagini, video sau iframe. */
    public static function blank( $value ): bool {
        if ( is_array( $value ) ) {
            return empty( $value['url'] ) && empty( $value['id'] );
        }
        $html = (string) $value;
        if ( preg_match( '/<(img|video|iframe|svg|picture|audio|object|embed)\b/i', $html ) ) return false;
        $text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );
        return '' === trim( preg_replace( '/[\s\x{00A0}\x{200B}]+/u', '', $text ) );
    }

    public static function should_render( $should_render, $element ) {
        if ( ! $should_render || ! is_object( $element ) || ! method_exists( $element, 'get_settings' ) ) return $should_render;
        if ( 'yes' !== $element->get_settings( 'pavva_cond_on' ) ) return $should_render;
        if ( self::is_editor() ) return $should_render;

        $key   = 'image' === $element->get_settings( 'pavva_cond_source' ) ? 'pavva_cond_image' : 'pavva_cond_value';
        $empty = self::blank( $element->get_settings_for_display( $key ) );
        $rule  = 'show_empty' === $element->get_settings( 'pavva_cond_rule' ) ? 'show_empty' : 'hide_empty';

        return 'hide_empty' === $rule ? ! $empty : $empty;
    }
}
