<?php
/**
 * Pavva Widgets – Carusel produse
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
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Css_Filter;
use Elementor\Icons_Manager;
use Elementor\Utils;

class Pavva_Carusel_Widget extends Widget_Base {

	public function get_name() {
		return 'carusel_produse';
	}

	public function get_title() {
		return 'Pavva – Carusel produse';
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_categories() {
		return array( 'pavva' );
	}

	public function get_keywords() {
		return array( 'carusel', 'carousel', 'slider', 'produse', 'carduri' );
	}

	public function get_style_depends() {
		return array( 'pavva-carusel' );
	}

	public function get_script_depends() {
		return array( 'pavva-carusel' );
	}

	/* Prefix comun pentru toate selectorii: bate stilurile globale ale temei. */
	private function sel( $inner = '' ) {
		return '{{WRAPPER}} .cc-carousel' . ( $inner ? ' ' . $inner : '' );
	}

	protected function register_controls() {
		$this->controls_content();
		$this->controls_behavior();
		$this->controls_style_heading();
		$this->controls_style_card();
		$this->controls_style_image();
		$this->controls_style_texts();
		$this->controls_style_arrow();
		$this->controls_style_bar();
	}

	/* ================================================================
	 * CONTENT
	 * ================================================================ */
	private function controls_content() {

		/* ---------- Titlu secțiune ---------- */
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
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'heading_text',
			array(
				'label'       => 'Text',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Cele mai căutate',
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

		/* ---------- Carduri ---------- */
		$this->start_controls_section(
			'section_items',
			array(
				'label' => 'Carduri',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'item_image',
			array(
				'label'       => 'Imagine',
				'description' => 'Opțional. Fără imagine, cardul afișează culoarea de mai jos.',
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'item_bg',
			array(
				'label'       => 'Culoare fundal imagine',
				'description' => 'Se vede cât timp nu ai pus imaginea (placeholder) sau în spatele unei imagini transparente.',
				'type'        => Controls_Manager::COLOR,
				'selectors'   => array(
					'{{WRAPPER}} .cc-carousel {{CURRENT_ITEM}} .cc-media' => 'background-color: {{VALUE}};',
				),
			)
		);

		$repeater->add_control(
			'item_title',
			array(
				'label'       => 'Titlu',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Nume produs',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'item_desc',
			array(
				'label'   => 'Descriere',
				'type'    => Controls_Manager::TEXTAREA,
				'default' => 'Descriere scurtă a produsului.',
				'rows'    => 4,
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'item_link',
			array(
				'label'       => 'Link',
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://site-ul-tau.ro/produs',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => 'Carduri',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ item_title }}}',
				'default'     => array(
					array(
						'item_title' => 'Kombi',
						'item_bg'    => '#cfc9bf',
						'item_desc'  => 'Pavaj modular în trei formate, pentru amenajări moderne și integrare armonioasă în orice proiect.',
					),
					array(
						'item_title' => 'Alegria',
						'item_bg'    => '#b9ab9b',
						'item_desc'  => 'Pavaj dreptunghiular cu grosimi variate, pentru amenajări rezidențiale și suprafețe cu tipuri diferite de trafic.',
					),
					array(
						'item_title' => 'Cammino',
						'item_bg'    => '#a9a9a4',
						'item_desc'  => 'Pavaj dreptunghiular de format mare, pentru alei și suprafețe cu trafic pietonal sau auto ușor.',
					),
					array(
						'item_title' => 'Produs 4',
						'item_bg'    => '#d8d2c7',
						'item_desc'  => 'Descriere scurtă a produsului.',
					),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'image',
				'default'   => 'large',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => 'Tag HTML titlu card',
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => $this->tag_options(),
			)
		);

		$this->add_control(
			'show_desc',
			array(
				'label'        => 'Afișează descrierea',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'arrow_type',
			array(
				'label'   => 'Săgeată',
				'type'    => Controls_Manager::SELECT,
				'default' => 'default',
				'options' => array(
					'default' => 'Săgeata implicită (subțire)',
					'upload'  => 'SVG sau imagine încărcată de mine',
					'icon'    => 'Iconiță din bibliotecă',
					'none'    => 'Fără săgeată',
				),
			)
		);

		$this->add_control(
			'arrow_upload',
			array(
				'label'       => 'Fișier săgeată',
				'description' => 'Recomandat SVG: i se poate schimba culoarea din Style → Săgeată. PNG/JPG se afișează cu culorile originale.',
				'type'        => Controls_Manager::MEDIA,
				'media_types' => array( 'image', 'svg' ),
				'default'     => array( 'url' => '' ),
				'condition'   => array( 'arrow_type' => 'upload' ),
			)
		);

		$this->add_control(
			'arrow_icon',
			array(
				'label'     => 'Iconiță',
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'fas fa-arrow-right',
					'library' => 'fa-solid',
				),
				'condition' => array( 'arrow_type' => 'icon' ),
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * COMPORTAMENT
	 * ================================================================ */
	private function controls_behavior() {

		$this->start_controls_section(
			'section_behavior',
			array(
				'label' => 'Carusel',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'per_view',
			array(
				'label'          => 'Carduri vizibile',
				'description'    => 'Poți pune și zecimale: 2.75 = 2 carduri întregi și o bucată din al treilea.',
				'type'           => Controls_Manager::NUMBER,
				'min'            => 1,
				'max'            => 12,
				'step'           => 0.05,
				'default'        => 2.75,
				'tablet_default' => 2.3,
				'mobile_default' => 1.2,
				'selectors'      => array(
					$this->sel( '.cc-track' ) => '--cc-per-view: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'          => 'Spațiu între carduri',
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px', 'em', 'rem', 'vw' ),
				'range'          => array(
					'px'  => array( 'min' => 0, 'max' => 120 ),
					'em'  => array( 'min' => 0, 'max' => 8, 'step' => 0.05 ),
					'rem' => array( 'min' => 0, 'max' => 8, 'step' => 0.05 ),
					'vw'  => array( 'min' => 0, 'max' => 10, 'step' => 0.1 ),
				),
				'default'        => array( 'size' => 24, 'unit' => 'px' ),
				'tablet_default' => array( 'size' => 20, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 16, 'unit' => 'px' ),
				'selectors'      => array(
					$this->sel( '.cc-track' ) => '--cc-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'bleed_right',
			array(
				'label'                => 'Lipit de marginea dreaptă a ecranului',
				'description'          => 'Cardurile pornesc aliniate la stânga cu restul paginii, dar ies în dreapta până la marginea ecranului.',
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'no',
				'options'              => array(
					'yes' => 'Da',
					'no'  => 'Nu',
				),
				'selectors_dictionary' => array(
					'yes' => '1',
					'no'  => '0',
				),
				'selectors'            => array(
					$this->sel() => '--cc-bleed-on: {{VALUE}};',
				),
				'separator'            => 'before',
			)
		);

		$this->add_responsive_control(
			'bleed_bar',
			array(
				'label'                => 'Bara de progres iese și ea până la margine',
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'no',
				'options'              => array(
					'yes' => 'Da',
					'no'  => 'Nu',
				),
				'selectors_dictionary' => array(
					'yes' => '1',
					'no'  => '0',
				),
				'selectors'            => array(
					$this->sel() => '--cc-bar-bleed-on: {{VALUE}};',
				),
				'condition'            => array( 'show_bar' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'end_space',
			array(
				'label'       => 'Spațiu după ultimul card',
				'description' => 'Gol = automat: dacă caruselul iese până la marginea ecranului, ultimul card se oprește la aceeași distanță de margine ca primul. Setează o valoare ca s-o controlezi manual.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem', 'vw' ),
				'range'       => array(
					'px' => array( 'min' => 0, 'max' => 400 ),
					'vw' => array( 'min' => 0, 'max' => 30, 'step' => 0.5 ),
				),
				'selectors'   => array(
					$this->sel() => '--cc-end-space: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'mouse_drag',
			array(
				'label'        => 'Trage cu mouse-ul',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'inertia',
			array(
				'label'        => 'Alunecare (inerție)',
				'description'  => 'Dacă tragi repede și dai drumul, cardurile continuă să alunece.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'mouse_drag' => 'yes' ),
			)
		);

		$this->add_control(
			'anim_duration',
			array(
				'label'       => 'Durata alunecării (ms)',
				'description' => 'Cât durează oprirea lină pe card. Mai mare = mai lent.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 100,
				'max'         => 3000,
				'step'        => 50,
				'default'     => 700,
			)
		);

		$this->add_control(
			'show_bar',
			array(
				'label'        => 'Bară de progres',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'bar_mode',
			array(
				'label'     => 'Tip bară',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'fill',
				'options'   => array(
					'fill' => 'Se umple de la stânga la dreapta',
					'move' => 'Se mută (ca un scrollbar)',
				),
				'condition' => array( 'show_bar' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * STYLE: titlu secțiune
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
					$this->sel( '.cc-heading' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'heading_typography',
				'selector' => $this->sel( '.cc-heading' ),
			)
		);

		$this->add_responsive_control(
			'heading_align',
			array(
				'label'     => 'Aliniere',
				'type'      => Controls_Manager::CHOOSE,
				'options'   => $this->align_options(),
				'selectors' => array(
					$this->sel( '.cc-heading' ) => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'heading_margin',
			array(
				'label'      => 'Margin',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%', 'vw' ),
				'selectors'  => array(
					$this->sel( '.cc-heading' ) => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * STYLE: card
	 * ================================================================ */
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
					$this->sel( '.cc-card' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'body_padding',
			array(
				'label'       => 'Padding zonă text',
				'description' => 'Spațiul din jurul titlului și al descrierii, sub imagine.',
				'type'        => Controls_Manager::DIMENSIONS,
				'size_units'  => array( 'px', 'em', 'rem', '%' ),
				'selectors'   => array(
					$this->sel( '.cc-body' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					$this->sel( '.cc-card' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'card_tabs' );

		/* Normal */
		$this->start_controls_tab( 'card_tab_normal', array( 'label' => 'Normal' ) );

		$this->add_control(
			'card_bg',
			array(
				'label'     => 'Fundal',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.cc-card' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => $this->sel( '.cc-card' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => $this->sel( '.cc-card' ),
			)
		);

		$this->end_controls_tab();

		/* Hover */
		$this->start_controls_tab( 'card_tab_hover', array( 'label' => 'Hover' ) );

		$this->add_control(
			'card_bg_hover',
			array(
				'label'     => 'Fundal',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.cc-card:hover' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'card_border_color_hover',
			array(
				'label'     => 'Culoare bordură',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.cc-card:hover' ) => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow_hover',
				'selector' => $this->sel( '.cc-card:hover' ),
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
					$this->sel() => '--cc-lift: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_control(
			'card_speed',
			array(
				'label'     => 'Durata efectelor hover (ms)',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 3000,
				'step'      => 50,
				'separator' => 'before',
				'selectors' => array(
					$this->sel() => '--cc-speed-card: {{VALUE}}ms;',
				),
			)
		);

		$this->add_control(
			'focus_color',
			array(
				'label'       => 'Culoare contur focus',
				'description' => 'Conturul care apare când se navighează cu tastatura.',
				'type'        => Controls_Manager::COLOR,
				'selectors'   => array(
					$this->sel() => '--cc-focus: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * STYLE: imagine
	 * ================================================================ */
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
				'label'     => 'Raport imagine',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''       => 'Implicit (4:3)',
					'1 / 1'  => '1:1 (pătrat)',
					'5 / 4'  => '5:4',
					'4 / 3'  => '4:3',
					'3 / 2'  => '3:2',
					'16 / 9' => '16:9',
					'21 / 9' => '21:9',
					'4 / 5'  => '4:5 (portret)',
					'3 / 4'  => '3:4 (portret)',
					'2 / 3'  => '2:3 (portret)',
				),
				'selectors' => array(
					$this->sel( '.cc-media' ) => 'aspect-ratio: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_height',
			array(
				'label'       => 'Înălțime fixă',
				'description' => 'Dacă setezi o înălțime, raportul de mai sus este ignorat.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem', 'vh' ),
				'range'       => array(
					'px'  => array( 'min' => 50, 'max' => 1000 ),
					'em'  => array( 'min' => 3, 'max' => 60 ),
					'rem' => array( 'min' => 3, 'max' => 60 ),
					'vh'  => array( 'min' => 5, 'max' => 100 ),
				),
				'selectors'   => array(
					$this->sel( '.cc-media' ) => 'height: {{SIZE}}{{UNIT}}; aspect-ratio: auto;',
				),
			)
		);

		$this->add_control(
			'image_fit',
			array(
				'label'     => 'Potrivire imagine',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''        => 'Implicit (Cover)',
					'cover'   => 'Cover – umple, taie marginile',
					'contain' => 'Contain – imaginea întreagă',
					'fill'    => 'Fill – întinde',
				),
				'selectors' => array(
					$this->sel( '.cc-media img' ) => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_position',
			array(
				'label'     => 'Poziție imagine',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''              => 'Implicit (centru)',
					'center center' => 'Centru',
					'center top'    => 'Sus',
					'center bottom' => 'Jos',
					'left center'   => 'Stânga',
					'right center'  => 'Dreapta',
				),
				'selectors' => array(
					$this->sel( '.cc-media img' ) => 'object-position: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_bg',
			array(
				'label'     => 'Fundal zonă imagine',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.cc-media' ) => 'background-color: {{VALUE}};',
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
					$this->sel( '.cc-media' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'image_zoom',
			array(
				'label'       => 'Zoom la hover',
				'description' => '1 = fără zoom. 1.04 = zoom ușor.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 2,
				'step'        => 0.01,
				'separator'   => 'before',
				'selectors'   => array(
					$this->sel() => '--cc-zoom: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_zoom_speed',
			array(
				'label'     => 'Durata zoom (ms)',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 3000,
				'step'      => 50,
				'selectors' => array(
					$this->sel() => '--cc-speed-zoom: {{VALUE}}ms;',
				),
			)
		);

		$this->start_controls_tabs( 'image_filter_tabs' );

		$this->start_controls_tab( 'image_filter_normal', array( 'label' => 'Normal' ) );
		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name'     => 'image_filters',
				'selector' => $this->sel( '.cc-media img' ),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'image_filter_hover', array( 'label' => 'Hover' ) );
		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name'     => 'image_filters_hover',
				'selector' => $this->sel( '.cc-card:hover .cc-media img' ),
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/* ================================================================
	 * STYLE: titlu card + descriere
	 * ================================================================ */
	private function controls_style_texts() {

		$this->start_controls_section(
			'section_style_title',
			array(
				'label' => 'Titlu card',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.cc-title' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'title_color_hover',
			array(
				'label'     => 'Culoare la hover',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.cc-card:hover .cc-title' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'title_fx',
			array(
				'label'       => 'Efect la hover',
				'description' => 'Pornește când mouse-ul e pe card.',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'none',
				'options'     => array(
					'none'      => 'Fără (doar culoarea, dacă e setată)',
					'underline' => 'Linie sub titlu care crește de la stânga',
					'center'    => 'Linie sub titlu care crește din centru',
					'shift'     => 'Titlul se deplasează ușor spre dreapta',
				),
			)
		);

		$this->add_control(
			'title_line_color',
			array(
				'label'       => 'Culoare linie',
				'description' => 'Gol = culoarea titlului.',
				'type'        => Controls_Manager::COLOR,
				'selectors'   => array(
					$this->sel() => '--cc-tline-color: {{VALUE}};',
				),
				'condition'   => array( 'title_fx' => array( 'underline', 'center' ) ),
			)
		);

		$this->add_control(
			'title_line_height',
			array(
				'label'      => 'Grosime linie',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 1, 'max' => 6 ) ),
				'selectors'  => array(
					$this->sel() => '--cc-tline-h: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'title_fx' => array( 'underline', 'center' ) ),
			)
		);

		$this->add_control(
			'title_line_offset',
			array(
				'label'      => 'Distanță linie sub text',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 16 ) ),
				'selectors'  => array(
					$this->sel() => '--cc-tline-y: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'title_fx' => array( 'underline', 'center' ) ),
			)
		);

		$this->add_control(
			'title_shift',
			array(
				'label'      => 'Distanță deplasare',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'selectors'  => array(
					$this->sel() => '--cc-title-shift: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'title_fx' => 'shift' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => $this->sel( '.cc-title' ),
			)
		);

		$this->add_responsive_control(
			'title_margin',
			array(
				'label'       => 'Margin rând titlu',
				'description' => 'Rândul cu titlul și săgeata.',
				'type'        => Controls_Manager::DIMENSIONS,
				'size_units'  => array( 'px', 'em', 'rem', '%' ),
				'selectors'   => array(
					$this->sel( '.cc-row' ) => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_desc',
			array(
				'label'     => 'Descriere',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_desc' => 'yes' ),
			)
		);

		$this->add_control(
			'desc_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.cc-desc' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'desc_color_hover',
			array(
				'label'     => 'Culoare la hover',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.cc-card:hover .cc-desc' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'desc_typography',
				'selector' => $this->sel( '.cc-desc' ),
			)
		);

		$this->add_responsive_control(
			'desc_margin',
			array(
				'label'      => 'Margin',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors'  => array(
					$this->sel( '.cc-desc' ) => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'desc_lines',
			array(
				'label'       => 'Număr maxim de rânduri',
				'description' => 'Textul mai lung se taie cu „…”. Lasă gol pentru fără limită.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 20,
				'selectors'   => array(
					$this->sel( '.cc-desc' ) => 'display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: {{VALUE}}; line-clamp: {{VALUE}}; overflow: hidden;',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * STYLE: săgeată
	 * ================================================================ */
	private function controls_style_arrow() {

		$this->start_controls_section(
			'section_style_arrow',
			array(
				'label'     => 'Săgeată',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'arrow_type!' => 'none' ),
			)
		);

		$this->add_control(
			'arrow_svg_color_mode',
			array(
				'label'       => 'Cum se colorează SVG-ul',
				'description' => 'Pentru SVG-uri încărcate sau din bibliotecă. Dacă săgeata ta e desenată din linii, alege „Contur”.',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => array(
					'auto'     => 'Automat',
					'fill'     => 'Umplere (fill)',
					'stroke'   => 'Contur (stroke)',
					'both'     => 'Umplere + contur',
					'original' => 'Culorile originale ale fișierului',
				),
				'condition'   => array( 'arrow_type' => array( 'upload', 'icon' ) ),
			)
		);

		$this->add_control(
			'arrow_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.cc-arrow-wrap' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_color_hover',
			array(
				'label'     => 'Culoare la hover',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.cc-card:hover .cc-arrow-wrap' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'arrow_size',
			array(
				'label'      => 'Mărime',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px'  => array( 'min' => 8, 'max' => 80 ),
					'em'  => array( 'min' => 0.5, 'max' => 5, 'step' => 0.05 ),
					'rem' => array( 'min' => 0.5, 'max' => 5, 'step' => 0.05 ),
				),
				'selectors'  => array(
					$this->sel( '.cc-arrow-wrap' ) => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'arrow_no_push',
			array(
				'label'        => 'Săgeata nu mărește rândul',
				'description'  => 'Activ: poți face săgeata oricât de mare fără să împingă titlul, descrierea sau imaginea. Săgeata rămâne centrată pe titlu.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'selectors'    => array(
					$this->sel( '.cc-arrow-wrap' ) => 'height: 0; overflow: visible;',
				),
			)
		);

		$this->add_responsive_control(
			'arrow_offset_y',
			array(
				'label'       => 'Ajustare verticală',
				'description' => 'Mută săgeata puțin mai sus (minus) sau mai jos (plus), pentru aliniere perfectă cu titlul.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => -40, 'max' => 40 ) ),
				'selectors'   => array(
					$this->sel() => '--cc-arrow-y: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'arrow_gap',
			array(
				'label'      => 'Spațiu față de titlu',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors'  => array(
					$this->sel( '.cc-row' ) => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'arrow_dir',
			array(
				'label'                => 'Mișcare săgeată la hover',
				'type'                 => Controls_Manager::SELECT,
				'default'              => '',
				'options'              => array(
					''      => 'Implicit (spre dreapta)',
					'right' => 'Spre dreapta (în afară)',
					'left'  => 'Spre stânga (în interior)',
					'none'  => 'Fără mișcare',
				),
				'selectors_dictionary' => array(
					'right' => '--cc-arrow-dir: 1;',
					'left'  => '--cc-arrow-dir: -1;',
					'none'  => '--cc-arrow-dir: 0;',
				),
				'selectors'            => array(
					$this->sel() => '{{VALUE}}',
				),
			)
		);

		$this->add_control(
			'arrow_shift',
			array(
				'label'       => 'Distanță deplasare la hover',
				'description' => 'Implicit 4px. Direcția o alegi mai sus.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					$this->sel() => '--cc-arrow-shift: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * STYLE: bară progres
	 * ================================================================ */
	private function controls_style_bar() {

		$this->start_controls_section(
			'section_style_bar',
			array(
				'label'     => 'Bară de progres',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_bar' => 'yes' ),
			)
		);

		$this->add_control(
			'bar_track_color',
			array(
				'label'     => 'Culoare fundal bară',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.cc-bar' ) => '--cc-bar-track: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'bar_fill_color',
			array(
				'label'     => 'Culoare progres',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.cc-bar' ) => '--cc-bar-fill: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'bar_track_height',
			array(
				'label'      => 'Grosime fundal',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 20 ) ),
				'selectors'  => array(
					$this->sel( '.cc-bar' ) => '--cc-bar-track-h: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'bar_fill_height',
			array(
				'label'      => 'Grosime progres',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 1, 'max' => 20 ) ),
				'selectors'  => array(
					$this->sel( '.cc-bar' ) => '--cc-bar-fill-h: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'bar_radius',
			array(
				'label'      => 'Colțuri rotunjite',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 20 ) ),
				'selectors'  => array(
					$this->sel( '.cc-bar' ) => '--cc-bar-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'bar_spacing',
			array(
				'label'      => 'Distanță față de carduri',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 150 ) ),
				'selectors'  => array(
					$this->sel( '.cc-bar' ) => 'margin-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'bar_width',
			array(
				'label'      => 'Lățime bară',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%', 'px' ),
				'range'      => array(
					'%'  => array( 'min' => 10, 'max' => 100 ),
					'px' => array( 'min' => 50, 'max' => 2000 ),
				),
				'selectors'  => array(
					$this->sel( '.cc-bar' ) => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'bar_align',
			array(
				'label'     => 'Aliniere bară',
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array( 'title' => 'Stânga', 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => 'Centru', 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => 'Dreapta', 'icon' => 'eicon-text-align-right' ),
				),
				'selectors_dictionary' => array(
					'left'   => 'margin-left: 0; margin-right: auto;',
					'center' => 'margin-left: auto; margin-right: auto;',
					'right'  => 'margin-left: auto; margin-right: 0;',
				),
				'selectors' => array(
					$this->sel( '.cc-bar' ) => '{{VALUE}}',
				),
			)
		);

		$this->add_control(
			'bar_speed',
			array(
				'label'     => 'Durata animației barei (ms)',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 3000,
				'step'      => 50,
				'selectors' => array(
					$this->sel() => '--cc-speed-bar: {{VALUE}}ms;',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * Helpers
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

	private function align_options() {
		return array(
			'left'   => array( 'title' => 'Stânga', 'icon' => 'eicon-text-align-left' ),
			'center' => array( 'title' => 'Centru', 'icon' => 'eicon-text-align-center' ),
			'right'  => array( 'title' => 'Dreapta', 'icon' => 'eicon-text-align-right' ),
		);
	}

	private function default_arrow_svg() {
		return '<svg class="cc-arrow-default" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 12h17M14 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}

	/* HTML-ul săgeții, în funcție de tipul ales */
	private function get_arrow_html( $s, $type ) {
		if ( 'icon' === $type && ! empty( $s['arrow_icon']['value'] ) ) {
			ob_start();
			Icons_Manager::render_icon( $s['arrow_icon'], array( 'aria-hidden' => 'true' ) );
			return ob_get_clean();
		}

		if ( 'upload' === $type ) {
			$url = ! empty( $s['arrow_upload']['url'] ) ? $s['arrow_upload']['url'] : '';
			$id  = ! empty( $s['arrow_upload']['id'] ) ? (int) $s['arrow_upload']['id'] : 0;
			if ( '' === $url ) {
				return $this->default_arrow_svg();
			}
			$path    = wp_parse_url( $url, PHP_URL_PATH );
			$is_svg  = $path && 'svg' === strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

			/* SVG: îl punem direct în pagină (inline), ca să i se poată schimba culoarea */
			if ( $is_svg ) {
				ob_start();
				Icons_Manager::render_icon(
					array(
						'library' => 'svg',
						'value'   => array( 'id' => $id, 'url' => $url ),
					),
					array( 'aria-hidden' => 'true' )
				);
				$inline = trim( ob_get_clean() );
				if ( '' !== $inline && false !== stripos( $inline, '<svg' ) ) {
					return $inline;
				}
			}

			/* PNG / JPG / WebP sau SVG care nu s-a putut citi: ca imagine */
			return '<img class="cc-arrow-img" src="' . esc_url( $url ) . '" alt="" draggable="false">';
		}

		return $this->default_arrow_svg();
	}

	/* ================================================================
	 * RENDER
	 * ================================================================ */
	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = isset( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();

		$options = array(
			'drag'     => ( isset( $s['mouse_drag'] ) && 'yes' === $s['mouse_drag'] ),
			'inertia'  => ( isset( $s['inertia'] ) && 'yes' === $s['inertia'] ),
			'duration' => ! empty( $s['anim_duration'] ) ? (int) $s['anim_duration'] : 700,
			'barMode'  => ( isset( $s['bar_mode'] ) && 'move' === $s['bar_mode'] ) ? 'move' : 'fill',
		);

		$heading_tag = Utils::validate_html_tag( ! empty( $s['heading_tag'] ) ? $s['heading_tag'] : 'h2' );
		$title_tag   = Utils::validate_html_tag( ! empty( $s['title_tag'] ) ? $s['title_tag'] : 'h3' );
		$arrow_type  = ! empty( $s['arrow_type'] ) ? $s['arrow_type'] : 'default';
		$show_arrow  = 'none' !== $arrow_type;
		$arrow_html  = $show_arrow ? $this->get_arrow_html( $s, $arrow_type ) : '';
		$color_mode  = ! empty( $s['arrow_svg_color_mode'] ) ? $s['arrow_svg_color_mode'] : 'auto';
		if ( ! in_array( $color_mode, array( 'auto', 'fill', 'stroke', 'both', 'original' ), true ) ) {
			$color_mode = 'auto';
		}
		$show_desc   = isset( $s['show_desc'] ) && 'yes' === $s['show_desc'];
		$show_bar    = isset( $s['show_bar'] ) && 'yes' === $s['show_bar'];
		$label       = ! empty( $s['heading_text'] ) ? $s['heading_text'] : 'Produse';
		?>
		<?php
		$title_fx = isset( $s['title_fx'] ) && in_array( $s['title_fx'], array( 'none', 'underline', 'center', 'shift' ), true ) ? $s['title_fx'] : 'none';
		?>
		<div class="cc-carousel cc-svg-<?php echo esc_attr( $color_mode ); ?> cc-tfx-<?php echo esc_attr( $title_fx ); ?>" data-cc="<?php echo esc_attr( wp_json_encode( $options ) ); ?>">
			<?php if ( isset( $s['show_heading'] ) && 'yes' === $s['show_heading'] && ! empty( $s['heading_text'] ) ) : ?>
				<<?php echo $heading_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="cc-heading"><?php echo esc_html( $s['heading_text'] ); ?></<?php echo $heading_tag; // phpcs:ignore ?>>
			<?php endif; ?>

			<div class="cc-track<?php echo $options['drag'] ? ' cc-draggable' : ''; ?>" tabindex="0" role="region" aria-label="<?php echo esc_attr( $label ); ?>">
				<?php
				foreach ( $items as $index => $item ) :
					$has_link = ! empty( $item['item_link']['url'] );
					$card_tag = $has_link ? 'a' : 'div';
					$card_key = 'card_' . $index;

					$this->add_render_attribute( $card_key, 'class', array( 'cc-card', 'elementor-repeater-item-' . $item['_id'] ) );
					if ( $has_link ) {
						$this->add_link_attributes( $card_key, $item['item_link'] );
						$this->add_render_attribute( $card_key, 'draggable', 'false' );
					}

					$image_html = '';
					if ( ! empty( $item['item_image']['url'] ) || ! empty( $item['item_image']['id'] ) ) {
						$image_settings = array(
							'image'                  => $item['item_image'],
							'image_size'             => isset( $s['image_size'] ) ? $s['image_size'] : 'large',
							'image_custom_dimension' => isset( $s['image_custom_dimension'] ) ? $s['image_custom_dimension'] : array(),
						);
						$image_html = Group_Control_Image_Size::get_attachment_image_html( $image_settings, 'image' );
					}
					?>
					<<?php echo $card_tag; // phpcs:ignore ?> <?php echo $this->get_render_attribute_string( $card_key ); // phpcs:ignore ?>>
						<div class="cc-media"><?php echo $image_html; // phpcs:ignore -- generat de Elementor ?></div>
						<div class="cc-body">
							<div class="cc-row">
								<?php if ( ! empty( $item['item_title'] ) ) : ?>
									<<?php echo $title_tag; // phpcs:ignore ?> class="cc-title"><span class="cc-title-text"><?php echo esc_html( $item['item_title'] ); ?></span></<?php echo $title_tag; // phpcs:ignore ?>>
								<?php endif; ?>
								<?php if ( $show_arrow && '' !== $arrow_html ) : ?>
									<span class="cc-arrow-wrap" aria-hidden="true"><?php echo $arrow_html; // phpcs:ignore -- generat mai sus ?></span>
								<?php endif; ?>
							</div>
							<?php if ( $show_desc && ! empty( $item['item_desc'] ) ) : ?>
								<p class="cc-desc"><?php echo wp_kses_post( nl2br( $item['item_desc'] ) ); ?></p>
							<?php endif; ?>
						</div>
					</<?php echo $card_tag; // phpcs:ignore ?>>
				<?php endforeach; ?>
			</div>

			<?php if ( $show_bar ) : ?>
				<div class="cc-bar" aria-hidden="true"><div class="cc-thumb"></div></div>
			<?php endif; ?>
		</div>
		<?php
	}
}
