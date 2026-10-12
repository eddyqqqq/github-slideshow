<?php
/**
 * Pavva Widgets – Dimensiuni și specificații
 *
 * Câte o coloană pentru fiecare dimensiune / configurație a produsului:
 * imagine în chenar + tabel Etichetă / Valoare.
 * Citește câmpul „Dimensiuni și specificații” al produsului curent.
 *
 * @package Pavva_Widgets
 * @author  Imagient <https://imagient.ro/>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Utils;

class Pavva_Dimensiuni_Widget extends Widget_Base {

	public function get_name() {
		return 'pavva_dimensiuni';
	}

	public function get_title() {
		return 'Pavva – Dimensiuni și specificații';
	}

	public function get_icon() {
		return 'eicon-table';
	}

	public function get_categories() {
		return array( 'pavva' );
	}

	public function get_keywords() {
		return array( 'dimensiuni', 'specificatii', 'specificații', 'tabel', 'produs', 'pavva' );
	}

	public function get_style_depends() {
		return array( 'pavva-dimensiuni', 'pavva-sectiuni-goale' );
	}

	public function get_script_depends() {
		return array( 'pavva-sectiuni-goale' );
	}

	/* Conținutul depinde de produsul paginii: Elementor nu are voie să-l țină în cache. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/* Fără dimensiuni: widgetul primește o clasă care ascunde containerul (vezi sectiuni-goale.css). */
	protected function get_html_wrapper_class(): string {
		$class = parent::get_html_wrapper_class();
		if ( ! $this->is_edit_mode() && ! $this->items_for_page( $this->get_settings_for_display() ) ) {
			$class .= ' pavva-w-is-empty';
		}
		return $class;
	}

	/** @var array|null Dimensiunile produsului paginii (calculate o singură dată). */
	private $items_cache = null;

	private function items_for_page( $s ) {
		if ( null !== $this->items_cache ) {
			return $this->items_cache;
		}
		$this->items_cache = array();
		$product_id        = (int) Pavva_W_Data::resolve_product_id();
		if ( ! $product_id ) {
			return $this->items_cache;
		}
		$show_image = isset( $s['show_image'] ) && 'yes' === $s['show_image'];
		$show_specs = ! isset( $s['show_specs'] ) || 'yes' === $s['show_specs'];
		foreach ( Pavva_W_Data::visible_items( Pavva_W_Data::items( $product_id, Pavva_W_Data::META_DIM ) ) as $item ) {
			$specs = $show_specs ? Pavva_W_Data::dimension_item_specs( $item, $product_id ) : array();
			$image = ! empty( $item['imagine_id'] ) ? absint( $item['imagine_id'] ) : 0;
			if ( ! $specs && ! ( $show_image && $image ) ) {
				continue;
			}
			$this->items_cache[] = array(
				'image' => $image,
				'specs' => $specs,
			);
		}
		return $this->items_cache;
	}

	private function sel( $inner = '' ) {
		return '{{WRAPPER}} .pvd' . ( $inner ? ' ' . $inner : '' );
	}

	protected function register_controls() {
		$this->controls_content();
		$this->controls_style_heading();
		$this->controls_style_grid();
		$this->controls_style_card();
		$this->controls_style_image();
		$this->controls_style_table();
	}

	/* ================================================================
	 * CONTENT
	 * ================================================================ */
	private function controls_content() {

		$this->start_controls_section(
			'section_heading',
			array(
				'label' => 'Titlu secțiune',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_heading',
			array(
				'label'        => 'Afișează titlul',
				'description'  => 'Poți pune titlul și ca widget Heading separat, în același container. Dacă produsul nu are dimensiuni, containerul se ascunde cu tot cu titlu.',
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
				'label'       => 'Text',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Dimensiuni și specificații',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_heading' => 'yes' ),
			)
		);

		$this->add_control(
			'heading_tag',
			array(
				'label'     => 'Tag HTML',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h2',
				'options'   => $this->tag_options(),
				'condition' => array( 'show_heading' => 'yes' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_items',
			array(
				'label' => 'Dimensiuni',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'items_info',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'Datele se iau din produs: <strong>Dimensiuni și specificații</strong>. Un rând cu etichetă dar fără valoare (ex.: „Kombi 4 cm”) apare ca titlu al coloanei.',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
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
				),
				'selectors'      => array(
					$this->sel() => '--pvd-cols: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'show_image',
			array(
				'label'        => 'Afișează imaginea',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'image_size',
			array(
				'label'     => 'Dimensiune imagine',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'large',
				'options'   => $this->image_size_options(),
				'condition' => array( 'show_image' => 'yes' ),
			)
		);

		$this->add_control(
			'show_specs',
			array(
				'label'        => 'Afișează specificațiile',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'value_position',
			array(
				'label'     => 'Valoarea',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'right',
				'options'   => array(
					'right' => 'Pe același rând, în dreapta',
					'below' => 'Sub etichetă',
				),
				'condition' => array( 'show_specs' => 'yes' ),
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
				'label'     => 'Titlu secțiune',
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
					$this->sel( '.pvd-heading' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'heading_typo',
				'selector' => $this->sel( '.pvd-heading' ),
			)
		);

		$this->add_responsive_control(
			'heading_align',
			array(
				'label'     => 'Aliniere',
				'type'      => Controls_Manager::CHOOSE,
				'options'   => $this->align_options(),
				'selectors' => array(
					$this->sel( '.pvd-heading' ) => 'text-align: {{VALUE}};',
				),
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
					$this->sel( '.pvd-heading' ) => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name' => 'heading_tshadow',
				'selector' => $this->sel( '.pvd-heading' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_grid() {
		$this->start_controls_section(
			'section_style_grid',
			array(
				'label' => 'Coloane',
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
					$this->sel() => '--pvd-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'row_gap',
			array(
				'label'       => 'Spațiu între rânduri de coloane',
				'description' => 'Când coloanele trec pe rândul următor (tabletă / mobil).',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 150 ) ),
				'selectors'   => array(
					$this->sel() => '--pvd-row-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_card() {
		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => 'Card (fiecare coloană)',
				'tab' => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label' => 'Padding',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors' => array(
					$this->sel( '.pvd-item' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'card_radius',
			array(
				'label' => 'Colțuri rotunjite (fiecare colț)',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors' => array(
					$this->sel( '.pvd-item' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'card_tabs' );

		$this->start_controls_tab( 'card_tab_normal', array( 'label' => 'Normal' ) );

		$this->add_control(
			'card_bg',
			array(
				'label' => 'Fundal',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvd-item' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name' => 'card_border',
				'label' => 'Bordură',
				'selector' => $this->sel( '.pvd-item' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name' => 'card_shadow',
				'label' => 'Umbră',
				'selector' => $this->sel( '.pvd-item' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'card_tab_hover', array( 'label' => 'Hover' ) );

		$this->add_control(
			'card_bg_hover',
			array(
				'label' => 'Fundal',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvd-item:hover' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'card_border_hover',
			array(
				'label' => 'Culoare bordură',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvd-item:hover' ) => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name' => 'card_shadow_hover',
				'label' => 'Umbră',
				'selector' => $this->sel( '.pvd-item:hover' ),
			)
		);

		$this->add_control(
			'card_lift',
			array(
				'label' => 'Ridicare',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 30, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvd-item:hover' ) => 'transform: translateY(calc({{SIZE}}{{UNIT}} * -1));',
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
				'label'     => 'Imagine',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_image' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'image_ratio',
			array(
				'label'     => 'Proporție chenar',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''     => 'Implicit (pătrat)',
					'1/1'  => '1:1',
					'5/4'  => '5:4',
					'4/3'  => '4:3',
					'3/2'  => '3:2',
					'16/9' => '16:9',
					'3/4'  => '3:4 (portret)',
				),
				'selectors' => array(
					$this->sel() => '--pvd-ratio: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_padding',
			array(
				'label' => 'Spațiu în jurul imaginii (padding)',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors' => array(
					$this->sel( '.pvd-media' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'image_fit',
			array(
				'label'     => 'Încadrare',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'contain',
				'options'   => array(
					'contain' => 'Încape întreagă',
					'cover'   => 'Umple (decupează)',
				),
				'selectors' => array(
					$this->sel( '.pvd-media img' ) => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_bg',
			array(
				'label'     => 'Fundal',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvd-media' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_border_width',
			array(
				'label'       => 'Grosime chenar',
				'description' => 'Implicit 1px. 0 = fără chenar.',
				'type'        => Controls_Manager::DIMENSIONS,
				'size_units'  => array( 'px', 'em', 'rem' ),
				'separator'   => 'before',
				'selectors'   => array(
					$this->sel( '.pvd-media' ) => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'image_border_style',
			array(
				'label'     => 'Tip linie chenar',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''       => 'Implicit (continuă)',
					'solid'  => 'Continuă',
					'dashed' => 'Întreruptă',
					'dotted' => 'Punctată',
					'double' => 'Dublă',
					'none'   => 'Fără chenar',
				),
				'selectors' => array(
					$this->sel( '.pvd-media' ) => 'border-style: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_border_color',
			array(
				'label'     => 'Culoare chenar',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvd-media' ) => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_border_color_hover',
			array(
				'label'     => 'Culoare chenar la hover pe card',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvd-media' ) => 'transition: border-color .4s ease, background-color .4s ease;',
					$this->sel( '.pvd-item:hover .pvd-media' ) => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'image_border',
				'label'    => 'Chenar (setări Elementor, avansat)',
				'selector' => $this->sel( '.pvd-media' ),
			)
		);

		$this->add_responsive_control(
			'image_radius',
			array(
				'label' => 'Colțuri rotunjite (fiecare colț)',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors' => array(
					$this->sel( '.pvd-media' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_spacing',
			array(
				'label'      => 'Spațiu sub imagine',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'separator'  => 'before',
				'selectors'  => array(
					$this->sel( '.pvd-media' ) => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name' => 'image_shadow',
				'label' => 'Umbră',
				'selector' => $this->sel( '.pvd-media' ),
			)
		);

		$this->add_control(
			'image_zoom',
			array(
				'label' => 'Zoom imagine la hover pe card',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 1, 'max' => 1.3, 'step' => 0.01 ) ),
				'selectors' => array(
					$this->sel( '.pvd-item:hover .pvd-media img' ) => 'transform: scale({{SIZE}});',
				),
				'separator' => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_table() {
		$this->start_controls_section(
			'section_style_table',
			array(
				'label' => 'Tabel',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'row_padding',
			array(
				'label'      => 'Înălțime rând (padding sus / jos)',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					$this->sel( '.pvd-row' ) => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'row_padding_x',
			array(
				'label' => 'Padding stânga / dreapta rând',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 40, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvd-row' ) => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'row_gap_lv',
			array(
				'label' => 'Spațiu etichetă – valoare',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 80, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvd-row' ) => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'label_width',
			array(
				'label' => 'Lățime coloană etichete',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( '%', 'px' ),
				'range' => array( 'px' => array( 'min' => 20, 'max' => 80, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvd-label' ) => 'flex: 0 0 {{SIZE}}{{UNIT}}; max-width: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'value_position' => 'right' ),
			)
		);

		$this->add_responsive_control(
			'value_align',
			array(
				'label' => 'Aliniere valoare',
				'type' => Controls_Manager::CHOOSE,
				'options' => $this->align_options(),
				'selectors' => array(
					$this->sel( '.pvd-row:not(.is-title) .pvd-value' ) => 'text-align: {{VALUE}}; margin-left: auto;',
				),
				'condition' => array( 'value_position' => 'right' ),
			)
		);

		$this->add_control(
			'row_bg',
			array(
				'label' => 'Fundal rând',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvd-row' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'row_bg_alt',
			array(
				'label' => 'Fundal rânduri alternative (zebra)',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvd-row:nth-child(even)' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'row_bg_hover',
			array(
				'label' => 'Fundal rând la hover',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvd-row:hover' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'line_color',
			array(
				'label'     => 'Culoare linii',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--pvd-line: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'line_width',
			array(
				'label'      => 'Grosime linii',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 5 ) ),
				'selectors'  => array(
					$this->sel() => '--pvd-line-w: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'line_style',
			array(
				'label' => 'Stil linii',
				'type' => Controls_Manager::SELECT,
				'options' => array( '' => 'Continuă', 'dashed' => 'Întreruptă', 'dotted' => 'Punctată' ),
				'selectors' => array(
					$this->sel() => '--pvd-line-s: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'line_top',
			array(
				'label' => 'Linie și deasupra primului rând',
				'type' => Controls_Manager::SWITCHER,
				'label_on' => 'Da',
				'label_off' => 'Nu',
				'return_value' => 'yes',
				'selectors' => array(
					$this->sel( '.pvd-row:first-child' ) => 'border-top: var(--pvd-line-w) var(--pvd-line-s, solid) var(--pvd-line);',
				),
			)
		);

		$this->add_control(
			'title_row_heading',
			array(
				'label'     => 'Rând titlu (fără valoare)',
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'title_row_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvd-row.is-title' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'title_row_bg',
			array(
				'label' => 'Fundal',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvd-row.is-title' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'title_row_padding',
			array(
				'label' => 'Padding',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors' => array(
					$this->sel( '.pvd-row.is-title' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_row_typo',
				'selector' => $this->sel( '.pvd-row.is-title' ),
			)
		);

		$this->add_control(
			'label_heading',
			array(
				'label'     => 'Etichetă',
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'label_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvd-label' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typo',
				'selector' => $this->sel( '.pvd-label' ),
			)
		);

		$this->add_control(
			'value_heading',
			array(
				'label'     => 'Valoare',
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'value_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvd-value' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'value_typo',
				'selector' => $this->sel( '.pvd-value' ),
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * Ajutătoare
	 * ================================================================ */
	private function tag_options() {
		return array(
			'h1'  => 'H1',
			'h2'  => 'H2',
			'h3'  => 'H3',
			'h4'  => 'H4',
			'h5'  => 'H5',
			'h6'  => 'H6',
			'div' => 'div',
			'p'   => 'p',
		);
	}

	private function align_options() {
		return array(
			'left'   => array( 'title' => 'Stânga', 'icon' => 'eicon-text-align-left' ),
			'center' => array( 'title' => 'Centru', 'icon' => 'eicon-text-align-center' ),
			'right'  => array( 'title' => 'Dreapta', 'icon' => 'eicon-text-align-right' ),
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

	private function is_edit_mode() {
		return class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	private function editor_notice( $text ) {
		if ( ! $this->is_edit_mode() ) {
			return;
		}
		echo '<div class="pavva-editor-notice" style="padding:16px 18px;border:1px dashed #c9c4bb;border-radius:6px;background:#faf8f5;color:#6b6964;font-size:13px;line-height:1.5;">' . wp_kses_post( $text ) . '</div>';
	}

	/* Fără date: în editor un mesaj, pe site un marcaj invizibil (ascunde containerul). */
	private function empty_output( $notice ) {
		if ( $this->is_edit_mode() ) {
			$this->editor_notice( $notice );
			return;
		}
		echo '<span class="pavva-w-empty" hidden></span>';
	}

	/* ================================================================
	 * RENDER
	 * ================================================================ */
	protected function render() {
		$s          = $this->get_settings_for_display();
		$product_id = (int) Pavva_W_Data::resolve_product_id();

		if ( ! $product_id ) {
			$this->empty_output( '<strong>Dimensiuni și specificații:</strong> nu găsesc produsul. În template, alege un produs la <em>rotița Setări → Preview Settings</em>, apoi Apply &amp; Preview.' );
			return;
		}

		$show_image = isset( $s['show_image'] ) && 'yes' === $s['show_image'];
		$items      = $this->items_for_page( $s );

		if ( ! $items ) {
			$this->empty_output( '<strong>Dimensiuni și specificații:</strong> produsul acesta nu are dimensiuni vizibile. Pe site containerul acestei secțiuni se ascunde.' );
			return;
		}

		$heading_tag = Utils::validate_html_tag( ! empty( $s['heading_tag'] ) ? $s['heading_tag'] : 'h2' );
		$heading     = isset( $s['heading_text'] ) ? (string) $s['heading_text'] : '';
		$size        = $this->valid_size( isset( $s['image_size'] ) ? $s['image_size'] : '', 'large' );
		$below       = isset( $s['value_position'] ) && 'below' === $s['value_position'];
		?>
		<div class="pvd<?php echo $below ? ' pvd-value-below' : ''; ?>">
			<?php if ( isset( $s['show_heading'] ) && 'yes' === $s['show_heading'] && '' !== trim( $heading ) ) : ?>
				<<?php echo $heading_tag; // phpcs:ignore ?> class="pvd-heading"><?php echo esc_html( $heading ); ?></<?php echo $heading_tag; // phpcs:ignore ?>>
			<?php endif; ?>

			<div class="pvd-grid">
				<?php foreach ( $items as $item ) : ?>
					<?php
					/* primul rând fără valoare = titlul coloanei (folosit și ca text alternativ al imaginii) */
					$alt = '';
					foreach ( $item['specs'] as $spec ) {
						if ( '' === trim( $spec['valoare'] ) && '' !== trim( $spec['eticheta'] ) ) {
							$alt = $spec['eticheta'];
							break;
						}
					}
					?>
					<div class="pvd-item">
						<?php if ( $show_image && $item['image'] ) : ?>
							<div class="pvd-media">
								<?php
								echo wp_get_attachment_image( // phpcs:ignore -- HTML generat de WordPress
									$item['image'],
									$size,
									false,
									array(
										'alt'      => $alt,
										'loading'  => 'lazy',
										'decoding' => 'async',
										'sizes'    => '(max-width: 767px) 100vw, (max-width: 1024px) 50vw, 400px',
									)
								);
								?>
							</div>
						<?php endif; ?>

						<?php if ( $item['specs'] ) : ?>
							<ul class="pvd-specs">
								<?php
								foreach ( $item['specs'] as $spec ) :
									$label    = trim( (string) $spec['eticheta'] );
									$value    = trim( (string) $spec['valoare'] );
									$is_title = ( '' === $label ) !== ( '' === $value );
									?>
									<li class="pvd-row<?php echo $is_title ? ' is-title' : ''; ?>">
										<?php if ( '' !== $label ) : ?>
											<span class="pvd-label"><?php echo esc_html( $label ); ?></span>
										<?php endif; ?>
										<?php if ( '' !== $value ) : ?>
											<span class="pvd-value"><?php echo esc_html( $value ); ?></span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
