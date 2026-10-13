<?php
/**
 * Pavva Widgets – Grilă produse
 *
 * Produsele unei categorii (sau alese manual) în carduri: imagine, nume,
 * „Descriere sinteză”. Cardul arată ca în Caruselul de produse.
 *
 * @package Pavva_Widgets
 * @author  Imagient <https://imagient.ro/>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Text_Shadow;
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
		return array( 'pavva-grila-produse', 'pavva-sectiuni-goale' );
	}

	public function get_script_depends() {
		return array( 'pavva-sectiuni-goale' );
	}

	/* Conținutul depinde de categoria paginii: Elementor nu are voie să-l țină în cache. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/* Niciun produs (și fără text de rezervă): widgetul primește o clasă care ascunde containerul. */
	protected function get_html_wrapper_class(): string {
		$class = parent::get_html_wrapper_class();
		if ( ! $this->is_edit_mode() ) {
			$s = $this->get_settings_for_display();
			list( $ids ) = $this->ids_for_page( $s );
			if ( ! $ids && '' === trim( (string) ( isset( $s['empty_text'] ) ? $s['empty_text'] : '' ) ) ) {
				$class .= ' pavva-w-is-empty';
			}
		}
		return $class;
	}

	/** @var array|null Rezultatul interogării (calculat o singură dată). */
	private $ids_cache = null;

	private function ids_for_page( $s ) {
		if ( null === $this->ids_cache ) {
			$this->ids_cache = $this->query_ids( $s );
		}
		return $this->ids_cache;
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
		$this->controls_style_boxes();
		$this->controls_style_texts();
		$this->controls_style_colors();
		$this->controls_style_card_hover();
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
			'show_colors',
			array(
				'label'        => 'Culori (thumbnails)',
				'description'  => 'Primele culori din „Culori și finisaje” ale produsului, apoi +N pentru restul.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'max_colors',
			array(
				'label'     => 'Număr maxim culori',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 24,
				'default'   => 6,
				'condition' => array( 'show_colors' => 'yes' ),
			)
		);

		$this->add_control(
			'show_counter',
			array(
				'label'        => 'Afișează +N',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'show_colors' => 'yes' ),
			)
		);

		$this->add_control(
			'counter_format',
			array(
				'label' => 'Text contor',
				'type' => Controls_Manager::TEXT,
				'description' => '{n} = câte culori mai sunt. Ex.: +{n} sau +{n} culori.',
				'default' => '+{n}',
				'condition' => array( 'show_colors' => 'yes', 'show_counter' => 'yes' ),
			)
		);

		$this->add_control(
			'counter_style',
			array(
				'label' => 'Aspect contor',
				'type' => Controls_Manager::SELECT,
				'options' => array( 'text' => 'Text lângă culori', 'box' => 'Pătrățel, ca o culoare' ),
				'default' => 'text',
				'condition' => array( 'show_colors' => 'yes', 'show_counter' => 'yes' ),
			)
		);

		$this->add_control(
			'colors_position',
			array(
				'label' => 'Poziție culori',
				'type' => Controls_Manager::SELECT,
				'options' => array( 'bottom' => 'Sub descriere', 'top' => 'Deasupra numelui', 'image' => 'Peste imagine (jos)' ),
				'default' => 'bottom',
				'condition' => array( 'show_colors' => 'yes' ),
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

		$this->add_control(
			'arrow_type',
			array(
				'label' => 'Tip săgeată',
				'type' => Controls_Manager::SELECT,
				'options' => array( '' => 'Implicită (sau iconița aleasă mai jos)', 'icon' => 'Iconiță din bibliotecă / Upload SVG', 'upload' => 'Imagine sau SVG din Media' ),
				'default' => '',
				'condition' => array( 'show_arrow' => 'yes' ),
			)
		);

		$this->add_control(
			'arrow_icon',
			array(
				'label' => 'Iconiță săgeată',
				'type' => Controls_Manager::ICONS,
				'description' => 'Din bibliotecă sau „Upload SVG”.',
				'condition' => array( 'show_arrow' => 'yes', 'arrow_type' => array( '', 'icon' ) ),
			)
		);

		$this->add_control(
			'arrow_upload',
			array(
				'label' => 'Săgeată (imagine / SVG)',
				'type' => Controls_Manager::MEDIA,
				'description' => 'Apare exact la mărimea fișierului. SVG-ul se pune direct în pagină și își schimbă culoarea la hover.',
				'media_types' => array( 'image', 'svg' ),
				'condition' => array( 'show_arrow' => 'yes', 'arrow_type' => 'upload' ),
			)
		);

		$this->add_control(
			'arrow_recolor',
			array(
				'label' => 'Colorează săgeata (SVG)',
				'type' => Controls_Manager::SWITCHER,
				'description' => 'Culoarea săgeții și cea de hover se aplică peste culorile din fișierul SVG (inclusiv „Upload SVG”).',
				'label_on' => 'Da',
				'label_off' => 'Nu',
				'return_value' => 'yes',
				'default' => 'yes',
				'condition' => array( 'show_arrow' => 'yes' ),
			)
		);

		$this->end_controls_section();

		/* ---------- Lista de ofertă: butonul „+” pe carduri ---------- */
		$this->start_controls_section(
			'section_oferta',
			array(
				'label' => 'Buton ofertă pe carduri',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'oferta_show',
			array(
				'label'        => 'Buton „Adaugă la ofertă”',
				'description'  => 'Un buton mic pe poza fiecărui produs: îl pune în lista de ofertă fără să deschidă pagina produsului. Al doilea click îl scoate.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
			)
		);
		$this->add_control(
			'oferta_pos',
			array(
				'label'     => 'Poziție',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'right',
				'options'   => array( 'right' => 'Dreapta sus', 'left' => 'Stânga sus' ),
				'condition' => array( 'oferta_show' => 'yes' ),
			)
		);
		$this->add_control(
			'oferta_text',
			array(
				'label'       => 'Text lângă iconiță',
				'description' => 'Gol = doar iconița (textul „Adaugă la ofertă” rămâne pentru cititoarele de ecran).',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'condition'   => array( 'oferta_show' => 'yes' ),
			)
		);
		$this->add_control(
			'oferta_text_added',
			array(
				'label'     => 'Text după adăugare',
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'condition' => array( 'oferta_show' => 'yes', 'oferta_text!' => '' ),
			)
		);
		$this->add_control(
			'oferta_toast',
			array(
				'label'        => 'Mesaj după adăugare',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'oferta_show' => 'yes' ),
			)
		);
		$this->add_control(
			'oferta_form',
			array(
				'label'       => 'Pagina formularului',
				'type'        => Controls_Manager::TEXT,
				'default'     => '/solicita-oferta/',
				'label_block' => true,
				'condition'   => array( 'oferta_show' => 'yes' ),
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
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors'   => array(
					$this->sel( '.pvg-body' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'body_inset',
			array(
				'label' => 'Spațiu stânga / dreapta (toate elementele)',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem', 'em', '%' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 80, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-body' ) => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'body_top',
			array(
				'label' => 'Spațiu sub imagine',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem', 'em' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 100, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-body' ) => 'padding-top: {{SIZE}}{{UNIT}};',
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
				'size_units' => array( 'px', 'rem', 'em' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'selectors'  => array(
					$this->sel() => '--pvg-lift: {{SIZE}}{{UNIT}};',
				),
			)
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'body_align',
			array(
				'label' => 'Aliniere text',
				'type' => Controls_Manager::CHOOSE,
				'options' => array( 'left' => array( 'title' => 'Stânga', 'icon' => 'eicon-text-align-left' ), 'center' => array( 'title' => 'Centru', 'icon' => 'eicon-text-align-center' ), 'right' => array( 'title' => 'Dreapta', 'icon' => 'eicon-text-align-right' ) ),
				'selectors' => array(
					$this->sel( '.pvg-body' ) => 'text-align: {{VALUE}};',
				),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'body_bg',
			array(
				'label' => 'Fundal zonă text',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-body' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'card_speed',
			array(
				'label' => 'Durata efectelor hover (ms)',
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 3000,
				'step' => 50,
				'selectors' => array(
					$this->sel() => '--pvg-speed: {{VALUE}}ms;',
				),
			)
		);

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
				'label' => 'Colțuri rotunjite (fiecare colț)',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors' => array(
					$this->sel( '.pvg-media' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
			'image_fx',
			array(
				'label'        => 'Efect imagine la hover',
				'type'         => Controls_Manager::SELECT,
				'default'      => 'zoom',
				'options'      => array(
					'zoom'     => 'Zoom (mărire)',
					'zoom-out' => 'Zoom invers (micșorare)',
					'pan'      => 'Glisare ușoară',
					'rotate'   => 'Zoom cu rotire ușoară',
					'lift'     => 'Ridicare imagine',
					'darken'   => 'Întunecare',
					'lighten'  => 'Luminare',
					'gray'     => 'Alb-negru → color',
					'shine'    => 'Reflexie (lumină care trece)',
					'none'     => 'Fără efect',
				),
				'prefix_class' => 'pvg-imgfx-',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'image_overlay',
			array(
				'label'     => 'Culoare efect',
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'image_fx' => array( 'darken', 'lighten', 'shine' ) ),
				'selectors' => array(
					$this->sel() => '--pvg-overlay: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_fx_speed',
			array(
				'label'     => 'Durata efect (ms)',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 3000,
				'step'      => 50,
				'condition' => array( 'image_fx!' => 'none' ),
				'selectors' => array(
					$this->sel() => '--pvg-speed-zoom: {{VALUE}}ms;',
				),
			)
		);

		$this->add_control(
			'image_zoom',
			array(
				'label'     => 'Intensitate zoom',
				'condition' => array( 'image_fx' => array( 'zoom', 'zoom-out', 'pan', 'rotate' ) ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 1, 'max' => 1.3, 'step' => 0.01 ) ),
				'selectors' => array(
					$this->sel() => '--pvg-zoom: {{SIZE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name' => 'image_border',
				'label' => 'Bordură',
				'selector' => $this->sel( '.pvg-media' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name' => 'image_shadow',
				'label' => 'Umbră',
				'selector' => $this->sel( '.pvg-media' ),
			)
		);

		$this->start_controls_tabs( 'image_filter_tabs' );

		$this->start_controls_tab( 'image_filter_normal', array( 'label' => 'Normal' ) );

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name' => 'image_filter',
				'label' => 'Filtre',
				'selector' => $this->sel( '.pvg-media img' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'image_filter_hover', array( 'label' => 'Hover' ) );

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name' => 'image_filter_hover',
				'label' => 'Filtre',
				'selector' => $this->sel( '.pvg-card:hover .pvg-media img' ),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

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

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name' => 'title_tshadow',
				'label' => 'Umbră text',
				'selector' => $this->sel( '.pvg-title' ),
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
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'condition'  => array( 'show_desc' => 'yes' ),
				'selectors'  => array(
					$this->sel( '.pvg-desc' ) => 'margin-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'desc_lines',
			array(
				'label' => 'Descriere: număr maxim de rânduri',
				'type' => Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 12,
				'description' => 'Gol = textul întreg.',
				'selectors' => array(
					$this->sel( '.pvg-desc' ) => 'display: -webkit-box; -webkit-line-clamp: {{VALUE}}; -webkit-box-orient: vertical; overflow: hidden;',
				),
				'condition' => array( 'show_desc' => 'yes' ),
			)
		);

		$this->add_control(
			'texts_layout_heading',
			array(
				'label' => 'Lățimi și spațieri',
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'title_max_width',
			array(
				'label' => 'Lățime maximă nume',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'ch', 'rem', 'em' ),
				'range' => array( 'px' => array( 'min' => 40, 'max' => 800, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-title' ) => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'desc_max_width',
			array(
				'label' => 'Lățime maximă descriere',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'ch', 'rem', 'em' ),
				'range' => array( 'px' => array( 'min' => 40, 'max' => 800, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-desc' ) => 'max-width: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'show_desc' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'body_max_width',
			array(
				'label' => 'Lățime maximă zonă text',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'rem', 'em' ),
				'range' => array( 'px' => array( 'min' => 100, 'max' => 1000, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-body' ) => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'title_margin',
			array(
				'label' => 'Margini nume',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%', 'rem' ),
				'selectors' => array(
					$this->sel( '.pvg-row' ) => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'desc_margin',
			array(
				'label' => 'Margini descriere',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%', 'rem' ),
				'selectors' => array(
					$this->sel( '.pvg-desc' ) => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition' => array( 'show_desc' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_boxes() {
		$this->start_controls_section(
			'section_style_boxes',
			array(
				'label' => 'Containere (nume / descriere / culori)',
				'tab' => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'boxes_info',
			array(
				'label' => '',
				'type' => Controls_Manager::RAW_HTML,
				'raw' => 'Fiecare element are containerul lui. „Spațiu stânga / dreapta” și „Distanța între elemente” se aplică la toate, ca să rămână aliniate; apoi poți ajusta fiecare container separat.',
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_responsive_control(
			'boxes_inset',
			array(
				'label' => 'Spațiu stânga / dreapta (toate containerele)',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem', 'em', '%' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 80, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-box' ) => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'boxes_gap',
			array(
				'label' => 'Distanța între elemente',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem', 'em' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 80, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-body' ) => 'row-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'boxes_align',
			array(
				'label' => 'Aliniere conținut',
				'type' => Controls_Manager::CHOOSE,
				'options' => array( 'left' => array( 'title' => 'Stânga', 'icon' => 'eicon-text-align-left' ), 'center' => array( 'title' => 'Centru', 'icon' => 'eicon-text-align-center' ), 'right' => array( 'title' => 'Dreapta', 'icon' => 'eicon-text-align-right' ) ),
				'selectors' => array(
					$this->sel( '.pvg-box' ) => 'text-align: {{VALUE}};',
					$this->sel( '.pvg-colors' ) => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'box_head_heading',
			array(
				'label' => 'Container nume + săgeată',
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'box_head_padding',
			array(
				'label' => 'Padding',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors' => array(
					$this->sel( '.pvg-head' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'box_head_margin',
			array(
				'label' => 'Margini',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'rem', 'em', '%' ),
				'selectors' => array(
					$this->sel( '.pvg-head' ) => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'box_head_maxw',
			array(
				'label' => 'Lățime maximă',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem', 'em', '%', 'ch' ),
				'range' => array( 'px' => array( 'min' => 40, 'max' => 1000, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-head' ) => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'box_head_bg',
			array(
				'label' => 'Fundal',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-head' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'box_head_radius',
			array(
				'label' => 'Colțuri rotunjite (fiecare colț)',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors' => array(
					$this->sel( '.pvg-head' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name' => 'box_head_border',
				'label' => 'Bordură',
				'selector' => $this->sel( '.pvg-head' ),
			)
		);

		$this->add_control(
			'box_text_heading',
			array(
				'label' => 'Container descriere',
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'box_text_padding',
			array(
				'label' => 'Padding',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors' => array(
					$this->sel( '.pvg-text' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'box_text_margin',
			array(
				'label' => 'Margini',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'rem', 'em', '%' ),
				'selectors' => array(
					$this->sel( '.pvg-text' ) => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'box_text_maxw',
			array(
				'label' => 'Lățime maximă',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem', 'em', '%', 'ch' ),
				'range' => array( 'px' => array( 'min' => 40, 'max' => 1000, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-text' ) => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'box_text_bg',
			array(
				'label' => 'Fundal',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-text' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'box_text_radius',
			array(
				'label' => 'Colțuri rotunjite (fiecare colț)',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors' => array(
					$this->sel( '.pvg-text' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name' => 'box_text_border',
				'label' => 'Bordură',
				'selector' => $this->sel( '.pvg-text' ),
			)
		);

		$this->add_control(
			'box_sw_heading',
			array(
				'label' => 'Container culori',
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'box_sw_padding',
			array(
				'label' => 'Padding',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors' => array(
					$this->sel( '.pvg-swatches' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'box_sw_margin',
			array(
				'label' => 'Margini',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'rem', 'em', '%' ),
				'selectors' => array(
					$this->sel( '.pvg-swatches' ) => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'box_sw_maxw',
			array(
				'label' => 'Lățime maximă',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem', 'em', '%', 'ch' ),
				'range' => array( 'px' => array( 'min' => 40, 'max' => 1000, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-swatches' ) => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'box_sw_bg',
			array(
				'label' => 'Fundal',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-swatches' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'box_sw_radius',
			array(
				'label' => 'Colțuri rotunjite (fiecare colț)',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors' => array(
					$this->sel( '.pvg-swatches' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name' => 'box_sw_border',
				'label' => 'Bordură',
				'selector' => $this->sel( '.pvg-swatches' ),
			)
		);

		$this->end_controls_section();

	}

	private function controls_style_card_hover() {
		$this->start_controls_section(
			'section_style_card_hover',
			array(
				'label' => 'Hover pe card',
				'tab' => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'hv_title',
			array(
				'label' => 'Culoare nume',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-card:hover .pvg-title' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'hv_desc',
			array(
				'label' => 'Culoare descriere',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-card:hover .pvg-desc' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'hv_arrow',
			array(
				'label' => 'Culoare săgeată',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-card:hover .pvg-arrow' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'hv_counter_heading',
			array(
				'label' => '+N',
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'hv_counter',
			array(
				'label' => 'Culoare +N',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-card:hover .pvg-colors-more' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'hv_counter_bg',
			array(
				'label' => 'Fundal +N',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-card:hover .pvg-colors-more' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'hv_counter_border',
			array(
				'label' => 'Culoare bordură +N',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-card:hover .pvg-colors-more' ) => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'hv_sw_heading',
			array(
				'label' => 'Culori (thumbnails)',
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'hv_sw_border',
			array(
				'label' => 'Culoare bordură culori',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-card:hover .pvg-color' ) => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'hv_sw_opacity',
			array(
				'label' => 'Opacitate culori',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 0.2, 'max' => 1, 'step' => 0.05 ) ),
				'selectors' => array(
					$this->sel( '.pvg-card:hover .pvg-color' ) => 'opacity: {{SIZE}};',
				),
			)
		);

		$this->add_control(
			'hv_boxes_heading',
			array(
				'label' => 'Containere',
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'hv_head_bg',
			array(
				'label' => 'Fundal container nume',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-card:hover .pvg-head' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'hv_text_bg',
			array(
				'label' => 'Fundal container descriere',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-card:hover .pvg-text' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'hv_sw_bg',
			array(
				'label' => 'Fundal container culori',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-card:hover .pvg-swatches' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

	}

	private function controls_style_colors() {
		$this->start_controls_section(
			'section_style_colors',
			array(
				'label'     => 'Culori',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_colors' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'colors_size',
			array(
				'label' => 'Mărime thumbnail (lățime = înălțime)',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range' => array( 'px' => array( 'min' => 8, 'max' => 160, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel() => '--pvg-color: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'colors_width',
			array(
				'label' => 'Lățime (opțional)',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range' => array( 'px' => array( 'min' => 8, 'max' => 200, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel() => '--pvg-color-w: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'colors_height',
			array(
				'label' => 'Înălțime (opțional)',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range' => array( 'px' => array( 'min' => 8, 'max' => 200, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel() => '--pvg-color-h: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'colors_gap',
			array(
				'label'      => 'Spațiu între thumbnails',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem', 'em' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'selectors'  => array(
					$this->sel( '.pvg-colors' ) => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'colors_radius',
			array(
				'label' => 'Colțuri rotunjite (fiecare colț)',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors' => array(
					$this->sel( '.pvg-color, {{WRAPPER}} .pvg .pvg-colors-more.is-box' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'colors_spacing',
			array(
				'label'      => 'Spațiu deasupra',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array(
					$this->sel( '.pvg-swatches' ) => 'padding-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'colors_align_bottom',
			array(
				'label' => 'Aliniază culorile jos (la fel în toate cardurile)',
				'type' => Controls_Manager::SWITCHER,
				'description' => 'Culorile stau pe aceeași linie în toate cardurile, oricât de lungă e descrierea.',
				'label_on' => 'Da',
				'label_off' => 'Nu',
				'return_value' => 'yes',
				'default' => 'yes',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name' => 'colors_border',
				'label' => 'Bordură culoare',
				'selector' => $this->sel( '.pvg-color' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name' => 'colors_shadow',
				'label' => 'Umbră culoare',
				'selector' => $this->sel( '.pvg-color' ),
			)
		);

		$this->add_control(
			'colors_hover_scale',
			array(
				'label' => 'Mărire culoare la hover',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 1, 'max' => 1.5, 'step' => 0.01 ) ),
				'selectors' => array(
					$this->sel( '.pvg-color:hover' ) => 'transform: scale({{SIZE}});',
				),
			)
		);

		$this->add_responsive_control(
			'colors_justify',
			array(
				'label' => 'Aliniere',
				'type' => Controls_Manager::CHOOSE,
				'options' => array( 'flex-start' => array( 'title' => 'Stânga', 'icon' => 'eicon-h-align-left' ), 'center' => array( 'title' => 'Centru', 'icon' => 'eicon-h-align-center' ), 'flex-end' => array( 'title' => 'Dreapta', 'icon' => 'eicon-h-align-right' ) ),
				'selectors' => array(
					$this->sel( '.pvg-colors' ) => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'counter_color',
			array(
				'label'     => 'Culoare +N',
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'condition' => array( 'show_counter' => 'yes' ),
				'selectors' => array(
					$this->sel( '.pvg-colors-more' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'counter_typo',
				'label'     => 'Tipografie +N',
				'selector'  => $this->sel( '.pvg-colors-more' ),
				'condition' => array( 'show_counter' => 'yes' ),
			)
		);

		$this->add_control(
			'counter_bg',
			array(
				'label' => 'Fundal +N',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-colors-more' ) => 'background-color: {{VALUE}};',
				),
				'condition' => array( 'show_counter' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'counter_padding',
			array(
				'label' => 'Padding +N',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors' => array(
					$this->sel( '.pvg-colors-more' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition' => array( 'show_counter' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'counter_radius',
			array(
				'label' => 'Colțuri rotunjite +N',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors' => array(
					$this->sel( '.pvg-colors-more' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition' => array( 'show_counter' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name' => 'counter_border',
				'label' => 'Bordură +N',
				'selector' => $this->sel( '.pvg-colors-more' ),
				'condition' => array( 'show_counter' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'counter_spacing',
			array(
				'label' => 'Spațiu înainte de +N',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem', 'em' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 30, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-colors-more' ) => 'margin-left: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'show_counter' => 'yes' ),
			)
		);

		$this->add_control(
			'colors_overlay_heading',
			array(
				'label' => 'Culori peste imagine',
				'type' => Controls_Manager::HEADING,
				'condition' => array( 'colors_position' => 'image' ),
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'colors_overlay_left',
			array(
				'label' => 'Distanță stânga',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'rem', 'em' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 100, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-media .pvg-colors' ) => 'left: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'colors_position' => 'image' ),
			)
		);

		$this->add_responsive_control(
			'colors_overlay_bottom',
			array(
				'label' => 'Distanță jos',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'rem', 'em' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 100, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-media .pvg-colors' ) => 'bottom: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'colors_position' => 'image' ),
			)
		);

		$this->add_control(
			'colors_overlay_bg',
			array(
				'label' => 'Fundal bandă culori',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-media .pvg-colors' ) => 'background-color: {{VALUE}};',
				),
				'condition' => array( 'colors_position' => 'image' ),
			)
		);

		$this->add_responsive_control(
			'colors_overlay_padding',
			array(
				'label' => 'Padding bandă culori',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors' => array(
					$this->sel( '.pvg-media .pvg-colors' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition' => array( 'colors_position' => 'image' ),
			)
		);

		$this->add_responsive_control(
			'colors_overlay_radius',
			array(
				'label' => 'Colțuri bandă culori',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors' => array(
					$this->sel( '.pvg-media .pvg-colors' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition' => array( 'colors_position' => 'image' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_style_arrow() {
		$this->start_controls_section(
			'section_style_arrow',
			array(
				'label' => 'Săgeată',
				'tab' => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'arrow_info',
			array(
				'label' => '',
				'type' => Controls_Manager::RAW_HTML,
				'raw' => 'Săgeata nu mai împinge textul: are mărimea fișierului încărcat. Setează lățimea doar dacă vrei altă mărime (înălțimea urmează proporția).',
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_responsive_control(
			'arrow_width',
			array(
				'label' => 'Lățime săgeată',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range' => array( 'px' => array( 'min' => 6, 'max' => 240, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-arrow' ) => '--pvg-arrow-w: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'arrow_size',
			array(
				'label' => 'Mărime iconiță (doar bibliotecă)',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem', 'em' ),
				'range' => array( 'px' => array( 'min' => 8, 'max' => 80, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-arrow' ) => 'font-size: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'arrow_type' => array( '', 'icon' ) ),
			)
		);

		$this->start_controls_tabs( 'arrow_tabs' );

		$this->start_controls_tab( 'arrow_tab_normal', array( 'label' => 'Normal' ) );

		$this->add_control(
			'arrow_color',
			array(
				'label' => 'Culoare',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-arrow' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'arrow_tab_hover', array( 'label' => 'Hover pe card' ) );

		$this->add_control(
			'arrow_color_hover',
			array(
				'label' => 'Culoare',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvg-card:hover .pvg-arrow' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_dir',
			array(
				'label' => 'Direcția mișcării',
				'type' => Controls_Manager::SELECT,
				'options' => array( '-1' => 'Spre stânga (spre text)', '1' => 'Spre dreapta', '0' => 'Fără mișcare' ),
				'default' => '-1',
				'selectors' => array(
					$this->sel() => '--pvg-arrow-dir: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_shift',
			array(
				'label' => 'Distanța mișcării',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem', 'em' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 40, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel() => '--pvg-arrow-shift: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'arrow_y',
			array(
				'label' => 'Ajustare verticală',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem', 'em' ),
				'range' => array( 'px' => array( 'min' => -30, 'max' => 30, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-arrow' ) => '--pvg-arrow-y: {{SIZE}}{{UNIT}};',
				),
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'arrow_gap',
			array(
				'label' => 'Spațiu minim față de nume',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem', 'em' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 80, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvg-row' ) => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		/* ---------- Stil: butonul de ofertă de pe carduri ---------- */
		$this->start_controls_section(
			'section_style_oferta',
			array(
				'label'     => 'Buton ofertă',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'oferta_show' => 'yes' ),
			)
		);
		$this->add_responsive_control(
			'oferta_size',
			array(
				'label'      => 'Înălțime',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 24, 'max' => 72 ) ),
				'selectors'  => array( '{{WRAPPER}} .pvg-oferta' => '--pvg-of-size: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'oferta_icon_size',
			array(
				'label'      => 'Mărime iconiță',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .pvg-oferta svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'oferta_offset',
			array(
				'label'      => 'Distanța față de margine',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .pvg-oferta' => '--pvg-of-off: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'oferta_radius',
			array(
				'label'      => 'Colțuri rotunjite',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( '{{WRAPPER}} .pvg-oferta' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'oferta_typo', 'label' => 'Font text', 'selector' => '{{WRAPPER}} .pvg-oferta .pvl-t', 'condition' => array( 'oferta_text!' => '' ) ) );
		$this->start_controls_tabs( 'tabs_oferta' );
		foreach ( array( 'normal' => array( 'Normal', '' ), 'hover' => array( 'Hover', ':hover' ), 'added' => array( 'Adăugat', '.is-added' ) ) as $k => $t ) {
			$this->start_controls_tab( 'tab_oferta_' . $k, array( 'label' => $t[0] ) );
			$sel = '{{WRAPPER}} .pvg-oferta';
			$sfx = array( 'normal' => '', 'hover' => '-h', 'added' => '-a' )[ $k ];
			$this->add_control( 'oferta_color_' . $k, array( 'label' => 'Iconiță / text', 'type' => Controls_Manager::COLOR, 'selectors' => array( $sel => '--pvg-of-c' . $sfx . ': {{VALUE}};' ) ) );
			$this->add_control( 'oferta_bg_' . $k, array( 'label' => 'Fundal', 'type' => Controls_Manager::COLOR, 'selectors' => array( $sel => '--pvg-of-bg' . $sfx . ': {{VALUE}};' ) ) );
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();
		$this->end_controls_section();

	}

	/* ================================================================
	 * Ajutătoare
	 * ================================================================ */

	/* butonul „+” al listei de ofertă (în afara linkului cardului: un buton nu are voie în interiorul unui link) */
	private function oferta_button( $id, $s, $form ) {
		$item = Pavva_W_Lista::item( (int) $id );
		if ( ! $item ) return;
		$text  = trim( (string) ( $s['oferta_text'] ?? '' ) );
		$added = trim( (string) ( $s['oferta_text_added'] ?? '' ) );
		$pos   = 'left' === ( $s['oferta_pos'] ?? 'right' ) ? 'left' : 'right';
		$plus  = '<svg class="pvl-ico-add" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" fill="none"/></svg>';
		$check = '<svg class="pvl-ico-added" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12.5l4.5 4.5L19 7.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>';
		printf(
			'<button type="button" class="pvg-oferta pvg-oferta-%1$s%2$s" aria-pressed="false" data-pvl-add data-pvl="%3$s" data-pvl-mode="add" data-pvl-again="remove" data-pvl-form="%4$s" data-pvl-toast="%5$s">%6$s%7$s%8$s</button>',
			esc_attr( $pos ),
			'' !== $text ? ' has-text' : '',
			esc_attr( wp_json_encode( $item ) ),
			esc_url( $form ),
			'yes' === ( $s['oferta_toast'] ?? 'yes' ) ? '1' : '0',
			$plus . $check, // phpcs:ignore -- SVG fix
			'' !== $text
				? '<span class="pvl-t pvl-t-add">' . esc_html( $text ) . '</span><span class="pvl-t pvl-t-added">' . esc_html( '' !== $added ? $added : $text ) . '</span>'
				: '<span class="pvl-sr">' . esc_html( 'Adaugă la ofertă: ' . $item['name'] ) . '</span>', /* starea o spune aria-pressed */
			''
		);
	}
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

	private function arrow_html( $s ) {
		$type = isset( $s['arrow_type'] ) ? $s['arrow_type'] : '';
		if ( 'upload' === $type && ! empty( $s['arrow_upload']['url'] ) ) {
			$url  = (string) $s['arrow_upload']['url'];
			$path = wp_parse_url( $url, PHP_URL_PATH );
			if ( $path && 'svg' === strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) ) {
				ob_start();
				Icons_Manager::render_icon(
					array(
						'library' => 'svg',
						'value'   => array( 'id' => ! empty( $s['arrow_upload']['id'] ) ? (int) $s['arrow_upload']['id'] : 0, 'url' => $url ),
					),
					array( 'aria-hidden' => 'true' )
				);
				$svg = trim( ob_get_clean() );
				if ( '' !== $svg && false !== stripos( $svg, '<svg' ) ) {
					return '<span class="pvg-arrow-upload">' . $svg . '</span>';
				}
			}
			return '<img class="pvg-arrow-img" src="' . esc_url( $url ) . '" alt="" draggable="false">';
		}
		/* „Implicită” folosește totuși iconița, dacă ai ales una (setările existente rămân) */
		if ( in_array( $type, array( '', 'icon' ), true ) && ! empty( $s['arrow_icon']['value'] ) ) {
			ob_start();
			Icons_Manager::render_icon( $s['arrow_icon'], array( 'aria-hidden' => 'true' ) );
			$html = trim( ob_get_clean() );
			if ( '' !== $html ) {
				return $html;
			}
		}
		return $this->arrow_svg();
	}

	private function arrow_svg() {
		/* săgeata lungă și subțire din design */
		return '<svg class="pvg-arrow-default" width="46" height="12" viewBox="0 0 46 12" aria-hidden="true" focusable="false"><path d="M1 6h43M39 1.5 44.5 6 39 10.5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
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

	/* Thumbnails culori pentru card: primele $max, apoi +N (ca „Culori card” din Pavva Products). */
	private function colors_html( $id, $max, $counter, $format = '+{n}', $box = false ) {
		$items = Pavva_W_Data::items( $id, Pavva_W_Data::META_CULORI );
		$list  = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || ! Pavva_W_Data::item_is_visible( $item ) ) {
				continue;
			}
			$thumb = ! empty( $item['thumbnail_id'] ) ? absint( $item['thumbnail_id'] ) : ( ! empty( $item['imagine_mare_id'] ) ? absint( $item['imagine_mare_id'] ) : 0 );
			if ( $thumb ) {
				$list[] = array(
					'id'   => $thumb,
					'name' => isset( $item['denumire'] ) ? (string) $item['denumire'] : '',
				);
			}
		}
		if ( ! $list ) {
			return '';
		}
		$max   = max( 1, (int) $max );
		$shown = array_slice( $list, 0, $max );
		$rest  = count( $list ) - count( $shown );

		$html = '<div class="pvg-colors">';
		foreach ( $shown as $c ) {
			$html .= '<span class="pvg-color" title="' . esc_attr( $c['name'] ) . '">'
				. wp_get_attachment_image(
					$c['id'],
					'thumbnail',
					false,
					array(
						'alt'      => $c['name'],
						'loading'  => 'lazy',
						'decoding' => 'async',
					)
				)
				. '</span>';
		}
		if ( $counter && $rest > 0 ) {
			$label = str_replace( '{n}', (string) (int) $rest, '' !== trim( (string) $format ) ? (string) $format : '+{n}' );
			$html .= '<span class="pvg-colors-more' . ( $box ? ' is-box' : '' ) . '" aria-label="' . esc_attr( 'încă ' . $rest . ' culori' ) . '">' . esc_html( $label ) . '</span>';
		}
		return $html . '</div>';
	}

	/* ================================================================
	 * RENDER
	 * ================================================================ */
	protected function render() {
		$s = $this->get_settings_for_display();

		list( $ids, $preview_all ) = $this->ids_for_page( $s );

		$heading_tag = Utils::validate_html_tag( ! empty( $s['heading_tag'] ) ? $s['heading_tag'] : 'h2' );
		$title_tag   = Utils::validate_html_tag( ! empty( $s['title_tag'] ) ? $s['title_tag'] : 'h3' );
		$size        = $this->valid_size( isset( $s['image_size'] ) ? $s['image_size'] : '', 'large' );
		$show_desc   = isset( $s['show_desc'] ) && 'yes' === $s['show_desc'];
		$show_arrow  = isset( $s['show_arrow'] ) && 'yes' === $s['show_arrow'];
		$words       = isset( $s['desc_words'] ) ? absint( $s['desc_words'] ) : 0;
		$heading     = isset( $s['heading_text'] ) ? (string) $s['heading_text'] : '';
		$empty_text  = isset( $s['empty_text'] ) ? trim( (string) $s['empty_text'] ) : '';
		$show_colors = isset( $s['show_colors'] ) && 'yes' === $s['show_colors'];
		$max_colors  = isset( $s['max_colors'] ) ? absint( $s['max_colors'] ) : 6;
		$counter     = isset( $s['show_counter'] ) && 'yes' === $s['show_counter'];
		$c_format    = isset( $s['counter_format'] ) ? (string) $s['counter_format'] : '+{n}';
		$c_box       = isset( $s['counter_style'] ) && 'box' === $s['counter_style'];
		$c_pos       = isset( $s['colors_position'] ) && in_array( $s['colors_position'], array( 'bottom', 'top', 'image' ), true ) ? $s['colors_position'] : 'bottom';
		$arrow_html  = $this->arrow_html( $s );
		$oferta      = isset( $s['oferta_show'] ) && 'yes' === $s['oferta_show'];
		$oferta_form = $oferta && class_exists( 'Pavva_W_Lista' ) ? Pavva_W_Lista::resolve_url( $s['oferta_form'] ?? '' ) : '';

		if ( ! $ids ) {
			if ( '' !== $empty_text ) {
				echo '<div class="pvg"><p class="pvg-empty">' . esc_html( $empty_text ) . '</p></div>';
			} elseif ( $this->is_edit_mode() ) {
				$this->editor_notice( '<strong>Grilă produse:</strong> nu am găsit produse pentru setările alese. Pe site containerul acestei secțiuni se ascunde.' );
			} else {
				echo '<span class="pavva-w-empty" hidden></span>';
			}
			return;
		}

		if ( $preview_all ) {
			$this->editor_notice( '<strong>Grilă produse – previzualizare:</strong> nu știu categoria paginii, așa că arăt câteva produse ca exemplu. Pentru categoria reală, alege una la <em>rotița Setări → Preview Settings</em>. Pe site apar produsele categoriei.' );
		}
		?>
		<div class="pvg<?php echo ( ! isset( $s['arrow_recolor'] ) || 'yes' === $s['arrow_recolor'] ) ? ' pvg-recolor' : ''; ?><?php echo ( ! isset( $s['colors_align_bottom'] ) || 'yes' === $s['colors_align_bottom'] ) ? ' pvg-colors-bottom' : ''; ?>">
			<?php if ( isset( $s['show_heading'] ) && 'yes' === $s['show_heading'] && '' !== trim( $heading ) ) : ?>
				<<?php echo $heading_tag; // phpcs:ignore ?> class="pvg-heading"><?php echo esc_html( $heading ); ?></<?php echo $heading_tag; // phpcs:ignore ?>>
			<?php endif; ?>

			<ul class="pvg-grid">
				<?php
				foreach ( $ids as $id ) :
					$name  = get_the_title( $id );
					$thumb = get_post_thumbnail_id( $id );
					$desc  = $show_desc ? $this->product_desc( $id, $words ) : '';
					$cols  = $show_colors ? $this->colors_html( $id, $max_colors, $counter, $c_format, $c_box ) : '';
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
											'alt'      => wp_strip_all_tags( $name ),
											'loading'  => 'lazy',
											'decoding' => 'async',
											'sizes'    => '(max-width: 767px) 100vw, (max-width: 1024px) 50vw, 33vw',
										)
									);
								}
								if ( 'image' === $c_pos ) {
									echo $cols; // phpcs:ignore -- generat și escapat în colors_html()
								}
								?>
							</div>
							<div class="pvg-body">
								<?php if ( 'top' === $c_pos && '' !== $cols ) : ?>
									<div class="pvg-box pvg-swatches pvg-swatches-top"><?php echo $cols; // phpcs:ignore -- generat și escapat în colors_html() ?></div>
								<?php endif; ?>
								<div class="pvg-box pvg-head">
								<div class="pvg-row">
									<<?php echo $title_tag; // phpcs:ignore ?> class="pvg-title"><?php echo esc_html( wp_strip_all_tags( $name ) ); ?></<?php echo $title_tag; // phpcs:ignore ?>>
									<?php if ( $show_arrow ) : ?>
										<span class="pvg-arrow" aria-hidden="true"><?php echo $arrow_html; // phpcs:ignore -- generat în arrow_html() ?></span>
									<?php endif; ?>
								</div>
								</div>
								<?php if ( '' !== $desc ) : ?>
									<div class="pvg-box pvg-text"><p class="pvg-desc"><?php echo esc_html( $desc ); ?></p></div>
								<?php endif; ?>
								<?php if ( 'bottom' === $c_pos && '' !== $cols ) : ?>
									<div class="pvg-box pvg-swatches"><?php echo $cols; // phpcs:ignore -- generat și escapat în colors_html() ?></div>
								<?php endif; ?>
							</div>
						</a>
						<?php
						if ( $oferta && class_exists( 'Pavva_W_Lista' ) ) {
							$this->oferta_button( $id, $s, $oferta_form );
						}
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}
