<?php
/**
 * Pavva Widgets – Grilă produse
 *
 * Produsele unei categorii (sau alese manual) în carduri: imagine, nume,
 * „Descriere sinteză”. Cardul arată ca în Caruselul de produse.
 *
 * @package Pavva_Widgets
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Utils;

class Pavva_Grila_Produse_Widget extends Widget_Base {

	public function get_name() {
		return 'pavva_grila_produse';
	}

	public function get_title() {
		return 'Pavva – Grilă produse';
	}

	public function get_icon() {
		return 'eicon-products';
	}

	public function get_categories() {
		return array( 'pavva' );
	}

	public function get_keywords() {
		return array( 'grila', 'grilă', 'produse', 'categorie', 'carduri', 'pavva' );
	}

	public function get_style_depends() {
		return array( 'pavva-grila-produse' );
	}

	private function sel( $inner = '' ) {
		return '{{WRAPPER}} .pvg' . ( $inner ? ' ' . $inner : '' );
	}

	protected function register_controls() {
		$this->controls_query();
		$this->controls_layout();
		$this->controls_style_heading();
		$this->controls_style_grid();
		$this->controls_style_card();
		$this->controls_style_image();
		$this->controls_style_texts();
		$this->controls_style_arrow();
	}

	/* ================================================================
	 * CONTENT: ce produse
	 * ================================================================ */
	private function controls_query() {
		$this->start_controls_section(
			'section_query',
			array(
				'label' => 'Produse',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'source',
			array(
				'label'   => 'Ce produse',
				'type'    => Controls_Manager::SELECT,
				'default' => 'current',
				'options' => array(
					'current' => 'Categoria paginii (automat)',
					'cats'    => 'Din categoriile alese',
					'manual'  => 'Produse alese manual',
					'all'     => 'Toate produsele',
				),
			)
		);

		$this->add_control(
			'source_info',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'Pe pagina unei categorii arată produsele ei. Pe pagina unui produs arată celelalte produse din aceeași categorie.',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition'       => array( 'source' => 'current' ),
			)
		);

		$this->add_control(
			'cats',
			array(
				'label'       => 'Categorii',
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->category_options(),
				'condition'   => array( 'source' => 'cats' ),
			)
		);

		$this->add_control(
			'products',
			array(
				'label'       => 'Produse',
				'description' => 'Apar în ordinea în care le alegi.',
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->product_options(),
				'condition'   => array( 'source' => 'manual' ),
			)
		);

		$this->add_control(
			'include_children',
			array(
				'label'        => 'Include subcategoriile',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'source' => array( 'current', 'cats' ) ),
			)
		);

		$this->add_control(
			'exclude_current',
			array(
				'label'        => 'Fără produsul paginii',
				'description'  => 'Pe pagina unui produs, nu îl afișa și pe el în grilă.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'       => 'Număr maxim de produse',
				'description' => '0 = toate.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 200,
				'default'     => 0,
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'     => 'Ordonare',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'menu_order',
				'options'   => array(
					'menu_order' => 'Ordinea din WooCommerce (drag & drop)',
					'title'      => 'Alfabetic',
					'date'       => 'Data adăugării',
					'rand'       => 'Aleatoriu',
				),
				'condition' => array( 'source!' => 'manual' ),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'     => 'Sens',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'ASC',
				'options'   => array(
					'ASC'  => 'Crescător',
					'DESC' => 'Descrescător',
				),
				'condition' => array(
					'source!'  => 'manual',
					'orderby!' => 'rand',
				),
			)
		);

		$this->add_control(
			'empty_text',
			array(
				'label'       => 'Text când nu sunt produse',
				'description' => 'Gol = nu apare nimic pe site.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'separator'   => 'before',
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * CONTENT: aspect
	 * ================================================================ */
	private function controls_layout() {
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => 'Aspect',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_heading',
			array(
				'label'        => 'Titlu deasupra grilei',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'heading_text',
			array(
				'label'       => 'Text titlu',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Produse',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_heading' => 'yes' ),
			)
		);

		$this->add_control(
			'heading_tag',
			array(
				'label'     => 'Tag HTML titlu',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h2',
				'options'   => $this->tag_options(),
				'condition' => array( 'show_heading' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'columns',
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
					'6' => '6',
				),
				'separator'      => 'before',
				'selectors'      => array(
					$this->sel() => '--pvg-cols: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_size',
			array(
				'label'   => 'Dimensiune imagine',
				'type'    => Controls_Manager::SELECT,
				'default' => 'large',
				'options' => $this->image_size_options(),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => 'Tag HTML nume produs',
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => $this->tag_options(),
			)
		);

		$this->add_control(
			'show_desc',
			array(
				'label'        => 'Descriere',
				'description'  => 'Folosește „Descriere sinteză” a produsului; dacă e goală, descrierea scurtă.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'desc_words',
			array(
				'label'       => 'Număr maxim de cuvinte',
				'description' => '0 = textul întreg.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 200,
				'default'     => 0,
				'condition'   => array( 'show_desc' => 'yes' ),
			)
		);

		$this->add_control(
			'show_arrow',
			array(
				'label'        => 'Săgeată lângă nume',
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
	private function controls_style_heading() {
		$this->start_controls_section(
			'section_style_heading',
			array(
				'label'     => 'Titlu',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_heading' => 'yes' ),
			)
		);

		$this->add_control(
			'heading_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-heading' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'heading_typo',
				'selector' => $this->sel( '.pvg-heading' ),
			)
		);

		$this->add_responsive_control(
			'heading_spacing',
			array(
				'label'      => 'Spațiu sub titlu',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 150 ) ),
				'selectors'  => array(
					$this->sel( '.pvg-heading' ) => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_grid() {
		$this->start_controls_section(
			'section_style_grid',
			array(
				'label' => 'Grilă',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'col_gap',
			array(
				'label'      => 'Spațiu între coloane',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', 'vw' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'selectors'  => array(
					$this->sel() => '--pvg-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'row_gap',
			array(
				'label'      => 'Spațiu între rânduri',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', 'vw' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 150 ) ),
				'selectors'  => array(
					$this->sel() => '--pvg-row-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_card() {
		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => 'Card',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => 'Padding card',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors'  => array(
					$this->sel( '.pvg-card' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'body_padding',
			array(
				'label'       => 'Padding zonă text',
				'description' => 'Spațiul din jurul numelui și al descrierii, sub imagine.',
				'type'        => Controls_Manager::DIMENSIONS,
				'size_units'  => array( 'px', 'em', 'rem', '%' ),
				'selectors'   => array(
					$this->sel( '.pvg-body' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'card_radius',
			array(
				'label'      => 'Colțuri rotunjite card',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					$this->sel( '.pvg-card' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'card_tabs' );

		$this->start_controls_tab( 'card_tab_normal', array( 'label' => 'Normal' ) );
		$this->add_control(
			'card_bg',
			array(
				'label'     => 'Fundal',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-card' ) => 'background-color: {{VALUE}};',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => $this->sel( '.pvg-card' ),
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => $this->sel( '.pvg-card' ),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'card_tab_hover', array( 'label' => 'Hover' ) );
		$this->add_control(
			'card_bg_hover',
			array(
				'label'     => 'Fundal',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-card:hover' ) => 'background-color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'card_border_color_hover',
			array(
				'label'     => 'Culoare bordură',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-card:hover' ) => 'border-color: {{VALUE}};',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow_hover',
				'selector' => $this->sel( '.pvg-card:hover' ),
			)
		);
		$this->add_control(
			'card_lift',
			array(
				'label'      => 'Ridicare la hover',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'selectors'  => array(
					$this->sel() => '--pvg-lift: {{SIZE}}{{UNIT}};',
				),
			)
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	private function controls_style_image() {
		$this->start_controls_section(
			'section_style_image',
			array(
				'label' => 'Imagine',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'image_ratio',
			array(
				'label'     => 'Proporție',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''     => 'Implicit (4:3)',
					'1/1'  => '1:1',
					'5/4'  => '5:4',
					'4/3'  => '4:3',
					'3/2'  => '3:2',
					'16/9' => '16:9',
					'3/4'  => '3:4 (portret)',
				),
				'selectors' => array(
					$this->sel() => '--pvg-ratio: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_radius',
			array(
				'label'      => 'Colțuri rotunjite',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array(
					$this->sel( '.pvg-media' ) => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'image_bg',
			array(
				'label'     => 'Fundal (fără imagine)',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-media' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_zoom',
			array(
				'label'     => 'Zoom la hover',
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 1, 'max' => 1.3, 'step' => 0.01 ) ),
				'selectors' => array(
					$this->sel() => '--pvg-zoom: {{SIZE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_texts() {
		$this->start_controls_section(
			'section_style_texts',
			array(
				'label' => 'Texte',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'title_heading',
			array(
				'label' => 'Nume produs',
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typo',
				'selector' => $this->sel( '.pvg-title' ),
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-title' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'title_color_hover',
			array(
				'label'     => 'Culoare la hover pe card',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-card:hover .pvg-title' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'desc_heading',
			array(
				'label'     => 'Descriere',
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array( 'show_desc' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'desc_typo',
				'selector'  => $this->sel( '.pvg-desc' ),
				'condition' => array( 'show_desc' => 'yes' ),
			)
		);

		$this->add_control(
			'desc_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'show_desc' => 'yes' ),
				'selectors' => array(
					$this->sel( '.pvg-desc' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'desc_spacing',
			array(
				'label'      => 'Spațiu deasupra descrierii',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'condition'  => array( 'show_desc' => 'yes' ),
				'selectors'  => array(
					$this->sel( '.pvg-desc' ) => 'margin-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_arrow() {
		$this->start_controls_section(
			'section_style_arrow',
			array(
				'label'     => 'Săgeată',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_arrow' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'arrow_size',
			array(
				'label'      => 'Mărime',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 60 ) ),
				'selectors'  => array(
					$this->sel( '.pvg-arrow' ) => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'arrow_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-arrow' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_color_hover',
			array(
				'label'     => 'Culoare la hover pe card',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-card:hover .pvg-arrow' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_shift',
			array(
				'label'      => 'Deplasare la hover',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'selectors'  => array(
					$this->sel() => '--pvg-arrow-shift: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * Ajutătoare
	 * ================================================================ */
	private function tag_options() {
		return array(
			'h1'   => 'H1',
			'h2'   => 'H2',
			'h3'   => 'H3',
			'h4'   => 'H4',
			'h5'   => 'H5',
			'h6'   => 'H6',
			'div'  => 'div',
			'span' => 'span',
			'p'    => 'p',
		);
	}

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

	private function category_options() {
		$out   = array();
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) ) {
			return $out;
		}
		foreach ( $terms as $term ) {
			$out[ (string) $term->term_id ] = $term->name;
		}
		return $out;
	}

	private function product_options() {
		$out = array();
		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 300,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $id ) {
			$out[ (string) $id ] = get_the_title( $id );
		}
		return $out;
	}

	private function is_edit_mode() {
		return class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	private function editor_notice( $text ) {
		if ( ! $this->is_edit_mode() ) {
			return;
		}
		echo '<div class="pavva-editor-notice" style="margin-bottom:16px;padding:16px 18px;border:1px dashed #c9c4bb;border-radius:6px;background:#faf8f5;color:#6b6964;font-size:13px;line-height:1.5;">' . wp_kses_post( $text ) . '</div>';
	}

	private function arrow_svg() {
		return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 12h17M14 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}

	/* Categoriile paginii: categoria afișată sau categoriile produsului afișat. */
	private function current_terms() {
		$tid = (int) Pavva_W_Data::current_term_id();
		if ( $tid ) {
			return array( $tid );
		}
		$pid = (int) Pavva_W_Data::resolve_product_id();
		if ( $pid ) {
			$ids = wp_get_post_terms( $pid, 'product_cat', array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $ids ) && $ids ) {
				return array_map( 'absint', $ids );
			}
		}
		return array();
	}

	/* Lista de ID-uri de produse, după setări. */
	private function query_ids( $s ) {
		$source = isset( $s['source'] ) ? $s['source'] : 'current';
		$limit  = isset( $s['limit'] ) ? absint( $s['limit'] ) : 0;

		$args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit ? $limit : -1,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'tax_query'           => array(), // phpcs:ignore
		);

		/* produsele ascunse din catalog (WooCommerce → Vizibilitate catalog) nu apar */
		$hidden = get_term_by( 'name', 'exclude-from-catalog', 'product_visibility' );
		if ( $hidden instanceof WP_Term ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => array( (int) $hidden->term_taxonomy_id ),
				'operator' => 'NOT IN',
			);
		}

		$preview_all = false;

		if ( 'manual' === $source ) {
			$ids = isset( $s['products'] ) && is_array( $s['products'] ) ? array_filter( array_map( 'absint', $s['products'] ) ) : array();
			if ( ! $ids ) {
				return array( array(), false );
			}
			$args['post__in'] = $ids;
			$args['orderby']  = 'post__in';
		} else {
			$terms = array();
			if ( 'cats' === $source ) {
				$terms = isset( $s['cats'] ) && is_array( $s['cats'] ) ? array_filter( array_map( 'absint', $s['cats'] ) ) : array();
			} elseif ( 'current' === $source ) {
				$terms = $this->current_terms();
				/* în editor, fără categorie de previzualizare: arătăm câteva produse ca exemplu */
				if ( ! $terms && $this->is_edit_mode() ) {
					$preview_all = true;
					if ( ! $limit ) {
						$args['posts_per_page'] = 6;
					}
				}
			}

			if ( 'all' !== $source && ! $preview_all ) {
				if ( ! $terms ) {
					return array( array(), false );
				}
				$args['tax_query'][] = array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => $terms,
					'include_children' => isset( $s['include_children'] ) && 'yes' === $s['include_children'],
				);
			}

			$orderby = isset( $s['orderby'] ) ? $s['orderby'] : 'menu_order';
			$order   = ( isset( $s['order'] ) && 'DESC' === $s['order'] ) ? 'DESC' : 'ASC';
			if ( 'rand' === $orderby ) {
				$args['orderby'] = 'rand';
			} elseif ( 'title' === $orderby ) {
				$args['orderby'] = 'title';
				$args['order']   = $order;
			} elseif ( 'date' === $orderby ) {
				$args['orderby'] = 'date';
				$args['order']   = $order;
			} else {
				$args['orderby'] = array(
					'menu_order' => $order,
					'title'      => 'ASC',
				);
			}
		}

		if ( isset( $s['exclude_current'] ) && 'yes' === $s['exclude_current'] ) {
			/* doar pe pagina unui produs (pe o categorie, „produsul curent” ar fi primul din listă) */
			$current = Pavva_W_Data::current_term_id() ? 0 : (int) Pavva_W_Data::resolve_product_id();
			if ( $current ) {
				$args['post__not_in'] = array( $current );
				if ( isset( $args['post__in'] ) ) {
					$args['post__in'] = array_values( array_diff( $args['post__in'], array( $current ) ) );
					if ( ! $args['post__in'] ) {
						return array( array(), false );
					}
				}
			}
		}

		if ( count( $args['tax_query'] ) > 1 ) {
			$args['tax_query']['relation'] = 'AND';
		}

		$q = new WP_Query( $args );
		return array( array_map( 'absint', $q->posts ), $preview_all );
	}

	private function product_desc( $id, $words ) {
		$text = (string) get_post_meta( $id, Pavva_W_Data::META_DESCRIERE_SINTEZA, true );
		if ( '' === trim( $text ) ) {
			$text = wp_strip_all_tags( (string) get_post_field( 'post_excerpt', $id ) );
		}
		$text = trim( html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) );
		if ( $words > 0 && '' !== $text ) {
			$text = wp_trim_words( $text, $words, '…' );
		}
		return $text;
	}

	/* ================================================================
	 * RENDER
	 * ================================================================ */
	protected function render() {
		$s = $this->get_settings_for_display();

		list( $ids, $preview_all ) = $this->query_ids( $s );

		$heading_tag = Utils::validate_html_tag( ! empty( $s['heading_tag'] ) ? $s['heading_tag'] : 'h2' );
		$title_tag   = Utils::validate_html_tag( ! empty( $s['title_tag'] ) ? $s['title_tag'] : 'h3' );
		$size        = $this->valid_size( isset( $s['image_size'] ) ? $s['image_size'] : '', 'large' );
		$show_desc   = isset( $s['show_desc'] ) && 'yes' === $s['show_desc'];
		$show_arrow  = isset( $s['show_arrow'] ) && 'yes' === $s['show_arrow'];
		$words       = isset( $s['desc_words'] ) ? absint( $s['desc_words'] ) : 0;
		$heading     = isset( $s['heading_text'] ) ? (string) $s['heading_text'] : '';
		$empty_text  = isset( $s['empty_text'] ) ? trim( (string) $s['empty_text'] ) : '';

		if ( ! $ids ) {
			if ( '' !== $empty_text ) {
				echo '<div class="pvg"><p class="pvg-empty">' . esc_html( $empty_text ) . '</p></div>';
			} else {
				$this->editor_notice( '<strong>Grilă produse:</strong> nu am găsit produse pentru setările alese. Pe site widgetul nu apare.' );
			}
			return;
		}

		if ( $preview_all ) {
			$this->editor_notice( '<strong>Grilă produse – previzualizare:</strong> nu știu categoria paginii, așa că arăt câteva produse ca exemplu. Pentru categoria reală, alege una la <em>rotița Setări → Preview Settings</em>. Pe site apar produsele categoriei.' );
		}
		?>
		<div class="pvg">
			<?php if ( isset( $s['show_heading'] ) && 'yes' === $s['show_heading'] && '' !== trim( $heading ) ) : ?>
				<<?php echo $heading_tag; // phpcs:ignore ?> class="pvg-heading"><?php echo esc_html( $heading ); ?></<?php echo $heading_tag; // phpcs:ignore ?>>
			<?php endif; ?>

			<ul class="pvg-grid">
				<?php
				foreach ( $ids as $id ) :
					$name  = get_the_title( $id );
					$thumb = get_post_thumbnail_id( $id );
					$desc  = $show_desc ? $this->product_desc( $id, $words ) : '';
					?>
					<li class="pvg-item">
						<a class="pvg-card" href="<?php echo esc_url( get_permalink( $id ) ); ?>">
							<div class="pvg-media">
								<?php
								if ( $thumb ) {
									echo wp_get_attachment_image( // phpcs:ignore -- HTML generat de WordPress
										$thumb,
										$size,
										false,
										array(
											'alt'     => wp_strip_all_tags( $name ),
											'loading' => 'lazy',
										)
									);
								}
								?>
							</div>
							<div class="pvg-body">
								<div class="pvg-row">
									<<?php echo $title_tag; // phpcs:ignore ?> class="pvg-title"><?php echo esc_html( wp_strip_all_tags( $name ) ); ?></<?php echo $title_tag; // phpcs:ignore ?>>
									<?php if ( $show_arrow ) : ?>
										<span class="pvg-arrow" aria-hidden="true"><?php echo $this->arrow_svg(); // phpcs:ignore ?></span>
									<?php endif; ?>
								</div>
								<?php if ( '' !== $desc ) : ?>
									<p class="pvg-desc"><?php echo esc_html( $desc ); ?></p>
								<?php endif; ?>
							</div>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}
