<?php
/**
 * Pavva Widgets – Acordeon produs
 *
 * Secțiuni care se deschid la click: Variante de amenajare (galerie),
 * Fișe tehnice și Declarații de performanță (PDF-uri), descrieri sau text liber.
 * Citește câmpurile produsului curent.
 *
 * @package Pavva_Widgets
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Utils;

class Pavva_Acordeon_Widget extends Widget_Base {

	public function get_name() {
		return 'pavva_acordeon_produs';
	}

	public function get_title() {
		return 'Pavva – Acordeon produs';
	}

	public function get_icon() {
		return 'eicon-accordion';
	}

	public function get_categories() {
		return array( 'pavva' );
	}

	public function get_keywords() {
		return array( 'acordeon', 'accordion', 'fise', 'fișe', 'declaratii', 'amenajare', 'pdf', 'produs', 'pavva' );
	}

	public function get_style_depends() {
		return array( 'pavva-acordeon' );
	}

	public function get_script_depends() {
		return array( 'pavva-acordeon' );
	}

	private function sel( $inner = '' ) {
		return '{{WRAPPER}} .pva' . ( $inner ? ' ' . $inner : '' );
	}

	private function sources() {
		return array(
			'amenajare'        => 'Variante de amenajare (galerie)',
			'fise'             => 'Fișe tehnice (PDF)',
			'declaratii'       => 'Declarații de performanță (PDF)',
			'descriere'        => 'Descriere produs',
			'descriere_scurta' => 'Descriere scurtă',
			'text'             => 'Text scris aici',
		);
	}

	protected function register_controls() {
		$this->controls_content();
		$this->controls_style_items();
		$this->controls_style_title();
		$this->controls_style_icon();
		$this->controls_style_content();
		$this->controls_style_docs();
		$this->controls_style_gallery();
	}

	/* ================================================================
	 * CONTENT
	 * ================================================================ */
	private function controls_content() {

		$this->start_controls_section(
			'section_items',
			array(
				'label' => 'Secțiuni',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'item_title',
			array(
				'label'       => 'Titlu',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Secțiune',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'item_source',
			array(
				'label'   => 'Conținut',
				'type'    => Controls_Manager::SELECT,
				'default' => 'fise',
				'options' => $this->sources(),
			)
		);

		$repeater->add_control(
			'item_text',
			array(
				'label'     => 'Text',
				'type'      => Controls_Manager::WYSIWYG,
				'default'   => '',
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'item_source' => 'text' ),
			)
		);

		$repeater->add_control(
			'item_open',
			array(
				'label'        => 'Deschisă la încărcare',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => 'Secțiuni',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ item_title }}}',
				'default'     => array(
					array(
						'item_title'  => 'Variante de amenajare',
						'item_source' => 'amenajare',
					),
					array(
						'item_title'  => 'Fișe tehnice',
						'item_source' => 'fise',
					),
					array(
						'item_title'  => 'Declarații performanță',
						'item_source' => 'declaratii',
					),
				),
			)
		);

		$this->add_control(
			'hide_empty',
			array(
				'label'        => 'Ascunde secțiunile goale',
				'description'  => 'Ex.: dacă produsul nu are declarații, secțiunea nu apare pe site. În editor rămâne vizibilă, cu un mesaj.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'single_open',
			array(
				'label'        => 'Doar o secțiune deschisă odată',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => 'Tag HTML titlu',
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
				),
			)
		);

		$this->end_controls_section();

		/* ---------- Iconiță ---------- */
		$this->start_controls_section(
			'section_icon',
			array(
				'label' => 'Iconiță',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'icon_type',
			array(
				'label'   => 'Tip',
				'type'    => Controls_Manager::SELECT,
				'default' => 'plus',
				'options' => array(
					'plus'   => '+ / − (implicit)',
					'custom' => 'Iconițe din bibliotecă',
					'none'   => 'Fără iconiță',
				),
			)
		);

		$this->add_control(
			'icon_closed',
			array(
				'label'     => 'Iconiță închis',
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'fas fa-plus',
					'library' => 'fa-solid',
				),
				'condition' => array( 'icon_type' => 'custom' ),
			)
		);

		$this->add_control(
			'icon_opened',
			array(
				'label'     => 'Iconiță deschis',
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'fas fa-minus',
					'library' => 'fa-solid',
				),
				'condition' => array( 'icon_type' => 'custom' ),
			)
		);

		$this->add_control(
			'icon_position',
			array(
				'label'     => 'Poziție',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'inline',
				'options'   => array(
					'inline' => 'Lângă titlu',
					'end'    => 'La capătul rândului (dreapta)',
					'start'  => 'Înainte de titlu',
				),
				'condition' => array( 'icon_type!' => 'none' ),
			)
		);

		$this->end_controls_section();

		/* ---------- Documente ---------- */
		$this->start_controls_section(
			'section_docs',
			array(
				'label' => 'Documente PDF',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'docs_columns',
			array(
				'label'          => 'Coloane',
				'type'           => Controls_Manager::SELECT,
				'default'        => '2',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
				),
				'selectors'      => array(
					$this->sel() => '--pva-doc-cols: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'docs_meta',
			array(
				'label'        => 'Tip și mărime fișier',
				'description'  => 'Ex.: „PDF · 1,2 MB” sub numele documentului.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'docs_action',
			array(
				'label'       => 'Text acțiune',
				'description' => 'Apare în dreapta fiecărui document. Gol = doar săgeata.',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Descarcă',
			)
		);

		$this->add_control(
			'docs_new_tab',
			array(
				'label'        => 'Deschide în tab nou',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		/* ---------- Galerie ---------- */
		$this->start_controls_section(
			'section_gallery',
			array(
				'label' => 'Variante de amenajare',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'gallery_columns',
			array(
				'label'          => 'Coloane',
				'type'           => Controls_Manager::SELECT,
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
				),
				'selectors'      => array(
					$this->sel() => '--pva-gal-cols: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'gallery_size',
			array(
				'label'   => 'Dimensiune imagine',
				'type'    => Controls_Manager::SELECT,
				'default' => 'large',
				'options' => $this->image_size_options(),
			)
		);

		$this->add_control(
			'gallery_titles',
			array(
				'label'        => 'Titlu sub imagine',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'gallery_lightbox',
			array(
				'label'        => 'Mărire la click (lightbox)',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * STYLE
	 * ================================================================ */
	private function controls_style_items() {
		$this->start_controls_section(
			'section_style_items',
			array(
				'label' => 'Secțiuni',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'items_gap',
			array(
				'label'      => 'Spațiu între secțiuni',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'selectors'  => array(
					$this->sel() => '--pva-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'items_line',
			array(
				'label'       => 'Linie între secțiuni',
				'description' => 'Gol = fără linie (ca în design).',
				'type'        => Controls_Manager::COLOR,
				'selectors'   => array(
					$this->sel( '.pva-item' ) => 'border-bottom: 1px solid {{VALUE}}; padding-bottom: var(--pva-gap);',
				),
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'     => 'Durata deschiderii (ms)',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 2000,
				'step'      => 50,
				'selectors' => array(
					$this->sel() => '--pva-speed: {{VALUE}}ms;',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_title() {
		$this->start_controls_section(
			'section_style_title',
			array(
				'label' => 'Titlu',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typo',
				'selector' => $this->sel( '.pva-title-text' ),
			)
		);

		$this->start_controls_tabs( 'title_tabs' );

		$this->start_controls_tab( 'title_tab_normal', array( 'label' => 'Normal' ) );
		$this->add_control(
			'title_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pva-title' ) => 'color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'title_tab_hover', array( 'label' => 'Hover' ) );
		$this->add_control(
			'title_color_hover',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pva-title:hover' ) => 'color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'title_tab_open', array( 'label' => 'Deschis' ) );
		$this->add_control(
			'title_color_open',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pva-item[open]:not(.is-closing) > .pva-title' ) => 'color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'title_padding',
			array(
				'label'      => 'Padding',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'separator'  => 'before',
				'selectors'  => array(
					$this->sel( '.pva-title' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_icon() {
		$this->start_controls_section(
			'section_style_icon',
			array(
				'label'     => 'Iconiță',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'icon_type!' => 'none' ),
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--pva-icon-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'icon_color_open',
			array(
				'label'     => 'Culoare (deschis)',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pva-item[open]:not(.is-closing)' ) => '--pva-icon-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'icon_size',
			array(
				'label'      => 'Mărime',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 6, 'max' => 50 ) ),
				'selectors'  => array(
					$this->sel() => '--pva-icon: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'icon_stroke',
			array(
				'label'      => 'Grosime linie (+ / −)',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0.5, 'max' => 5, 'step' => 0.5 ) ),
				'condition'  => array( 'icon_type' => 'plus' ),
				'selectors'  => array(
					$this->sel() => '--pva-icon-w: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'icon_gap',
			array(
				'label'      => 'Spațiu față de titlu',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors'  => array(
					$this->sel() => '--pva-icon-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_content() {
		$this->start_controls_section(
			'section_style_content',
			array(
				'label' => 'Conținut deschis',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'content_spacing',
			array(
				'label'      => 'Spațiu sub titlu',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'selectors'  => array(
					$this->sel() => '--pva-content-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'     => 'Culoare text',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pva-text' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'text_typo',
				'label'    => 'Tipografie text',
				'selector' => $this->sel( '.pva-text' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_docs() {
		$this->start_controls_section(
			'section_style_docs',
			array(
				'label' => 'Documente PDF',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'docs_gap',
			array(
				'label'      => 'Spațiu între coloane',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'selectors'  => array(
					$this->sel() => '--pva-doc-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'doc_padding',
			array(
				'label'      => 'Înălțime rând (padding sus / jos)',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					$this->sel( '.pva-doc' ) => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'doc_line',
			array(
				'label'     => 'Culoare linii',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--pva-line: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'doc_icon_color',
			array(
				'label'     => 'Culoare iconiță PDF',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pva-doc-icon' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'doc_name_typo',
				'label'    => 'Tipografie nume',
				'selector' => $this->sel( '.pva-doc-name' ),
			)
		);

		$this->start_controls_tabs( 'doc_tabs' );

		$this->start_controls_tab( 'doc_tab_normal', array( 'label' => 'Normal' ) );
		$this->add_control(
			'doc_color',
			array(
				'label'     => 'Culoare nume',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pva-doc' ) => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'doc_action_color',
			array(
				'label'     => 'Culoare „Descarcă”',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pva-doc-action' ) => 'color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'doc_tab_hover', array( 'label' => 'Hover' ) );
		$this->add_control(
			'doc_color_hover',
			array(
				'label'     => 'Culoare nume',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pva-doc:hover' ) => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'doc_action_color_hover',
			array(
				'label'     => 'Culoare „Descarcă”',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pva-doc:hover .pva-doc-action' ) => 'color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_control(
			'doc_meta_color',
			array(
				'label'     => 'Culoare „PDF · mărime”',
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'condition' => array( 'docs_meta' => 'yes' ),
				'selectors' => array(
					$this->sel( '.pva-doc-meta' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'doc_meta_typo',
				'label'     => 'Tipografie „PDF · mărime”',
				'selector'  => $this->sel( '.pva-doc-meta' ),
				'condition' => array( 'docs_meta' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'doc_action_typo',
				'label'    => 'Tipografie „Descarcă”',
				'selector' => $this->sel( '.pva-doc-action' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_gallery() {
		$this->start_controls_section(
			'section_style_gallery',
			array(
				'label' => 'Variante de amenajare',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'gallery_gap',
			array(
				'label'      => 'Spațiu între imagini',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'selectors'  => array(
					$this->sel() => '--pva-gal-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'gallery_ratio',
			array(
				'label'     => 'Proporție imagine',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''     => 'Implicit (4:3)',
					'1/1'  => '1:1',
					'4/3'  => '4:3',
					'3/2'  => '3:2',
					'16/9' => '16:9',
					'3/4'  => '3:4 (portret)',
				),
				'selectors' => array(
					$this->sel() => '--pva-gal-ratio: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'gallery_radius',
			array(
				'label'      => 'Colțuri rotunjite',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
				'selectors'  => array(
					$this->sel( '.pva-gal-media' ) => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'gallery_caption_color',
			array(
				'label'     => 'Culoare titlu',
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'condition' => array( 'gallery_titles' => 'yes' ),
				'selectors' => array(
					$this->sel( '.pva-gal-caption' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'gallery_caption_typo',
				'selector'  => $this->sel( '.pva-gal-caption' ),
				'condition' => array( 'gallery_titles' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * Ajutătoare
	 * ================================================================ */
	private function image_size_options() {
		$out = array();
		foreach ( get_intermediate_image_sizes() as $size ) {
			$out[ $size ] = ucfirst( str_replace( array( '_', '-' ), ' ', $size ) );
		}
		$out['full'] = 'Original (full)';
		return $out;
	}

	private function valid_size( $size, $fallback ) {
		$size = (string) $size;
		return ( 'full' === $size || in_array( $size, get_intermediate_image_sizes(), true ) ) ? $size : $fallback;
	}

	private function is_edit_mode() {
		return class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	private function editor_notice( $text ) {
		if ( ! $this->is_edit_mode() ) {
			return;
		}
		echo '<div class="pavva-editor-notice" style="padding:16px 18px;border:1px dashed #c9c4bb;border-radius:6px;background:#faf8f5;color:#6b6964;font-size:13px;line-height:1.5;">' . wp_kses_post( $text ) . '</div>';
	}

	private function doc_icon_svg() {
		return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M14 3v5h5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M8.5 13.5h7M8.5 16.5h5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>';
	}

	private function download_svg() {
		return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 4v11m-5-5 5 5 5-5M5 20h14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}

	/* Documentele vizibile (PDF) ale produsului. */
	private function get_docs( $product_id, $meta_key ) {
		$out = array();
		foreach ( Pavva_W_Data::visible_items( Pavva_W_Data::items( $product_id, $meta_key ) ) as $doc ) {
			$id  = ! empty( $doc['fisier_id'] ) ? absint( $doc['fisier_id'] ) : 0;
			$url = $id ? wp_get_attachment_url( $id ) : '';
			if ( ! $url ) {
				continue;
			}
			$file = get_attached_file( $id );
			$name = isset( $doc['denumire'] ) ? trim( (string) $doc['denumire'] ) : '';
			if ( '' === $name ) {
				$name = $file ? basename( $file ) : get_the_title( $id );
			}
			$ext   = strtoupper( (string) pathinfo( (string) ( $file ? $file : wp_parse_url( $url, PHP_URL_PATH ) ), PATHINFO_EXTENSION ) );
			$bytes = ( $file && file_exists( $file ) ) ? (int) filesize( $file ) : 0;
			$out[] = array(
				'url'  => $url,
				'name' => $name,
				'meta' => trim( $ext . ( $bytes ? ' · ' . size_format( $bytes, $bytes >= MB_IN_BYTES ? 1 : 0 ) : '' ), ' ·' ),
			);
		}
		return $out;
	}

	/* Variantele de amenajare vizibile ale produsului. */
	private function get_gallery( $product_id ) {
		$out = array();
		foreach ( Pavva_W_Data::visible_items( Pavva_W_Data::items( $product_id, Pavva_W_Data::META_AMEN ) ) as $item ) {
			$id = ! empty( $item['imagine_id'] ) ? absint( $item['imagine_id'] ) : 0;
			if ( ! $id || ! wp_get_attachment_image_url( $id, 'thumbnail' ) ) {
				continue;
			}
			$out[] = array(
				'id'    => $id,
				'title' => isset( $item['titlu'] ) ? (string) $item['titlu'] : '',
			);
		}
		return $out;
	}

	/* HTML-ul conținutului unei secțiuni ('' = gol). */
	private function section_html( $item, $product_id, $s, $index ) {
		$source = isset( $item['item_source'] ) ? $item['item_source'] : '';

		if ( 'text' === $source ) {
			$text = isset( $item['item_text'] ) ? (string) $item['item_text'] : '';
			return '' === trim( wp_strip_all_tags( $text ) ) && false === stripos( $text, '<img' ) ? '' : '<div class="pva-text">' . $this->parse_text_editor( $text ) . '</div>';
		}

		if ( ! $product_id ) {
			return '';
		}

		if ( 'descriere' === $source ) {
			$html = trim( do_shortcode( (string) get_post_field( 'post_content', $product_id ) ) );
			return '' === $html ? '' : '<div class="pva-text">' . wp_kses_post( wpautop( $html ) ) . '</div>';
		}

		if ( 'descriere_scurta' === $source ) {
			$html = trim( (string) get_post_field( 'post_excerpt', $product_id ) );
			return '' === $html ? '' : '<div class="pva-text">' . wp_kses_post( wpautop( $html ) ) . '</div>';
		}

		if ( 'fise' === $source || 'declaratii' === $source ) {
			$docs = $this->get_docs( $product_id, 'fise' === $source ? Pavva_W_Data::META_FISE : Pavva_W_Data::META_DECL );
			if ( ! $docs ) {
				return '';
			}
			$new_tab = isset( $s['docs_new_tab'] ) && 'yes' === $s['docs_new_tab'];
			$meta_on = isset( $s['docs_meta'] ) && 'yes' === $s['docs_meta'];
			$action  = isset( $s['docs_action'] ) ? trim( (string) $s['docs_action'] ) : '';

			$html = '<ul class="pva-docs">';
			foreach ( $docs as $doc ) {
				$html .= '<li class="pva-docs-item"><a class="pva-doc" href="' . esc_url( $doc['url'] ) . '"' . ( $new_tab ? ' target="_blank" rel="noopener"' : '' ) . '>';
				$html .= '<span class="pva-doc-icon">' . $this->doc_icon_svg() . '</span>';
				$html .= '<span class="pva-doc-text"><span class="pva-doc-name">' . esc_html( $doc['name'] ) . '</span>';
				if ( $meta_on && '' !== $doc['meta'] ) {
					$html .= '<span class="pva-doc-meta">' . esc_html( $doc['meta'] ) . '</span>';
				}
				$html .= '</span>';
				$html .= '<span class="pva-doc-action">' . ( '' !== $action ? '<span class="pva-doc-action-text">' . esc_html( $action ) . '</span>' : '' ) . $this->download_svg() . '</span>';
				$html .= '</a></li>';
			}
			$html .= '</ul>';
			return $html;
		}

		if ( 'amenajare' === $source ) {
			$images = $this->get_gallery( $product_id );
			if ( ! $images ) {
				return '';
			}
			$size     = $this->valid_size( isset( $s['gallery_size'] ) ? $s['gallery_size'] : '', 'large' );
			$titles   = isset( $s['gallery_titles'] ) && 'yes' === $s['gallery_titles'];
			$lightbox = isset( $s['gallery_lightbox'] ) && 'yes' === $s['gallery_lightbox'];
			$group    = 'pva-' . $this->get_id() . '-' . $index;

			$html = '<div class="pva-gallery">';
			foreach ( $images as $img ) {
				$image = wp_get_attachment_image(
					$img['id'],
					$size,
					false,
					array(
						'alt'     => $img['title'],
						'loading' => 'lazy',
					)
				);
				$html .= '<figure class="pva-gal-item">';
				if ( $lightbox ) {
					$html .= '<a class="pva-gal-media" href="' . esc_url( (string) wp_get_attachment_image_url( $img['id'], 'full' ) ) . '" data-elementor-open-lightbox="yes" data-elementor-lightbox-slideshow="' . esc_attr( $group ) . '"' . ( '' !== $img['title'] ? ' data-elementor-lightbox-title="' . esc_attr( $img['title'] ) . '"' : '' ) . '>' . $image . '</a>';
				} else {
					$html .= '<span class="pva-gal-media">' . $image . '</span>';
				}
				if ( $titles && '' !== $img['title'] ) {
					$html .= '<figcaption class="pva-gal-caption">' . esc_html( $img['title'] ) . '</figcaption>';
				}
				$html .= '</figure>';
			}
			$html .= '</div>';
			return $html;
		}

		return '';
	}

	private function icon_html( $s ) {
		$type = isset( $s['icon_type'] ) ? $s['icon_type'] : 'plus';
		if ( 'none' === $type ) {
			return '';
		}
		if ( 'custom' === $type ) {
			$closed = '';
			$opened = '';
			if ( ! empty( $s['icon_closed']['value'] ) ) {
				ob_start();
				Icons_Manager::render_icon( $s['icon_closed'], array( 'aria-hidden' => 'true' ) );
				$closed = ob_get_clean();
			}
			if ( ! empty( $s['icon_opened']['value'] ) ) {
				ob_start();
				Icons_Manager::render_icon( $s['icon_opened'], array( 'aria-hidden' => 'true' ) );
				$opened = ob_get_clean();
			}
			if ( '' === $opened ) {
				$opened = $closed;
			}
			return '<span class="pva-icon pva-icon-custom" aria-hidden="true"><span class="pva-icon-closed">' . $closed . '</span><span class="pva-icon-opened">' . $opened . '</span></span>';
		}
		return '<span class="pva-icon pva-icon-plus" aria-hidden="true"></span>';
	}

	/* ================================================================
	 * RENDER
	 * ================================================================ */
	protected function render() {
		$s          = $this->get_settings_for_display();
		$items      = isset( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
		$product_id = (int) Pavva_W_Data::resolve_product_id();
		$edit       = $this->is_edit_mode();
		$hide_empty = isset( $s['hide_empty'] ) && 'yes' === $s['hide_empty'];
		$title_tag  = Utils::validate_html_tag( ! empty( $s['title_tag'] ) ? $s['title_tag'] : 'h3' );
		$icon       = $this->icon_html( $s );
		$icon_pos   = isset( $s['icon_position'] ) && in_array( $s['icon_position'], array( 'inline', 'end', 'start' ), true ) ? $s['icon_position'] : 'inline';
		$sources    = $this->sources();

		$rows = array();
		foreach ( $items as $i => $item ) {
			$title = isset( $item['item_title'] ) ? trim( (string) $item['item_title'] ) : '';
			if ( '' === $title ) {
				continue;
			}
			$html = $this->section_html( $item, $product_id, $s, $i );
			if ( '' === $html ) {
				if ( $edit ) {
					$src  = isset( $item['item_source'] ) && isset( $sources[ $item['item_source'] ] ) ? $sources[ $item['item_source'] ] : '';
					$html = '<div class="pavva-editor-notice" style="padding:12px 14px;border:1px dashed #c9c4bb;border-radius:6px;background:#faf8f5;color:#6b6964;font-size:13px;line-height:1.5;">'
						. ( $product_id ? 'Produsul nu are conținut pentru „' . esc_html( $src ) . '”.' : 'Nu găsesc produsul. Alege un produs la <em>rotița Setări → Preview Settings</em>.' )
						. ( $hide_empty ? ' Pe site secțiunea nu apare.' : '' )
						. '</div>';
				} elseif ( $hide_empty ) {
					continue;
				}
			}
			$rows[] = array(
				'item'  => $item,
				'title' => $title,
				'html'  => $html,
			);
		}

		if ( ! $rows ) {
			return;
		}

		$options = array( 'single' => isset( $s['single_open'] ) && 'yes' === $s['single_open'] );
		?>
		<div class="pva pva-icon-<?php echo esc_attr( $icon_pos ); ?>" data-pva="<?php echo esc_attr( wp_json_encode( $options ) ); ?>">
			<?php
			foreach ( $rows as $row ) :
				$item = $row['item'];
				$open = isset( $item['item_open'] ) && 'yes' === $item['item_open'];
				?>
				<details class="pva-item elementor-repeater-item-<?php echo esc_attr( $item['_id'] ); ?>"<?php echo $open ? ' open' : ''; ?>>
					<summary class="pva-title">
						<<?php echo $title_tag; // phpcs:ignore ?> class="pva-title-text"><?php echo esc_html( $row['title'] ); ?></<?php echo $title_tag; // phpcs:ignore ?>>
						<?php echo $icon; // phpcs:ignore -- generat mai sus ?>
					</summary>
					<div class="pva-panel">
						<div class="pva-panel-inner">
							<?php echo $row['html']; // phpcs:ignore -- generat și escapat în section_html() ?>
						</div>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
