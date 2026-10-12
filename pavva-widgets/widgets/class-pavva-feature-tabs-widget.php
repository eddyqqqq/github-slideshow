<?php
/**
 * Pavva Widgets – Listă interactivă cu imagine
 *
 * Listă de avantaje (titlu + descriere). La hover / click pe un element se
 * schimbă lin imaginea. Pe mobil: carduri glisante sau acordeon.
 *
 * @package Pavva_Widgets
 * @author  Imagient <https://imagient.ro/>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Image_Size;
use Elementor\Utils;

class Pavva_Feature_Tabs_Widget extends Widget_Base {

	public function get_name() {
		return 'pavva_feature_tabs';
	}

	public function get_title() {
		return 'Pavva – Listă interactivă cu imagine';
	}

	public function get_icon() {
		return 'eicon-tabs';
	}

	public function get_categories() {
		return array( 'pavva' );
	}

	public function get_keywords() {
		return array( 'lista', 'avantaje', 'tabs', 'imagine', 'hover', 'pavva' );
	}

	public function get_style_depends() {
		return array( 'pavva-feature-tabs' );
	}

	public function get_script_depends() {
		return array( 'pavva-feature-tabs' );
	}

	private function sel( $inner = '' ) {
		return '{{WRAPPER}} .pf' . ( $inner ? ' ' . $inner : '' );
	}

	/* Variabilele CSS pentru fiecare așezare (se aplică pe breakpoint-urile Elementor). */
	private function layout_vars() {
		return array(
			'side'      => '--pf-dir: row; --pf-media-order: var(--pf-side-order, 1); --pf-media-flex: 1 1 0%; --pf-list-basis: var(--pf-list-width, 44%); --pf-list-dir: column; --pf-list-ov: visible; --pf-snap: none; --pf-list-pad: 24px 0; --pf-list-justify: space-between; --pf-item-flex: 0 0 auto; --pf-item-bg: transparent; --pf-item-bg-on: transparent; --pf-item-pad: 0px; --pf-item-radius: 0px; --pf-item-border: transparent; --pf-item-border-on: transparent; --pf-item-dir: row; --pf-title-basis: var(--pf-title-w, 170px); --pf-row-gap: var(--pf-row-gap-side, 24px); --pf-inactive-rows: 1fr; --pf-dots: none; --pf-m: 0; --pf-gap-def: 32px;',
			'swipe'     => '--pf-dir: column; --pf-media-order: -1; --pf-media-flex: 0 0 auto; --pf-list-basis: auto; --pf-list-dir: row; --pf-list-ov: auto; --pf-snap: x mandatory; --pf-list-pad: 0 0 2px; --pf-list-justify: flex-start; --pf-item-flex: 0 0 var(--pf-card-w, 82%); --pf-item-bg: var(--pf-card-bg, #f5f1ec); --pf-item-bg-on: var(--pf-card-bg-on, #f5f1ec); --pf-item-pad: var(--pf-card-pad, 22px); --pf-item-radius: var(--pf-card-radius, 10px); --pf-item-border: var(--pf-card-border, transparent); --pf-item-border-on: var(--pf-card-border-on, #1d1127); --pf-item-dir: column; --pf-title-basis: auto; --pf-row-gap: var(--pf-row-gap-stack, 10px); --pf-inactive-rows: 1fr; --pf-dots: flex; --pf-m: 1; --pf-gap-def: 16px;',
			'accordion' => '--pf-dir: column; --pf-media-order: -1; --pf-media-flex: 0 0 auto; --pf-list-basis: auto; --pf-list-dir: column; --pf-list-ov: visible; --pf-snap: none; --pf-list-pad: 0; --pf-list-justify: flex-start; --pf-item-flex: 0 0 auto; --pf-item-bg: transparent; --pf-item-bg-on: transparent; --pf-item-pad: 0px; --pf-item-radius: 0px; --pf-item-border: transparent; --pf-item-border-on: transparent; --pf-item-dir: column; --pf-title-basis: auto; --pf-row-gap: var(--pf-row-gap-stack, 10px); --pf-inactive-rows: 0fr; --pf-dots: none; --pf-m: 2; --pf-gap-def: 24px;',
		);
	}

	protected function register_controls() {

		/* ================= CONTENT – Elemente ================= */
		$this->start_controls_section(
			'section_items',
			array(
				'label' => 'Elemente',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$r = new Repeater();

		$r->add_control(
			'item_title',
			array(
				'label'       => 'Titlu',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Titlu',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$r->add_control(
			'item_desc',
			array(
				'label'   => 'Descriere',
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => 'Descriere scurtă.',
				'dynamic' => array( 'active' => true ),
			)
		);

		$r->add_control(
			'item_image',
			array(
				'label'       => 'Imagine',
				'description' => 'Fără imagine se afișează culoarea de fundal de mai jos.',
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
				'dynamic'     => array( 'active' => true ),
				'separator'   => 'before',
			)
		);

		$r->add_control(
			'item_image_mobile',
			array(
				'label'       => 'Imagine pentru mobil (opțional)',
				'description' => 'Gol = se folosește imaginea principală.',
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
			)
		);

		$r->add_control(
			'item_bg',
			array(
				'label'       => 'Culoare fundal imagine',
				'description' => 'Placeholder cât timp nu ai imaginea.',
				'type'        => Controls_Manager::COLOR,
				'selectors'   => array(
					'{{WRAPPER}} .pf {{CURRENT_ITEM}}.pf-img' => 'background-color: {{VALUE}};',
				),
			)
		);

		$r->add_control(
			'item_position',
			array(
				'label'     => 'Punctul de focus al imaginii',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => $this->position_options(),
				'selectors' => array(
					'{{WRAPPER}} .pf {{CURRENT_ITEM}} .pf-photo' => 'object-position: {{VALUE}};',
				),
			)
		);

		$r->add_control(
			'item_alt',
			array(
				'label'       => 'Text alternativ (alt)',
				'description' => 'Gol = textul alt din Media Library.',
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => 'Elemente',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $r->get_controls(),
				'title_field' => '{{{ item_title }}}',
				'default'     => array(
					array(
						'item_title' => 'Calitate',
						'item_desc'  => 'Prioritizăm calitatea produselor având standarde ridicate de producție și echipamente moderne.',
						'item_bg'    => '#8a8580',
					),
					array(
						'item_title' => 'Rapiditate',
						'item_desc'  => 'Răspundem rapid cerințelor clienților prin producție locală, procese eficiente și livrare direct de la fabrică.',
						'item_bg'    => '#b0552a',
					),
					array(
						'item_title' => 'Adaptabilitate',
						'item_desc'  => 'Ne adaptăm situațiilor din teren și găsim soluții eficiente pentru proiectele rezidențiale sau de infrastructură.',
						'item_bg'    => '#5f5a55',
					),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'image',
				'default'   => 'large',
				'exclude'   => array( 'custom' ),
				'separator' => 'before',
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

		$this->add_control(
			'list_label',
			array(
				'label'       => 'Nume listă (accesibilitate)',
				'description' => 'Nu se vede; îl citesc cititoarele de ecran.',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Avantajele noastre',
			)
		);

		$this->end_controls_section();

		/* ================= CONTENT – Comportament ================= */
		$this->start_controls_section(
			'section_behavior',
			array(
				'label' => 'Comportament',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'trigger',
			array(
				'label'       => 'Imaginea se schimbă la (desktop)',
				'description' => 'Pe mobil se schimbă la atingere sau glisare.',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'hover',
				'options'     => array(
					'hover' => 'Hover (și click)',
					'click' => 'Doar click',
				),
			)
		);

		$this->add_control(
			'effect',
			array(
				'label'   => 'Efect schimbare imagine',
				'type'    => Controls_Manager::SELECT,
				'default' => 'fade',
				'options' => array(
					'fade'   => 'Estompare cu zoom ușor (recomandat)',
					'reveal' => 'Dezvăluire de jos în sus',
					'slide'  => 'Alunecare în sus',
					'blur'   => 'Estompare din blur',
				),
			)
		);

		$this->add_control(
			'duration',
			array(
				'label'       => 'Durata tranziției (ms)',
				'description' => 'Mai mare = mai lent și mai fin.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 4000,
				'step'        => 50,
				'selectors'   => array(
					$this->sel() => '--pf-dur: {{VALUE}}ms;',
				),
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => 'Trece singur prin elemente',
				'description'  => 'Se oprește când utilizatorul interacționează. Oprit în editor.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'autoplay_delay',
			array(
				'label'     => 'Timp pe fiecare element (ms)',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1500,
				'max'       => 30000,
				'step'      => 250,
				'default'   => 5000,
				'condition' => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'show_dots',
			array(
				'label'        => 'Puncte de progres (la carduri glisante)',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);

		$this->end_controls_section();

		/* ================= STYLE – Așezare ================= */
		$this->start_controls_section(
			'section_style_layout',
			array(
				'label' => 'Așezare',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'layout',
			array(
				'label'                => 'Așezare',
				'description'          => 'Setează separat pe fiecare dispozitiv (iconițele de lângă titlu).',
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'side',
				'tablet_default'       => 'side',
				'mobile_default'       => 'swipe',
				'options'              => array(
					'side'      => 'Listă și imagine alături',
					'swipe'     => 'Imagine sus, carduri glisante lateral',
					'accordion' => 'Imagine sus, listă acordeon',
				),
				'selectors_dictionary' => $this->layout_vars(),
				'selectors'            => array(
					$this->sel() => '{{VALUE}}',
				),
			)
		);

		$this->add_responsive_control(
			'media_side',
			array(
				'label'                => 'Imaginea în',
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => array(
					'left'  => array( 'title' => 'Stânga', 'icon' => 'eicon-h-align-left' ),
					'right' => array( 'title' => 'Dreapta', 'icon' => 'eicon-h-align-right' ),
				),
				'selectors_dictionary' => array(
					'left'  => '--pf-side-order: -1;',
					'right' => '--pf-side-order: 1;',
				),
				'selectors'            => array(
					$this->sel() => '{{VALUE}}',
				),
				'description'          => 'Pentru așezarea „alături”.',
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'       => 'Spațiu între listă și imagine',
				'description' => 'Implicit 40px.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem', '%', 'vw' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 240 ) ),
				'selectors'   => array(
					$this->sel( '.pf-inner' ) => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'list_width',
			array(
				'label'       => 'Lățime listă',
				'description' => 'Pentru așezarea „alături”. Implicit 44%.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%', 'px', 'em', 'rem', 'vw' ),
				'range'       => array(
					'%'  => array( 'min' => 10, 'max' => 90 ),
					'px' => array( 'min' => 150, 'max' => 1200 ),
				),
				'selectors'   => array(
					$this->sel() => '--pf-list-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'align_items',
			array(
				'label'       => 'Aliniere verticală coloane',
				'description' => '„stretch” = lista are aceeași înălțime ca imaginea (elementele se distribuie pe toată înălțimea).',
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array(
					''           => 'Implicit (stretch)',
					'stretch'    => 'stretch',
					'flex-start' => 'Sus',
					'center'     => 'Centru',
					'flex-end'   => 'Jos',
				),
				'selectors'   => array(
					$this->sel( '.pf-inner' ) => 'align-items: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'list_justify',
			array(
				'label'       => 'Distribuire elemente în listă',
				'description' => 'Pentru așezarea „alături”.',
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array(
					''              => 'Implicit (pe toată înălțimea)',
					'space-between' => 'Pe toată înălțimea',
					'flex-start'    => 'Sus',
					'center'        => 'Centru',
					'flex-end'      => 'Jos',
					'space-evenly'  => 'Spațiere egală',
				),
				'selectors'   => array(
					$this->sel() => '--pf-list-justify: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'list_padding',
			array(
				'label'      => 'Padding listă',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					$this->sel( '.pf-list' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		/* ================= STYLE – Elemente listă ================= */
		$this->start_controls_section(
			'section_style_items',
			array(
				'label' => 'Elemente listă',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'items_gap',
			array(
				'label'       => 'Spațiu minim între elemente',
				'description' => 'Implicit 32px în listă, 16px între carduri glisante, 24px la acordeon.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 200 ) ),
				'selectors'   => array(
					$this->sel() => '--pf-items-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'item_direction',
			array(
				'label'     => 'Titlu și descriere',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''       => 'Implicit (alături pe desktop, unul sub altul pe mobil)',
					'row'    => 'Alături',
					'column' => 'Unul sub altul',
				),
				'selectors' => array(
					$this->sel( '.pf-row' ) => 'flex-direction: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'title_width',
			array(
				'label'       => 'Lățime coloană titlu',
				'description' => 'Când sunt alături. Implicit 170px.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', '%', 'em', 'rem' ),
				'range'       => array( 'px' => array( 'min' => 40, 'max' => 500 ) ),
				'selectors'   => array(
					$this->sel() => '--pf-title-w: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'row_gap',
			array(
				'label'       => 'Spațiu titlu – descriere',
				'description' => 'Implicit 24px alături, 10px unul sub altul.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'selectors'   => array(
					$this->sel( '.pf-row' ) => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'desc_max',
			array(
				'label'       => 'Lățime maximă descriere',
				'description' => 'Implicit 340px.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', '%', 'em', 'rem' ),
				'range'       => array( 'px' => array( 'min' => 100, 'max' => 900 ) ),
				'selectors'   => array(
					$this->sel() => '--pf-desc-max: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'text_speed',
			array(
				'label'     => 'Durata schimbării de culoare (ms)',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 3000,
				'step'      => 50,
				'separator' => 'before',
				'selectors' => array(
					$this->sel() => '--pf-text-dur: {{VALUE}}ms;',
				),
			)
		);

		$this->end_controls_section();

		/* ================= STYLE – Titlu ================= */
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
				'name'     => 'title_typography',
				'selector' => $this->sel( '.pf-title' ),
			)
		);

		$this->add_control( 'title_color', array( 'label' => 'Culoare activ', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pf-title: {{VALUE}};' ) ) );
		$this->add_control( 'title_color_off', array( 'label' => 'Culoare inactiv', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pf-title-off: {{VALUE}};' ) ) );
		$this->add_control( 'title_color_hover', array( 'label' => 'Culoare inactiv la hover', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pf-title-hover: {{VALUE}};' ) ) );

		$this->end_controls_section();

		/* ================= STYLE – Descriere ================= */
		$this->start_controls_section(
			'section_style_desc',
			array(
				'label' => 'Descriere',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'desc_typography',
				'selector' => $this->sel( '.pf-desc' ),
			)
		);

		$this->add_control( 'desc_color', array( 'label' => 'Culoare activ', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pf-desc: {{VALUE}};' ) ) );
		$this->add_control( 'desc_color_off', array( 'label' => 'Culoare inactiv', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pf-desc-off: {{VALUE}};' ) ) );

		$this->end_controls_section();

		/* ================= STYLE – Imagine ================= */
		$this->start_controls_section(
			'section_style_image',
			array(
				'label' => 'Imagine',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'ratio',
			array(
				'label'       => 'Proporție lățime / înălțime',
				'description' => 'Ex.: 1.33 (4:3), 1 (pătrat), 1.78 (16:9). Implicit 1.33.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0.2,
				'max'         => 5,
				'step'        => 0.01,
				'selectors'   => array(
					$this->sel( '.pf-frame' ) => 'aspect-ratio: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_height',
			array(
				'label'       => 'Înălțime fixă (opțional)',
				'description' => 'Dacă o setezi, proporția de mai sus e ignorată.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'vh', 'em', 'rem' ),
				'range'       => array(
					'px' => array( 'min' => 100, 'max' => 1200 ),
					'vh' => array( 'min' => 10, 'max' => 100 ),
				),
				'selectors'   => array(
					$this->sel( '.pf-frame' ) => 'height: {{SIZE}}{{UNIT}}; aspect-ratio: auto;',
				),
			)
		);

		$this->add_responsive_control(
			'image_radius',
			array(
				'label'      => 'Colțuri rotunjite',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					$this->sel( '.pf-frame' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_fit',
			array(
				'label'     => 'Încadrare imagine',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''        => 'Implicit (Cover)',
					'cover'   => 'Cover – umple, taie marginile',
					'contain' => 'Contain – imaginea întreagă',
				),
				'selectors' => array(
					$this->sel( '.pf-photo' ) => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_bg',
			array(
				'label'     => 'Culoare fundal',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pf-frame' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_zoom',
			array(
				'label'       => 'Zoom la schimbare',
				'description' => 'Cât de „mare” pornește imaginea nouă. 1 = fără zoom.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 1.5,
				'step'        => 0.01,
				'selectors'   => array(
					$this->sel() => '--pf-zoom: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'image_shadow',
				'selector' => $this->sel( '.pf-frame' ),
			)
		);

		$this->end_controls_section();

		/* ================= STYLE – Carduri glisante ================= */
		$this->start_controls_section(
			'section_style_cards',
			array(
				'label' => 'Carduri glisante (mobil)',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'cards_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'Se aplică doar unde ai ales așezarea <strong>„Imagine sus, carduri glisante lateral”</strong> (implicit pe mobil).',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_responsive_control(
			'card_width',
			array(
				'label'       => 'Lățime card',
				'description' => 'Sub 100% se vede o bucată din cardul următor. Implicit 82%.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%', 'px', 'vw' ),
				'range'       => array(
					'%'  => array( 'min' => 40, 'max' => 100 ),
					'px' => array( 'min' => 150, 'max' => 600 ),
				),
				'selectors'   => array(
					$this->sel() => '--pf-card-w: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => 'Padding card',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array(
					$this->sel() => '--pf-card-pad: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'card_radius',
			array(
				'label'      => 'Colțuri rotunjite',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					$this->sel() => '--pf-card-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control( 'card_bg', array( 'label' => 'Fundal card', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pf-card-bg: {{VALUE}};' ) ) );
		$this->add_control( 'card_bg_on', array( 'label' => 'Fundal card activ', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pf-card-bg-on: {{VALUE}};' ) ) );
		$this->add_control( 'card_border', array( 'label' => 'Bordură card', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pf-card-border: {{VALUE}};' ) ) );
		$this->add_control( 'card_border_on', array( 'label' => 'Bordură card activ', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pf-card-border-on: {{VALUE}};' ) ) );

		$this->add_control( 'dot_color', array( 'label' => 'Culoare puncte', 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => array( $this->sel() => '--pf-dot: {{VALUE}};' ) ) );
		$this->add_control( 'dot_color_on', array( 'label' => 'Culoare punct activ', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pf-dot-on: {{VALUE}};' ) ) );

		$this->end_controls_section();
	}

	/* ================= Helpers ================= */
	private function position_options() {
		return array(
			''              => 'Implicit (centru)',
			'center center' => 'Centru',
			'center top'    => 'Sus',
			'center bottom' => 'Jos',
			'left center'   => 'Stânga',
			'right center'  => 'Dreapta',
		);
	}

	private function mobile_breakpoint() {
		$bp = 767;
		try {
			if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->breakpoints ) ) {
				$active = \Elementor\Plugin::$instance->breakpoints->get_active_breakpoints();
				if ( isset( $active['mobile'] ) ) {
					$bp = (int) $active['mobile']->get_value();
				}
			}
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
			// rămâne 767
		}
		return $bp;
	}

	private function image_html( $item, $index, $size, $bp ) {
		$media = isset( $item['item_image'] ) ? $item['item_image'] : array();
		if ( empty( $media['url'] ) && empty( $media['id'] ) ) {
			return '';
		}

		$alt = isset( $item['item_alt'] ) ? trim( $item['item_alt'] ) : '';
		if ( '' === $alt && ! empty( $media['id'] ) ) {
			$alt = (string) get_post_meta( (int) $media['id'], '_wp_attachment_image_alt', true );
		}
		if ( '' === $alt && ! empty( $item['item_title'] ) ) {
			$alt = wp_strip_all_tags( $item['item_title'] );
		}

		$attrs = array(
			'class'     => 'pf-photo',
			'alt'       => $alt,
			'decoding'  => 'async',
			'draggable' => 'false',
			'loading'   => 0 === $index ? 'eager' : 'lazy',
		);

		$img = '';
		if ( ! empty( $media['id'] ) ) {
			$img = wp_get_attachment_image( (int) $media['id'], $size, false, $attrs );
		}
		if ( '' === $img ) {
			$attr_html = '';
			foreach ( $attrs as $name => $value ) {
				$attr_html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
			}
			$img = '<img src="' . esc_url( $media['url'] ) . '"' . $attr_html . '>';
		}

		$mobile = '';
		if ( ! empty( $item['item_image_mobile']['id'] ) ) {
			$mobile = (string) wp_get_attachment_image_url( (int) $item['item_image_mobile']['id'], $size );
		}
		if ( '' === $mobile && ! empty( $item['item_image_mobile']['url'] ) ) {
			$mobile = $item['item_image_mobile']['url'];
		}
		if ( $mobile ) {
			return '<picture class="pf-picture"><source media="(max-width: ' . (int) $bp . 'px)" srcset="' . esc_url( $mobile ) . '">' . $img . '</picture>';
		}
		return $img;
	}

	/* ================= RENDER ================= */
	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = ( isset( $s['items'] ) && is_array( $s['items'] ) ) ? array_values( $s['items'] ) : array();
		if ( empty( $items ) ) {
			return;
		}

		$uid    = 'pf-' . $this->get_id();
		$effect = isset( $s['effect'] ) && in_array( $s['effect'], array( 'fade', 'reveal', 'slide', 'blur' ), true ) ? $s['effect'] : 'fade';
		$tag    = Utils::validate_html_tag( ! empty( $s['title_tag'] ) ? $s['title_tag'] : 'h3' );
		$size   = ! empty( $s['image_size'] ) ? $s['image_size'] : 'large';
		$bp     = $this->mobile_breakpoint();

		$options = array(
			'trigger'  => ( isset( $s['trigger'] ) && 'click' === $s['trigger'] ) ? 'click' : 'hover',
			'autoplay' => isset( $s['autoplay'] ) && 'yes' === $s['autoplay'],
			'delay'    => ! empty( $s['autoplay_delay'] ) ? max( 1500, (int) $s['autoplay_delay'] ) : 5000,
		);
		?>
		<div class="pf pf-fx-<?php echo esc_attr( $effect ); ?>" data-pf="<?php echo esc_attr( wp_json_encode( $options ) ); ?>">
			<div class="pf-inner">
				<div class="pf-list" role="tablist" aria-label="<?php echo esc_attr( ! empty( $s['list_label'] ) ? $s['list_label'] : 'Listă' ); ?>">
					<?php foreach ( $items as $i => $item ) : ?>
						<?php
						$rid    = isset( $item['_id'] ) ? $item['_id'] : $i;
						$active = 0 === $i;
						?>
						<div class="pf-item elementor-repeater-item-<?php echo esc_attr( $rid ); ?><?php echo $active ? ' is-active' : ''; ?>" role="tab" id="<?php echo esc_attr( $uid . '-tab-' . $i ); ?>" aria-controls="<?php echo esc_attr( $uid . '-img-' . $i ); ?>" aria-selected="<?php echo $active ? 'true' : 'false'; ?>" tabindex="<?php echo $active ? '0' : '-1'; ?>">
							<div class="pf-row">
								<<?php echo $tag; // phpcs:ignore ?> class="pf-title"><?php echo esc_html( isset( $item['item_title'] ) ? $item['item_title'] : '' ); ?></<?php echo $tag; // phpcs:ignore ?>>
								<?php if ( ! empty( $item['item_desc'] ) ) : ?>
									<div class="pf-desc-wrap"><div class="pf-desc-inner"><p class="pf-desc"><?php echo nl2br( esc_html( $item['item_desc'] ) ); // phpcs:ignore -- escapat ?></p></div></div>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="pf-media">
					<div class="pf-frame">
						<?php foreach ( $items as $i => $item ) : ?>
							<?php $rid = isset( $item['_id'] ) ? $item['_id'] : $i; ?>
							<div class="pf-img elementor-repeater-item-<?php echo esc_attr( $rid ); ?><?php echo 0 === $i ? ' is-active' : ''; ?>" id="<?php echo esc_attr( $uid . '-img-' . $i ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $uid . '-tab-' . $i ); ?>"<?php echo 0 === $i ? '' : ' aria-hidden="true"'; ?>>
								<?php echo $this->image_html( $item, $i, $size, $bp ); // phpcs:ignore -- generat cu funcțiile WordPress ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<?php if ( count( $items ) > 1 && isset( $s['show_dots'] ) && 'yes' === $s['show_dots'] ) : ?>
				<div class="pf-dots">
					<?php foreach ( $items as $i => $item ) : ?>
						<button type="button" class="pf-dot<?php echo 0 === $i ? ' is-active' : ''; ?>" aria-label="<?php echo esc_attr( 'Arată: ' . ( isset( $item['item_title'] ) ? wp_strip_all_tags( $item['item_title'] ) : ( $i + 1 ) ) ); ?>"></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
