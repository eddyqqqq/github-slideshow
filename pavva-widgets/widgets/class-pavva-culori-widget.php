<?php
/**
 * Pavva Widgets – Culori și finisaje
 *
 * Imaginea mare a culorii (cu numele ei și săgeți) + grila de thumbnails.
 * Citește câmpul „Culori și finisaje” al produsului curent.
 *
 * @package Pavva_Widgets
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Utils;

class Pavva_Culori_Widget extends Widget_Base {

	public function get_name() {
		return 'pavva_culori_finisaje';
	}

	public function get_title() {
		return 'Pavva – Culori și finisaje';
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return array( 'pavva' );
	}

	public function get_keywords() {
		return array( 'culori', 'finisaje', 'produs', 'galerie', 'thumbnails', 'pavva' );
	}

	public function get_style_depends() {
		return array( 'pavva-culori', 'pavva-sectiuni-goale' );
	}

	public function get_script_depends() {
		return array( 'pavva-culori', 'pavva-sectiuni-goale' );
	}

	/* Conținutul depinde de produsul paginii: Elementor nu are voie să-l țină în cache. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/* Fără culori: widgetul primește o clasă care ascunde containerul (vezi sectiuni-goale.css). */
	protected function get_html_wrapper_class(): string {
		$class = parent::get_html_wrapper_class();
		if ( ! $this->is_edit_mode() && ! $this->colors_for_page() ) {
			$class .= ' pavva-w-is-empty';
		}
		return $class;
	}

	/** @var array|null Culorile produsului paginii (calculate o singură dată). */
	private $colors_cache = null;

	private function colors_for_page() {
		if ( null === $this->colors_cache ) {
			$pid                = (int) Pavva_W_Data::resolve_product_id();
			$this->colors_cache = $pid ? $this->get_colors( $pid ) : array();
		}
		return $this->colors_cache;
	}

	/* Prefix comun pentru toate selectorii: bate stilurile globale ale temei. */
	private function sel( $inner = '' ) {
		return '{{WRAPPER}} .pvc' . ( $inner ? ' ' . $inner : '' );
	}

	protected function register_controls() {
		$this->controls_content();
		$this->controls_style_heading();
		$this->controls_style_layout();
		$this->controls_style_stage();
		$this->controls_style_name();
		$this->controls_style_arrows();
		$this->controls_style_thumbs();
		$this->controls_style_fx();
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
				'description'  => 'Poți pune titlul și ca widget Heading separat, în același container. Dacă produsul nu are culori, containerul se ascunde cu tot cu titlu.',
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
				'default'     => 'Culori și finisaje',
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

		/* ---------- Culori ---------- */
		$this->start_controls_section(
			'section_colors',
			array(
				'label' => 'Culori',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'colors_info',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'Culorile se iau din produs: <strong>Produse → editare produs → Culori și finisaje</strong> (denumire, thumbnail, imagine mare).',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_responsive_control(
			'layout',
			array(
				'label'                => 'Așezare',
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'side',
				'tablet_default'       => 'side',
				'mobile_default'       => 'stack',
				'options'              => array(
					'side'  => 'Thumbnails în dreapta',
					'stack' => 'Thumbnails sub imagine',
				),
				'selectors_dictionary' => array(
					'side'  => 'minmax(0, calc(var(--pvc-main) - var(--pvc-gap) / 2)) minmax(0, 1fr)',
					'stack' => 'minmax(0, 1fr)',
				),
				'selectors'            => array(
					$this->sel( '.pvc-layout' ) => 'grid-template-columns: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'main_width',
			array(
				'label'       => 'Lățime imagine mare (%)',
				'description' => 'Doar când thumbnails sunt în dreapta.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 25, 'max' => 80 ) ),
				'default'     => array( 'unit' => '%', 'size' => 50 ),
				'selectors'   => array(
					$this->sel() => '--pvc-main: {{SIZE}}%;',
				),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'       => 'Coloane thumbnails',
				'description' => '„Din produs” folosește setarea din produs (3 sau 4 coloane).',
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array(
					''  => 'Din produs',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'selectors'   => array(
					$this->sel() => '--pvc-cols: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_size',
			array(
				'label'     => 'Dimensiune imagine mare',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'large',
				'options'   => $this->image_size_options(),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'thumb_size',
			array(
				'label'   => 'Dimensiune thumbnails',
				'type'    => Controls_Manager::SELECT,
				'default' => 'medium',
				'options' => $this->image_size_options(),
			)
		);

		$this->add_control(
			'show_name',
			array(
				'label'        => 'Numele culorii pe imagine',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'show_arrows',
			array(
				'label'        => 'Săgeți pe imagine',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_thumb_labels',
			array(
				'label'        => 'Nume sub thumbnails',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'trigger',
			array(
				'label'   => 'Schimbă imaginea la',
				'type'    => Controls_Manager::SELECT,
				'default' => 'click',
				'options' => array(
					'click' => 'Click pe thumbnail',
					'hover' => 'Hover pe thumbnail (pe telefon: atingere)',
				),
			)
		);

		$this->add_control(
			'arrow_type',
			array(
				'label'     => 'Tip săgeți',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'default',
				'options'   => array(
					'default' => 'Implicite (subțiri, din design)',
					'icon'    => 'Iconițe din bibliotecă / Upload SVG',
					'upload'  => 'Imagine sau SVG din Media',
				),
				'separator' => 'before',
				'condition' => array( 'show_arrows' => 'yes' ),
			)
		);

		$this->add_control(
			'prev_icon',
			array(
				'label'       => 'Săgeată anterior',
				'description' => 'Alege din bibliotecă sau „Upload SVG”.',
				'type'        => Controls_Manager::ICONS,
				'default'     => array( 'value' => 'fas fa-chevron-left', 'library' => 'fa-solid' ),
				'condition'   => array( 'show_arrows' => 'yes', 'arrow_type' => 'icon' ),
			)
		);

		$this->add_control(
			'next_icon',
			array(
				'label'     => 'Săgeată următor',
				'type'      => Controls_Manager::ICONS,
				'default'   => array( 'value' => 'fas fa-chevron-right', 'library' => 'fa-solid' ),
				'condition' => array( 'show_arrows' => 'yes', 'arrow_type' => 'icon' ),
			)
		);

		$this->add_control(
			'prev_upload',
			array(
				'label'       => 'Săgeată anterior (imagine / SVG)',
				'description' => 'SVG-ul se pune direct în pagină, deci îi poți schimba culoarea din Style → Săgeți.',
				'type'        => Controls_Manager::MEDIA,
				'media_types' => array( 'image', 'svg' ),
				'condition'   => array( 'show_arrows' => 'yes', 'arrow_type' => 'upload' ),
			)
		);

		$this->add_control(
			'next_upload',
			array(
				'label'       => 'Săgeată următor (imagine / SVG)',
				'type'        => Controls_Manager::MEDIA,
				'media_types' => array( 'image', 'svg' ),
				'condition'   => array( 'show_arrows' => 'yes', 'arrow_type' => 'upload' ),
			)
		);

		$this->end_controls_section();

		/* ---------- Animație ---------- */
		$this->start_controls_section(
			'section_animation',
			array(
				'label' => 'Animație și interacțiune',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'animation_mode',
			array(
				'label'   => 'Tip animație',
				'type'    => Controls_Manager::SELECT,
				'default' => 'slide',
				'options' => array(
					'slide' => 'Alunecare',
					'fade'  => 'Estompare',
					'none'  => 'Fără',
				),
			)
		);

		$this->add_control(
			'animation_duration',
			array(
				'label'     => 'Durată (ms)',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 3000,
				'step'      => 50,
				'default'   => 600,
				'condition' => array( 'animation_mode!' => 'none' ),
				'selectors' => array(
					$this->sel() => '--pvc-speed: {{VALUE}}ms;',
				),
			)
		);

		$this->add_control(
			'animation_easing',
			array(
				'label'     => 'Easing',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cubic-bezier(.22,.61,.36,1)',
				'options'   => array(
					'cubic-bezier(.22,.61,.36,1)' => 'Lin (implicit)',
					'cubic-bezier(.4,0,.2,1)'     => 'Material',
					'cubic-bezier(.65,0,.35,1)'   => 'Lin la ambele capete',
					'ease'                        => 'Ease',
					'ease-in-out'                 => 'Ease in / out',
					'linear'                      => 'Linear',
				),
				'condition' => array( 'animation_mode!' => 'none' ),
				'selectors' => array(
					$this->sel() => '--pvc-ease: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'swipe',
			array(
				'label'        => 'Swipe pe telefon / tabletă',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'drag',
			array(
				'label'        => 'Tragere cu mouse-ul',
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
	 * STYLE: titlu
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
					$this->sel( '.pvc-heading' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'heading_typo',
				'selector' => $this->sel( '.pvc-heading' ),
			)
		);

		$this->add_responsive_control(
			'heading_align',
			array(
				'label'     => 'Aliniere',
				'type'      => Controls_Manager::CHOOSE,
				'options'   => $this->align_options(),
				'selectors' => array(
					$this->sel( '.pvc-heading' ) => 'text-align: {{VALUE}};',
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
					$this->sel( '.pvc-heading' ) => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name' => 'heading_tshadow',
				'selector' => $this->sel( '.pvc-heading' ),
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * STYLE: așezare
	 * ================================================================ */
	private function controls_style_layout() {
		$this->start_controls_section(
			'section_style_layout',
			array(
				'label' => 'Spațieri',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'       => 'Spațiu imagine – thumbnails',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem', 'vw' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'selectors'   => array(
					$this->sel() => '--pvc-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'thumbs_gap',
			array(
				'label'      => 'Spațiu între thumbnails',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem', 'vw' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors'  => array(
					$this->sel() => '--pvc-tgap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'thumbs_valign',
			array(
				'label' => 'Aliniere verticală thumbnails',
				'type' => Controls_Manager::SELECT,
				'options' => array( '' => 'Implicit (sus)', 'start' => 'Sus', 'center' => 'Centru', 'end' => 'Jos', 'stretch' => 'Întinse' ),
				'selectors' => array(
					$this->sel( '.pvc-layout' ) => 'align-items: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * STYLE: imagine mare
	 * ================================================================ */
	private function controls_style_stage() {
		$this->start_controls_section(
			'section_style_stage',
			array(
				'label' => 'Imagine mare',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'stage_ratio',
			array(
				'label'       => 'Proporție',
				'description' => 'La 4:3 cu thumbnails pătrate pe 4 coloane, imaginea are aceeași înălțime ca 3 rânduri de thumbnails.',
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => $this->ratio_options(),
				'selectors'   => array(
					$this->sel() => '--pvc-ratio: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'stage_radius',
			array(
				'label' => 'Colțuri rotunjite (fiecare colț)',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors' => array(
					$this->sel( '.pvc-stage' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'stage_bg',
			array(
				'label'       => 'Fundal',
				'description' => 'Se vede până se încarcă imaginea.',
				'type'        => Controls_Manager::COLOR,
				'selectors'   => array(
					$this->sel( '.pvc-stage' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'stage_fit',
			array(
				'label'     => 'Încadrare imagine',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => array(
					'cover'   => 'Umple (decupează marginile)',
					'contain' => 'Încape întreagă',
				),
				'selectors' => array(
					$this->sel( '.pvc-slide img' ) => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name' => 'stage_border',
				'label' => 'Bordură',
				'selector' => $this->sel( '.pvc-stage' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name' => 'stage_shadow',
				'label' => 'Umbră',
				'selector' => $this->sel( '.pvc-stage' ),
			)
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name' => 'stage_filter',
				'label' => 'Filtre imagine',
				'selector' => $this->sel( '.pvc-slide img' ),
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * STYLE: numele culorii
	 * ================================================================ */
	private function controls_style_name() {
		$this->start_controls_section(
			'section_style_name',
			array(
				'label'     => 'Numele culorii',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_name' => 'yes' ),
			)
		);

		$this->add_control(
			'name_color',
			array(
				'label'     => 'Culoare text',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvc-name' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'name_typo',
				'selector' => $this->sel( '.pvc-name' ),
			)
		);

		$this->add_responsive_control(
			'name_padding',
			array(
				'label'       => 'Distanță față de margini',
				'type'        => Controls_Manager::DIMENSIONS,
				'size_units'  => array( 'px', 'em', 'rem', '%' ),
				'allowed_dimensions' => array( 'right', 'bottom', 'left' ),
				'selectors'   => array(
					$this->sel( '.pvc-name' ) => 'right: {{RIGHT}}{{UNIT}}; bottom: {{BOTTOM}}{{UNIT}}; left: {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'show_shade',
			array(
				'label'        => 'Umbră sub text',
				'description'  => 'Degradeu închis jos pe imagine, ca numele să se citească pe culorile deschise.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'shade_color',
			array(
				'label'     => 'Culoare umbră',
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'show_shade' => 'yes' ),
				'selectors' => array(
					$this->sel() => '--pvc-shade: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'shade_height',
			array(
				'label' => 'Înălțime umbră',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( '%', 'px' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 100, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvc-shade' ) => 'height: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'show_shade' => 'yes' ),
			)
		);

		$this->add_control(
			'name_box_heading',
			array(
				'label' => 'Casetă nume',
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'name_bg',
			array(
				'label' => 'Fundal casetă',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvc-name' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'name_inner_padding',
			array(
				'label' => 'Padding casetă',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors' => array(
					$this->sel( '.pvc-name' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'name_radius',
			array(
				'label' => 'Colțuri rotunjite (fiecare colț)',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors' => array(
					$this->sel( '.pvc-name' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name' => 'name_border',
				'label' => 'Bordură casetă',
				'selector' => $this->sel( '.pvc-name' ),
			)
		);

		$this->add_responsive_control(
			'name_align',
			array(
				'label' => 'Aliniere text',
				'type' => Controls_Manager::CHOOSE,
				'options' => $this->align_options(),
				'selectors' => array(
					$this->sel( '.pvc-name' ) => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name' => 'name_tshadow',
				'label' => 'Umbră text',
				'selector' => $this->sel( '.pvc-name' ),
			)
		);

		$this->add_control(
			'name_anim',
			array(
				'label' => 'Animație nume la schimbare',
				'type' => Controls_Manager::SELECT,
				'options' => array( 'up' => 'Urcă și apare', 'fade' => 'Apare treptat', 'none' => 'Fără' ),
				'default' => 'up',
				'separator' => 'before',
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * STYLE: săgeți
	 * ================================================================ */
	private function controls_style_arrows() {
		$this->start_controls_section(
			'section_style_arrows',
			array(
				'label'     => 'Săgeți',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_arrows' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'arrow_size',
			array(
				'label'      => 'Mărime',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 10, 'max' => 80 ) ),
				'selectors'  => array(
					$this->sel() => '--pvc-arrow: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'arrow_offset',
			array(
				'label'      => 'Distanță față de margine',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors'  => array(
					$this->sel() => '--pvc-arrow-x: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'arrow_stroke',
			array(
				'label'     => 'Grosime linie',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0.5,
				'max'       => 5,
				'step'      => 0.1,
				'selectors' => array(
					$this->sel( '.pvc-arrow svg path' ) => 'stroke-width: {{VALUE}};',
				),
			)
		);

		$this->start_controls_tabs( 'arrow_tabs' );

		$this->start_controls_tab( 'arrow_tab_normal', array( 'label' => 'Normal' ) );
		$this->add_control(
			'arrow_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvc-arrow' ) => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'arrow_bg',
			array(
				'label'     => 'Fundal',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvc-arrow' ) => 'background-color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'arrow_tab_hover', array( 'label' => 'Hover' ) );
		$this->add_control(
			'arrow_color_hover',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvc-arrow:hover' ) => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'arrow_bg_hover',
			array(
				'label'     => 'Fundal',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvc-arrow:hover' ) => 'background-color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'arrow_padding',
			array(
				'label' => 'Padding',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors' => array(
					$this->sel( '.pvc-arrow' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'arrow_radius',
			array(
				'label' => 'Colțuri rotunjite (fiecare colț)',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors' => array(
					$this->sel( '.pvc-arrow' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name' => 'arrow_border',
				'label' => 'Bordură',
				'selector' => $this->sel( '.pvc-arrow' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name' => 'arrow_shadow',
				'label' => 'Umbră',
				'selector' => $this->sel( '.pvc-arrow' ),
			)
		);

		$this->add_responsive_control(
			'arrow_top',
			array(
				'label' => 'Poziție verticală',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( '%', 'px' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 100, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvc-arrow' ) => 'top: {{SIZE}}{{UNIT}};',
				),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'arrow_on_hover',
			array(
				'label' => 'Săgeți vizibile doar la hover',
				'type' => Controls_Manager::SWITCHER,
				'label_on' => 'Da',
				'label_off' => 'Nu',
				'return_value' => 'yes',
				'prefix_class' => 'pvc-arrows-hover-',
			)
		);

		$this->add_control(
			'arrow_nudge',
			array(
				'label' => 'Deplasare la hover',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 20, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel() => '--pvc-arrow-nudge: {{SIZE}}{{UNIT}};',
				),
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'arrow_img_size',
			array(
				'label' => 'Mărime imagine săgeată',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 8, 'max' => 120, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvc-arrow-img' ) => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'arrow_type' => 'upload' ),
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * STYLE: thumbnails
	 * ================================================================ */
	private function controls_style_thumbs() {
		$this->start_controls_section(
			'section_style_thumbs',
			array(
				'label' => 'Thumbnails',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'thumb_ratio',
			array(
				'label'     => 'Proporție',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => $this->ratio_options(),
				'selectors' => array(
					$this->sel() => '--pvc-tratio: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'thumb_radius',
			array(
				'label' => 'Colțuri rotunjite (fiecare colț)',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors' => array(
					$this->sel( '.pvc-thumb-media' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					$this->sel( '.pvc-thumb-fx' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'thumb_border_width',
			array(
				'label'      => 'Grosime chenar',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 6, 'step' => 0.5 ) ),
				'selectors'  => array(
					$this->sel() => '--pvc-tbw: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'thumb_tabs' );

		$this->start_controls_tab( 'thumb_tab_normal', array( 'label' => 'Normal' ) );
		$this->add_control(
			'thumb_border',
			array(
				'label'     => 'Culoare chenar',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--pvc-tbc: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'thumb_opacity',
			array(
				'label'     => 'Opacitate',
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0.2, 'max' => 1, 'step' => 0.05 ) ),
				'selectors' => array(
					$this->sel( '.pvc-thumb' ) => '--pvc-top: {{SIZE}};',
				),
			)
		);
		$this->add_control(
			'thumb_bg',
			array(
				'label' => 'Fundal',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvc-thumb-media' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name' => 'thumb_shadow',
				'label' => 'Umbră',
				'selector' => $this->sel( '.pvc-thumb-media' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'thumb_tab_hover', array( 'label' => 'Hover' ) );
		$this->add_control(
			'thumb_border_hover',
			array(
				'label'     => 'Culoare chenar',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--pvc-tbc-h: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'thumb_opacity_hover',
			array(
				'label'     => 'Opacitate',
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0.2, 'max' => 1, 'step' => 0.05 ) ),
				'selectors' => array(
					$this->sel( '.pvc-thumb:hover' ) => '--pvc-top: {{SIZE}};',
				),
			)
		);
		$this->add_control(
			'thumb_zoom',
			array(
				'label' => 'Zoom imagine',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 1, 'max' => 1.3, 'step' => 0.01 ) ),
				'selectors' => array(
					$this->sel( '.pvc-thumb:hover .pvc-thumb-media img' ) => 'transform: scale({{SIZE}});',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name' => 'thumb_shadow_hover',
				'label' => 'Umbră',
				'selector' => $this->sel( '.pvc-thumb:hover .pvc-thumb-media' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'thumb_tab_active', array( 'label' => 'Activ' ) );
		$this->add_control(
			'thumb_border_active',
			array(
				'label'     => 'Culoare chenar',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--pvc-tbc-a: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'thumb_border_width_active',
			array(
				'label' => 'Grosime chenar',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 8, 'step' => 0.5 ) ),
				'selectors' => array(
					$this->sel() => '--pvc-tbw-a: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'thumb_opacity_active',
			array(
				'label'     => 'Opacitate',
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0.2, 'max' => 1, 'step' => 0.05 ) ),
				'selectors' => array(
					$this->sel( '.pvc-thumb.is-active' ) => '--pvc-top: {{SIZE}};',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name' => 'thumb_shadow_active',
				'label' => 'Umbră',
				'selector' => $this->sel( '.pvc-thumb.is-active .pvc-thumb-media' ),
			)
		);

		$this->add_control(
			'thumb_label_color_active',
			array(
				'label' => 'Culoare nume (activ)',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvc-thumb.is-active .pvc-thumb-label' ) => 'color: {{VALUE}};',
				),
				'condition' => array( 'show_thumb_labels' => 'yes' ),
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_control(
			'thumb_label_heading',
			array(
				'label'     => 'Nume sub thumbnails',
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array( 'show_thumb_labels' => 'yes' ),
			)
		);

		$this->add_control(
			'thumb_label_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'show_thumb_labels' => 'yes' ),
				'selectors' => array(
					$this->sel( '.pvc-thumb-label' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'thumb_label_typo',
				'selector'  => $this->sel( '.pvc-thumb-label' ),
				'condition' => array( 'show_thumb_labels' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'thumb_label_spacing',
			array(
				'label' => 'Spațiu deasupra numelui',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 40, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvc-thumb-label' ) => 'margin-top: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'show_thumb_labels' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'thumb_label_align',
			array(
				'label' => 'Aliniere nume',
				'type' => Controls_Manager::CHOOSE,
				'options' => $this->align_options(),
				'selectors' => array(
					$this->sel( '.pvc-thumb-label' ) => 'text-align: {{VALUE}};',
				),
				'condition' => array( 'show_thumb_labels' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/* ================================================================
	 * STYLE: efect la selectare
	 * ================================================================ */
	private function controls_style_fx() {
		$this->start_controls_section(
			'section_style_fx',
			array(
				'label' => 'Efect la selectarea culorii',
				'tab' => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'active_fx',
			array(
				'label' => 'Efect',
				'type' => Controls_Manager::SELECT,
				'options' => array( 'border' => 'Chenar (ca în design)', 'ring' => 'Contur cu spațiu (inel)', 'lift' => 'Ridicare + umbră', 'underline' => 'Linie sub thumbnail', 'check' => 'Bifă în colț', 'corner' => 'Colț colorat', 'none' => 'Fără efect' ),
				'default' => 'border',
			)
		);

		$this->add_control(
			'fx_color',
			array(
				'label' => 'Culoare accent',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--pvc-accent: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'fx_speed',
			array(
				'label' => 'Durata efectului (ms)',
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 2000,
				'step' => 50,
				'selectors' => array(
					$this->sel() => '--pvc-fx-speed: {{VALUE}}ms;',
				),
			)
		);

		$this->add_responsive_control(
			'fx_ring_gap',
			array(
				'label' => 'Spațiu până la inel',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 12, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel() => '--pvc-ring-gap: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'active_fx' => 'ring' ),
			)
		);

		$this->add_control(
			'fx_ring_bg',
			array(
				'label' => 'Culoare spațiu (fundalul paginii)',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--pvc-ring-bg: {{VALUE}};',
				),
				'condition' => array( 'active_fx' => 'ring' ),
			)
		);

		$this->add_responsive_control(
			'fx_lift',
			array(
				'label' => 'Cât se ridică',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 20, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel() => '--pvc-lift: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'active_fx' => 'lift' ),
			)
		);

		$this->add_control(
			'fx_lift_shadow',
			array(
				'label' => 'Culoare umbră',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--pvc-lift-shadow: {{VALUE}};',
				),
				'condition' => array( 'active_fx' => 'lift' ),
			)
		);

		$this->add_responsive_control(
			'fx_bar_h',
			array(
				'label' => 'Grosime linie',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 1, 'max' => 10, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel() => '--pvc-bar-h: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'active_fx' => 'underline' ),
			)
		);

		$this->add_responsive_control(
			'fx_bar_gap',
			array(
				'label' => 'Spațiu deasupra liniei',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 24, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel() => '--pvc-bar-gap: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'active_fx' => 'underline' ),
			)
		);

		$this->add_responsive_control(
			'fx_bar_w',
			array(
				'label' => 'Lățime linie',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range' => array( 'px' => array( 'min' => 10, 'max' => 100, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel() => '--pvc-bar-w: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'active_fx' => 'underline' ),
			)
		);

		$this->add_responsive_control(
			'fx_check',
			array(
				'label' => 'Mărime bifă',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 12, 'max' => 48, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel() => '--pvc-check: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'active_fx' => 'check' ),
			)
		);

		$this->add_control(
			'fx_check_bg',
			array(
				'label' => 'Fundal bifă',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--pvc-check-bg: {{VALUE}};',
				),
				'condition' => array( 'active_fx' => 'check' ),
			)
		);

		$this->add_control(
			'fx_check_ink',
			array(
				'label' => 'Culoare bifă',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--pvc-check-ink: {{VALUE}};',
				),
				'condition' => array( 'active_fx' => 'check' ),
			)
		);

		$this->add_responsive_control(
			'fx_corner',
			array(
				'label' => 'Mărime colț',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 8, 'max' => 60, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel() => '--pvc-corner: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'active_fx' => 'corner' ),
			)
		);

		$this->add_control(
			'fx_others_heading',
			array(
				'label' => 'Celelalte culori',
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'fx_dim',
			array(
				'label' => 'Estompează culorile neselectate',
				'type' => Controls_Manager::SWITCHER,
				'label_on' => 'Da',
				'label_off' => 'Nu',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'fx_dim_opacity',
			array(
				'label' => 'Opacitate neselectate',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 0.1, 'max' => 1, 'step' => 0.05 ) ),
				'selectors' => array(
					$this->sel() => '--pvc-dim: {{SIZE}};',
				),
				'condition' => array( 'fx_dim' => 'yes' ),
			)
		);

		$this->add_control(
			'fx_dim_gray',
			array(
				'label' => 'Alb-negru neselectate (%)',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 100, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel() => '--pvc-dim-gray: {{SIZE}}%;',
				),
				'condition' => array( 'fx_dim' => 'yes' ),
			)
		);

		$this->add_control(
			'fx_zoom_heading',
			array(
				'label' => 'Culoarea selectată',
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'fx_zoom',
			array(
				'label' => 'Mărește ușor imaginea selectată',
				'type' => Controls_Manager::SWITCHER,
				'label_on' => 'Da',
				'label_off' => 'Nu',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'fx_zoom_amount',
			array(
				'label' => 'Cât se mărește',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 1, 'max' => 1.3, 'step' => 0.01 ) ),
				'selectors' => array(
					$this->sel() => '--pvc-zoom-a: {{SIZE}};',
				),
				'condition' => array( 'fx_zoom' => 'yes' ),
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

	private function ratio_options() {
		return array(
			''     => 'Implicit',
			'1/1'  => '1:1 (pătrat)',
			'5/4'  => '5:4',
			'4/3'  => '4:3',
			'3/2'  => '3:2',
			'16/9' => '16:9',
			'3/4'  => '3:4 (portret)',
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

	/* Mesaj doar în editor; pe site widgetul gol nu afișează nimic. */
	private function editor_notice( $text ) {
		if ( ! $this->is_edit_mode() ) {
			return;
		}
		echo '<div class="pavva-editor-notice" style="padding:16px 18px;border:1px dashed #c9c4bb;border-radius:6px;background:#faf8f5;color:#6b6964;font-size:13px;line-height:1.5;">' . wp_kses_post( $text ) . '</div>';
	}

	private function arrow_html( $s, $dir ) {
		$type = isset( $s['arrow_type'] ) ? $s['arrow_type'] : 'default';

		if ( 'upload' === $type ) {
			$media = isset( $s[ $dir . '_upload' ] ) ? $s[ $dir . '_upload' ] : array();
			$url   = ! empty( $media['url'] ) ? (string) $media['url'] : '';
			if ( '' !== $url ) {
				$path = wp_parse_url( $url, PHP_URL_PATH );
				if ( $path && 'svg' === strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) ) {
					ob_start();
					Icons_Manager::render_icon(
						array(
							'library' => 'svg',
							'value'   => array( 'id' => ! empty( $media['id'] ) ? (int) $media['id'] : 0, 'url' => $url ),
						),
						array( 'aria-hidden' => 'true' )
					);
					$inline = trim( ob_get_clean() );
					if ( '' !== $inline && false !== stripos( $inline, '<svg' ) ) {
						return $inline;
					}
				}
				return '<img class="pvc-arrow-img" src="' . esc_url( $url ) . '" alt="" draggable="false">';
			}
			return $this->chevron_svg( $dir );
		}

		$icon = 'icon' === $type && isset( $s[ $dir . '_icon' ] ) ? $s[ $dir . '_icon' ] : array();
		if ( is_array( $icon ) && ! empty( $icon['value'] ) ) {
			ob_start();
			Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) );
			$html = trim( ob_get_clean() );
			if ( '' !== $html ) {
				return $html;
			}
		}
		return $this->chevron_svg( $dir );
	}

	/* Fără date: în editor un mesaj, pe site un marcaj invizibil (ascunde containerul). */
	private function empty_output( $notice ) {
		if ( $this->is_edit_mode() ) {
			$this->editor_notice( $notice );
			return;
		}
		echo '<span class="pavva-w-empty" hidden></span>';
	}

	private function chevron_svg( $dir ) {
		$d = 'prev' === $dir ? 'M15 4.5 7.5 12l7.5 7.5' : 'M9 4.5 16.5 12 9 19.5';
		return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="' . $d . '" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}

	/* Culorile vizibile ale produsului, cu imaginile completate reciproc. */
	private function get_colors( $product_id ) {
		$out = array();
		foreach ( Pavva_W_Data::items( $product_id, Pavva_W_Data::META_CULORI ) as $item ) {
			if ( ! is_array( $item ) || ! Pavva_W_Data::item_is_visible( $item ) ) {
				continue;
			}
			$thumb = ! empty( $item['thumbnail_id'] ) ? absint( $item['thumbnail_id'] ) : 0;
			$large = ! empty( $item['imagine_mare_id'] ) ? absint( $item['imagine_mare_id'] ) : 0;
			if ( ! $thumb && ! $large ) {
				continue;
			}
			$out[] = array(
				'name'  => isset( $item['denumire'] ) ? (string) $item['denumire'] : '',
				'thumb' => $thumb ? $thumb : $large,
				'large' => $large ? $large : $thumb,
			);
		}
		return $out;
	}

	/* ================================================================
	 * RENDER
	 * ================================================================ */
	protected function render() {
		$s          = $this->get_settings_for_display();
		$product_id = (int) Pavva_W_Data::resolve_product_id();

		if ( ! $product_id ) {
			$this->empty_output( '<strong>Culori și finisaje:</strong> nu găsesc produsul. În template, alege un produs la <em>rotița Setări → Preview Settings</em>, apoi Apply &amp; Preview.' );
			return;
		}

		$colors = $this->colors_for_page();
		if ( ! $colors ) {
			$this->empty_output( '<strong>Culori și finisaje:</strong> produsul acesta nu are culori adăugate. Pe site containerul acestei secțiuni se ascunde.' );
			return;
		}

		$auto_cols = absint( get_post_meta( $product_id, Pavva_W_Data::META_CULORI_COLS, true ) );
		if ( ! in_array( $auto_cols, array( 3, 4 ), true ) ) {
			$auto_cols = 3;
		}

		$heading_tag = Utils::validate_html_tag( ! empty( $s['heading_tag'] ) ? $s['heading_tag'] : 'h2' );
		$large_size  = $this->valid_size( isset( $s['image_size'] ) ? $s['image_size'] : '', 'large' );
		$thumb_size  = $this->valid_size( isset( $s['thumb_size'] ) ? $s['thumb_size'] : '', 'medium' );
		$show_name   = isset( $s['show_name'] ) && 'yes' === $s['show_name'];
		$show_shade  = $show_name && isset( $s['show_shade'] ) && 'yes' === $s['show_shade'];
		$show_arrows = isset( $s['show_arrows'] ) && 'yes' === $s['show_arrows'] && count( $colors ) > 1;
		$show_labels = isset( $s['show_thumb_labels'] ) && 'yes' === $s['show_thumb_labels'];
		$heading     = isset( $s['heading_text'] ) ? (string) $s['heading_text'] : '';
		$label       = '' !== trim( $heading ) ? $heading : 'Culori și finisaje';
		$mode        = isset( $s['animation_mode'] ) && in_array( $s['animation_mode'], array( 'slide', 'fade', 'none' ), true ) ? $s['animation_mode'] : 'slide';
		$options     = array(
			'trigger' => ( isset( $s['trigger'] ) && 'hover' === $s['trigger'] ) ? 'hover' : 'click',
			'mode'    => $mode,
			'swipe'   => isset( $s['swipe'] ) && 'yes' === $s['swipe'],
			'drag'    => isset( $s['drag'] ) && 'yes' === $s['drag'],
		);
		$uid         = 'pvc-' . $this->get_id();
		$fx          = isset( $s['active_fx'] ) && in_array( $s['active_fx'], array( 'border', 'ring', 'lift', 'underline', 'check', 'corner', 'none' ), true ) ? $s['active_fx'] : 'border';
		$name_anim   = isset( $s['name_anim'] ) && in_array( $s['name_anim'], array( 'up', 'fade', 'none' ), true ) ? $s['name_anim'] : 'up';
		$root_class  = 'pvc pvc-mode-' . $mode . ' pvc-fx-' . $fx . ' pvc-name-' . $name_anim
			. ( isset( $s['fx_dim'] ) && 'yes' === $s['fx_dim'] ? ' pvc-dim' : '' )
			. ( isset( $s['fx_zoom'] ) && 'yes' === $s['fx_zoom'] ? ' pvc-zoom-active' : '' );
		?>
		<div class="<?php echo esc_attr( $root_class ); ?>" data-pvc="<?php echo esc_attr( wp_json_encode( $options ) ); ?>" style="--pvc-cols-auto: <?php echo (int) $auto_cols; ?>;">
			<?php if ( isset( $s['show_heading'] ) && 'yes' === $s['show_heading'] && '' !== trim( $heading ) ) : ?>
				<<?php echo $heading_tag; // phpcs:ignore ?> class="pvc-heading"><?php echo esc_html( $heading ); ?></<?php echo $heading_tag; // phpcs:ignore ?>>
			<?php endif; ?>

			<div class="pvc-layout">
				<div class="pvc-stage" tabindex="0" role="region" aria-roledescription="galerie" aria-label="<?php echo esc_attr( $label ); ?>">
					<?php foreach ( $colors as $i => $c ) : ?>
						<figure class="pvc-slide<?php echo 0 === $i ? ' is-active' : ''; ?>" id="<?php echo esc_attr( $uid . '-' . $i ); ?>"<?php echo 0 === $i ? '' : ' aria-hidden="true"'; ?>>
							<?php
							echo wp_get_attachment_image( // phpcs:ignore -- HTML generat de WordPress
								$c['large'],
								$large_size,
								false,
								array(
									'alt'       => $c['name'],
									'loading'   => 0 === $i ? 'eager' : 'lazy',
									'decoding'  => 'async',
									'draggable' => 'false',
									'sizes'     => '(max-width: 767px) 100vw, (max-width: 1400px) 50vw, 700px',
								)
							);
							?>
							<?php if ( $show_shade ) : ?>
								<span class="pvc-shade" aria-hidden="true"></span>
							<?php endif; ?>
							<?php if ( $show_name && '' !== $c['name'] ) : ?>
								<figcaption class="pvc-name"><?php echo esc_html( $c['name'] ); ?></figcaption>
							<?php endif; ?>
						</figure>
					<?php endforeach; ?>

					<?php if ( $show_arrows ) : ?>
						<button type="button" class="pvc-arrow pvc-prev" aria-label="Culoarea anterioară"><?php echo $this->arrow_html( $s, 'prev' ); // phpcs:ignore ?></button>
						<button type="button" class="pvc-arrow pvc-next" aria-label="Culoarea următoare"><?php echo $this->arrow_html( $s, 'next' ); // phpcs:ignore ?></button>
					<?php endif; ?>
				</div>

				<?php if ( count( $colors ) > 1 || $show_labels ) : ?>
					<div class="pvc-thumbs">
						<?php foreach ( $colors as $i => $c ) : ?>
							<button type="button" class="pvc-thumb<?php echo 0 === $i ? ' is-active' : ''; ?>" data-index="<?php echo (int) $i; ?>" aria-controls="<?php echo esc_attr( $uid . '-' . $i ); ?>" aria-pressed="<?php echo 0 === $i ? 'true' : 'false'; ?>"<?php echo $show_labels ? '' : ' aria-label="' . esc_attr( '' !== $c['name'] ? $c['name'] : 'Culoarea ' . ( $i + 1 ) ) . '"'; ?>>
								<span class="pvc-thumb-frame">
								<span class="pvc-thumb-media">
									<?php
									echo wp_get_attachment_image( // phpcs:ignore -- HTML generat de WordPress
										$c['thumb'],
										$thumb_size,
										false,
										array(
											'alt'       => '',
											'loading'   => 'lazy',
											'decoding'  => 'async',
											'draggable' => 'false',
											'sizes'     => '(max-width: 767px) 25vw, 160px',
										)
									);
									?>
								</span>
								<span class="pvc-thumb-fx" aria-hidden="true"></span>
								</span>
								<?php if ( $show_labels && '' !== $c['name'] ) : ?>
									<span class="pvc-thumb-label"><?php echo esc_html( $c['name'] ); ?></span>
								<?php endif; ?>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
