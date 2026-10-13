<?php
/**
 * Pavva Widgets – Oferta ta
 *
 * Iconița pentru header: arată câte produse are lista de ofertă și duce la formular.
 * Numărul îl pune assets/oferta-lista.js (pagina rămâne aceeași pentru toți, deci merge și din cache).
 *
 * @package Pavva_Widgets
 * @author  Imagient <https://imagient.ro/>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

class Pavva_Oferta_Contor_Widget extends Widget_Base {

	public function get_name() { return 'pavva_oferta_contor'; }
	public function get_title() { return 'Pavva – Oferta ta'; }
	public function get_icon() { return 'eicon-cart-medium'; }
	public function get_categories() { return array( 'pavva' ); }
	public function get_keywords() { return array( 'oferta', 'ofertă', 'lista', 'cos', 'coș', 'header', 'contor', 'pavva' ); }

	private function is_edit_mode() {
		return class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	private function sel( $inner = '' ) {
		return '{{WRAPPER}} .pvl-counter' . ( $inner ? ' ' . $inner : '' );
	}

	protected function register_controls() {

		$this->start_controls_section( 'section_content', array( 'label' => 'Oferta ta' ) );

		$this->add_control(
			'icon',
			array(
				'label'   => 'Iconiță',
				'type'    => Controls_Manager::ICONS,
				'default' => array( 'value' => 'fas fa-clipboard-list', 'library' => 'fa-solid' ),
			)
		);
		$this->add_control(
			'text',
			array(
				'label'       => 'Text',
				'description' => 'Gol = doar iconița. Cititoarele de ecran primesc oricum „Oferta ta – 3 produse”.',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Oferta ta',
				'dynamic'     => array( 'active' => true ),
			)
		);
		$this->add_control(
			'show_count',
			array(
				'label'        => 'Numărul de produse',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
		$this->add_control(
			'hide_empty',
			array(
				'label'        => 'Ascuns cât lista e goală',
				'description'  => 'Nu = iconița se vede mereu (fără număr cât lista e goală). În editor se vede mereu.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
			)
		);
		$this->add_control(
			'form_url',
			array(
				'label'       => 'Duce la',
				'type'        => Controls_Manager::TEXT,
				'default'     => '/solicita-oferta/',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->end_controls_section();

		/* ---------- Panoul lateral cu produsele ---------- */
		/* previzualizare în editor: bara și panoul apar direct pe pagină, ca să le poți stiliza pe loc */
		$this->start_controls_section( 'section_preview', array( 'label' => 'Previzualizare în editor' ) );
		$this->add_control(
			'preview_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'Doar în editorul Elementor: arată bara de jos și panoul cu produse (cu produse reale ca exemplu), ca să vezi pe loc ce schimbi în Style. Pe site nu apar decât când vizitatorul are produse în listă.',
				'content_classes' => 'elementor-descriptor',
			)
		);
		$this->add_control(
			'preview_bar',
			array(
				'label'        => 'Arată bara de jos',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
			)
		);
		$this->add_control(
			'preview_drawer',
			array(
				'label'        => 'Arată panoul cu produse',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
			)
		);
		$this->add_control(
			'preview_empty',
			array(
				'label'        => 'Panoul cu lista goală',
				'description'  => 'Ca să stilizezi textul și butonul „Vezi produsele”.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array( 'preview_drawer' => 'yes' ),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section( 'section_drawer', array( 'label' => 'Panoul cu produse' ) );
		$this->add_control(
			'drawer',
			array(
				'label'        => 'La click deschide panoul',
				'description'  => 'Un panou lateral cu produsele alese și butonul „Solicită ofertă” (ca un coș). Nu = iconița duce direct la formular. Se aplică și linkului din meniu și barei de jos.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
		$this->add_control(
			'drawer_side',
			array(
				'label'     => 'Intră din',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'right',
				'options'   => array( 'right' => 'Dreapta', 'left' => 'Stânga' ),
				'condition' => array( 'drawer' => 'yes' ),
			)
		);
		$this->add_control(
			'drawer_auto',
			array(
				'label'        => 'Se deschide singur la „Adaugă la ofertă”',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array( 'drawer' => 'yes' ),
			)
		);
		foreach ( array(
			'dw_title' => array( 'Titlu', 'Oferta ta' ),
			'dw_cta'   => array( 'Buton mare', 'Solicită ofertă' ),
			'dw_more'  => array( 'Link sub buton', 'Continuă să alegi' ),
			'dw_empty' => array( 'Text când lista e goală', 'Nu ai adăugat încă niciun produs. Apasă „Adaugă la ofertă” pe produsele care te interesează.' ),
			'dw_browse' => array( 'Buton când lista e goală', 'Vezi produsele' ),
		) as $id => $t ) {
			$this->add_control(
				$id,
				array(
					'label'       => $t[0],
					'type'        => 'dw_empty' === $id ? Controls_Manager::TEXTAREA : Controls_Manager::TEXT,
					'default'     => $t[1],
					'label_block' => true,
					'condition'   => array( 'drawer' => 'yes' ),
				)
			);
		}
		$this->add_control(
			'dw_browse_url',
			array(
				'label'       => 'Link buton (listă goală)',
				'description' => 'Gol = butonul doar închide panoul.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'condition'   => array( 'drawer' => 'yes' ),
			)
		);
		$this->end_controls_section();

		/* ---------- Stil ---------- */
		$this->start_controls_section( 'section_style', array( 'label' => 'Oferta ta', 'tab' => Controls_Manager::TAB_STYLE ) );

		$this->add_responsive_control(
			'align',
			array(
				'label'     => 'Aliniere',
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array( 'title' => 'Stânga', 'icon' => 'eicon-text-align-left' ),
					'center'     => array( 'title' => 'Centru', 'icon' => 'eicon-text-align-center' ),
					'flex-end'   => array( 'title' => 'Dreapta', 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array( '{{WRAPPER}} .pvl-counter-wrap' => 'justify-content: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'text_position',
			array(
				'label'     => 'Textul',
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'row',
				'toggle'    => false,
				'options'   => array(
					'row-reverse' => array( 'title' => 'Înaintea iconiței', 'icon' => 'eicon-h-align-left' ),
					'row'         => array( 'title' => 'După iconiță', 'icon' => 'eicon-h-align-right' ),
				),
				'selectors' => array( $this->sel() => 'flex-direction: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'gap',
			array(
				'label'      => 'Distanța iconiță – text',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( $this->sel() => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'padding',
			array(
				'label'      => 'Spațiu interior',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( $this->sel() => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'border', 'selector' => $this->sel() ) );
		$this->add_responsive_control(
			'radius',
			array(
				'label'      => 'Colțuri rotunjite',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( $this->sel() => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->start_controls_tabs( 'tabs_colors' );
		foreach ( array( 'normal' => array( 'Normal', '' ), 'hover' => array( 'Hover', ':hover' ) ) as $k => $t ) {
			$this->start_controls_tab( 'tab_' . $k, array( 'label' => $t[0] ) );
			$s = '{{WRAPPER}} .pvl-counter' . $t[1];
			$this->add_control( 'icon_color_' . $k, array( 'label' => 'Iconiță', 'type' => Controls_Manager::COLOR, 'selectors' => array( $s . ' .pvl-c-ico' => 'color: {{VALUE}};' ) ) );
			$this->add_control( 'text_color_' . $k, array( 'label' => 'Text', 'type' => Controls_Manager::COLOR, 'selectors' => array( $s . ' .pvl-c-text' => 'color: {{VALUE}};' ) ) );
			$this->add_control( 'bg_' . $k, array( 'label' => 'Fundal', 'type' => Controls_Manager::COLOR, 'selectors' => array( $s => 'background-color: {{VALUE}};' ) ) );
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'icon_size',
			array(
				'label'      => 'Mărime iconiță',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 10, 'max' => 80 ) ),
				'separator'  => 'before',
				'selectors'  => array( $this->sel( '.pvl-c-ico' ) => 'font-size: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'text_typo', 'label' => 'Font text', 'selector' => $this->sel( '.pvl-c-text' ) ) );

		$this->add_control( 'badge_heading', array( 'label' => 'Numărul', 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => array( 'show_count' => 'yes' ) ) );
		$this->add_control( 'badge_bg', array( 'label' => 'Fundal', 'type' => Controls_Manager::COLOR, 'condition' => array( 'show_count' => 'yes' ), 'selectors' => array( $this->sel( '.pvl-badge' ) => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'badge_color', array( 'label' => 'Cifră', 'type' => Controls_Manager::COLOR, 'condition' => array( 'show_count' => 'yes' ), 'selectors' => array( $this->sel( '.pvl-badge' ) => 'color: {{VALUE}};' ) ) );
		$this->add_responsive_control(
			'badge_size',
			array(
				'label'      => 'Mărime',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 12, 'max' => 36 ) ),
				'condition'  => array( 'show_count' => 'yes' ),
				'selectors'  => array( $this->sel( '.pvl-badge' ) => 'min-width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; line-height: {{SIZE}}{{UNIT}}; font-size: calc({{SIZE}}{{UNIT}} * .6);' ),
			)
		);
		$this->add_responsive_control(
			'badge_x',
			array(
				'label'      => 'Poziție orizontală',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => -30, 'max' => 30 ) ),
				'condition'  => array( 'show_count' => 'yes' ),
				'selectors'  => array( $this->sel( '.pvl-badge' ) => 'right: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'badge_y',
			array(
				'label'      => 'Poziție verticală',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => -30, 'max' => 30 ) ),
				'condition'  => array( 'show_count' => 'yes' ),
				'selectors'  => array( $this->sel( '.pvl-badge' ) => 'top: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		/* ---------- bara de jos „Oferta ta · Vezi oferta” (e una pe tot site-ul: se stilizează de aici, din header) ---------- */
		$bar = 'body .pvl-toast';
		$this->start_controls_section( 'section_style_bar', array( 'label' => 'Bara de jos (pe tot site-ul)', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'bar_note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => 'Bara apare jos pe ecran cât lista de ofertă are produse. Stilul de aici se aplică pe toate paginile cu acest header.', 'content_classes' => 'elementor-descriptor' ) );
		$this->add_control( 'bar_bg', array( 'label' => 'Fundal', 'type' => Controls_Manager::COLOR, 'selectors' => array( $bar => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'bar_color', array( 'label' => 'Culoare text', 'type' => Controls_Manager::COLOR, 'selectors' => array( $bar => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'bar_typo', 'label' => 'Font', 'selector' => $bar ) );
		$this->add_responsive_control(
			'bar_radius',
			array(
				'label'      => 'Colțuri rotunjite',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( $bar => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'bar_bottom',
			array(
				'label'      => 'Distanța de jos',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors'  => array( $bar => 'bottom: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control( 'bar_btn_heading', array( 'label' => 'Butonul „Vezi oferta”', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'bar_btn_bg', array( 'label' => 'Fundal', 'type' => Controls_Manager::COLOR, 'selectors' => array( 'body .pvl-toast .pvl-toast-a' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'bar_btn_color', array( 'label' => 'Text', 'type' => Controls_Manager::COLOR, 'selectors' => array( 'body .pvl-toast .pvl-toast-a' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'bar_btn_bg_h', array( 'label' => 'Fundal la hover', 'type' => Controls_Manager::COLOR, 'selectors' => array( 'body .pvl-toast .pvl-toast-a:hover, body .pvl-toast .pvl-toast-a:focus-visible' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'bar_btn_color_h', array( 'label' => 'Text la hover', 'type' => Controls_Manager::COLOR, 'selectors' => array( 'body .pvl-toast .pvl-toast-a:hover, body .pvl-toast .pvl-toast-a:focus-visible' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'bar_btn_typo', 'label' => 'Font', 'selector' => 'body .pvl-toast .pvl-toast-a' ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'bar_btn_border', 'label' => 'Chenar', 'selector' => 'body .pvl-toast .pvl-toast-a' ) );
		$this->add_responsive_control(
			'bar_btn_pad',
			array(
				'label'      => 'Padding',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'selectors'  => array( 'body .pvl-toast .pvl-toast-a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'bar_btn_radius',
			array(
				'label'      => 'Colțuri buton',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( 'body .pvl-toast .pvl-toast-a' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control( 'bar_x_heading', array( 'label' => 'Butonul × (ascunde bara)', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->icon_button_controls( 'bar_x', 'body .pvl-toast', 'tx' );
		$this->end_controls_section();

		/* ---------- Stil: panoul lateral (unul pe tot site-ul) ---------- */
		$p = 'body .pvl-drawer';
		$this->start_controls_section( 'section_style_drawer', array( 'label' => 'Panoul cu produse (pe tot site-ul)', 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'drawer' => 'yes' ) ) );
		$this->add_responsive_control(
			'dw_width',
			array(
				'label'      => 'Lățime',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vw' ),
				'range'      => array( 'px' => array( 'min' => 300, 'max' => 720 ), 'vw' => array( 'min' => 40, 'max' => 100 ) ),
				'selectors'  => array( $p . ' .pvl-dw-panel' => 'width: min({{SIZE}}{{UNIT}}, 100vw);' ),
			)
		);
		$this->add_control( 'dw_bg', array( 'label' => 'Fundal panou', 'type' => Controls_Manager::COLOR, 'selectors' => array( $p . ' .pvl-dw-panel' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'dw_color', array( 'label' => 'Culoare text', 'type' => Controls_Manager::COLOR, 'selectors' => array( $p . ' .pvl-dw-panel' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'dw_overlay', array( 'label' => 'Fundal întunecat (în spate)', 'type' => Controls_Manager::COLOR, 'selectors' => array( $p . ' .pvl-dw-overlay' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'dw_line', array( 'label' => 'Linii despărțitoare', 'type' => Controls_Manager::COLOR, 'selectors' => array( $p . ' .pvl-dw-head, ' . $p . ' .pvl-dw-foot' => 'border-color: {{VALUE}};' ) ) );
		$this->add_responsive_control(
			'dw_pad',
			array(
				'label'      => 'Spațiu lateral',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 60 ) ),
				'selectors'  => array( $p . ' .pvl-dw-panel' => '--pvl-dw-pad: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control( 'dw_h_title', array( 'label' => 'Titlu', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'dw_title_color', array( 'label' => 'Culoare', 'type' => Controls_Manager::COLOR, 'selectors' => array( $p . ' .pvl-dw-title' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'dw_title_typo', 'label' => 'Font', 'selector' => $p . ' .pvl-dw-title' ) );

		$this->add_control( 'dw_h_items', array( 'label' => 'Produse', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'dw_item_bg', array( 'label' => 'Fundal rând', 'type' => Controls_Manager::COLOR, 'selectors' => array( $p . ' .pvl-item' => 'background-color: {{VALUE}};' ) ) );
		$this->add_responsive_control(
			'dw_item_radius',
			array(
				'label'      => 'Colțuri rând',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 24 ) ),
				'selectors'  => array( $p . ' .pvl-item' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'dw_img',
			array(
				'label'      => 'Mărime poză',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 32, 'max' => 120 ) ),
				'selectors'  => array( $p . ' .pvl-media' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control( 'dw_name_color', array( 'label' => 'Culoare nume', 'type' => Controls_Manager::COLOR, 'selectors' => array( $p . ' .pvl-name' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'dw_name_typo', 'label' => 'Font nume', 'selector' => $p . ' .pvl-name' ) );
		$this->add_control( 'dw_cat_color', array( 'label' => 'Culoare categorie', 'type' => Controls_Manager::COLOR, 'selectors' => array( $p . ' .pvl-cat' => 'color: {{VALUE}}; opacity: 1;' ) ) );
		$this->add_control( 'dw_rm_color', array( 'label' => 'Culoare ×', 'type' => Controls_Manager::COLOR, 'selectors' => array( $p => '--pvl-rm-c: {{VALUE}};' ) ) );
		$this->add_control( 'dw_rm_color_h', array( 'label' => 'Culoare × la hover', 'type' => Controls_Manager::COLOR, 'selectors' => array( $p => '--pvl-rm-c-h: {{VALUE}};' ) ) );

		$this->add_control( 'dw_h_cta', array( 'label' => 'Butonul mare', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'dw_cta_typo', 'label' => 'Font', 'selector' => $p . ' .pvl-dw-cta' ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'dw_cta_border', 'label' => 'Chenar', 'selector' => $p . ' .pvl-dw-cta' ) );
		$this->add_responsive_control(
			'dw_cta_pad',
			array(
				'label'      => 'Padding',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'selectors'  => array( $p . ' .pvl-dw-cta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_control( 'dw_cta_bd_h', array( 'label' => 'Culoare chenar la hover', 'type' => Controls_Manager::COLOR, 'selectors' => array( $p . ' .pvl-dw-cta:hover, ' . $p . ' .pvl-dw-cta:focus-visible' => 'border-color: {{VALUE}};' ) ) );
		$this->add_control( 'dw_cta_bg', array( 'label' => 'Fundal', 'type' => Controls_Manager::COLOR, 'selectors' => array( $p . ' .pvl-dw-cta' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'dw_cta_color', array( 'label' => 'Text', 'type' => Controls_Manager::COLOR, 'selectors' => array( $p . ' .pvl-dw-cta' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'dw_cta_bg_h', array( 'label' => 'Fundal la hover', 'type' => Controls_Manager::COLOR, 'selectors' => array( $p . ' .pvl-dw-cta:hover, ' . $p . ' .pvl-dw-cta:focus-visible' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'dw_cta_color_h', array( 'label' => 'Text la hover', 'type' => Controls_Manager::COLOR, 'selectors' => array( $p . ' .pvl-dw-cta:hover, ' . $p . ' .pvl-dw-cta:focus-visible' => 'color: {{VALUE}};' ) ) );
		$this->add_responsive_control(
			'dw_cta_radius',
			array(
				'label'      => 'Colțuri',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( $p . ' .pvl-dw-cta, ' . $p . ' .pvl-dw-browse' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'dw_cta_h',
			array(
				'label'      => 'Înălțime',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 40, 'max' => 80 ) ),
				'selectors'  => array( $p . ' .pvl-dw-cta' => 'min-height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control( 'dw_h_more', array( 'label' => 'Butonul „Continuă să alegi”', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->text_button_controls( 'dw_more', $p, 'mo', 'body .pvl-drawer button.pvl-dw-more', true );

		$this->add_control( 'dw_h_browse', array( 'label' => 'Butonul „Vezi produsele” (listă goală)', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->text_button_controls( 'dw_browse_btn', $p, 'br', 'body .pvl-drawer .pvl-dw-browse', false );

		$this->add_control( 'dw_h_x', array( 'label' => 'Butonul × (închide)', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->icon_button_controls( 'dw_x', $p, 'dx' );
		$this->end_controls_section();
	}

	/* buton cu iconiță (×): mărime, iconiță, chenar, colțuri, culori normal / hover — prin variabile (ferite de stilul temei) */
	private function icon_button_controls( $id, $scope, $v ) {
		$this->add_responsive_control( $id . '_box', array( 'label' => 'Mărime buton', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 20, 'max' => 72 ) ), 'selectors' => array( $scope => '--pvl-' . $v . '-size: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( $id . '_size', array( 'label' => 'Mărime ×', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 8, 'max' => 40 ) ), 'selectors' => array( $scope => '--pvl-' . $v . '-i: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( $id . '_bw', array( 'label' => 'Grosime chenar', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 6, 'step' => 0.5 ) ), 'selectors' => array( $scope => '--pvl-' . $v . '-bw: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( $id . '_r', array( 'label' => 'Colțuri', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ), '%' => array( 'min' => 0, 'max' => 50 ) ), 'selectors' => array( $scope => '--pvl-' . $v . '-r: {{SIZE}}{{UNIT}};' ) ) );
		$this->start_controls_tabs( $id . '_tabs' );
		foreach ( array( '' => 'Normal', '_h' => 'Hover' ) as $k => $label ) {
			$sfx = '' === $k ? '' : '-h';
			$this->start_controls_tab( $id . '_tab' . $k, array( 'label' => $label ) );
			$this->add_control( $id . '_color' . $k, array( 'label' => 'Culoare ×', 'type' => Controls_Manager::COLOR, 'selectors' => array( $scope => '--pvl-' . $v . '-c' . $sfx . ': {{VALUE}};' ) ) );
			$this->add_control( $id . '_bg' . $k, array( 'label' => 'Fundal', 'type' => Controls_Manager::COLOR, 'selectors' => array( $scope => '--pvl-' . $v . '-bg' . $sfx . ': {{VALUE}};' ) ) );
			$this->add_control( $id . '_bd' . $k, array( 'label' => 'Culoare chenar', 'type' => Controls_Manager::COLOR, 'selectors' => array( $scope => '--pvl-' . $v . '-bd' . $sfx . ': {{VALUE}};' ) ) );
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();
	}

	/* buton cu text („Continuă să alegi”, „Vezi produsele”): font, culori, fundal, chenar, colțuri, padding, subliniere, lățime */
	private function text_button_controls( $id, $scope, $v, $el, $more ) {
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => $id . '_typo', 'label' => 'Font', 'selector' => $el ) );
		$color = 'mo' === $v ? 'more-c' : $v . '-c';
		$this->start_controls_tabs( $id . '_tabs' );
		foreach ( array( '' => 'Normal', '_h' => 'Hover' ) as $k => $label ) {
			$sfx = '' === $k ? '' : '-h';
			$this->start_controls_tab( $id . '_tab' . $k, array( 'label' => $label ) );
			/* „dw_more_color” păstrează numele vechi: valoarea aleasă înainte rămâne */
			$this->add_control( $id . '_color' . $k, array( 'label' => 'Culoare text', 'type' => Controls_Manager::COLOR, 'selectors' => array( $scope => '--pvl-' . $color . $sfx . ': {{VALUE}};' ) ) );
			$this->add_control( $id . '_bg' . $k, array( 'label' => 'Fundal', 'type' => Controls_Manager::COLOR, 'selectors' => array( $scope => '--pvl-' . $v . '-bg' . $sfx . ': {{VALUE}};' ) ) );
			$this->add_control( $id . '_bd' . $k, array( 'label' => 'Culoare chenar', 'type' => Controls_Manager::COLOR, 'selectors' => array( $scope => '--pvl-' . $v . '-bd' . $sfx . ': {{VALUE}};' ) ) );
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();
		$this->add_responsive_control( $id . '_bw', array( 'label' => 'Grosime chenar', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 6, 'step' => 0.5 ) ), 'separator' => 'before', 'selectors' => array( $scope => '--pvl-' . $v . '-bw: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( $id . '_r', array( 'label' => 'Colțuri', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'selectors' => array( $scope => '--pvl-' . $v . '-r: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( $id . '_p', array( 'label' => 'Padding', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px' ), 'selectors' => array( $scope => '--pvl-' . $v . '-p: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		if ( $more ) {
			$this->add_control( $id . '_u', array( 'label' => 'Fără subliniere', 'type' => Controls_Manager::SWITCHER, 'label_on' => 'Da', 'label_off' => 'Nu', 'return_value' => 'none', 'default' => '', 'selectors' => array( $scope => '--pvl-' . $v . '-u: {{VALUE}};' ) ) );
			$this->add_control( $id . '_full', array( 'label' => 'Toată lățimea', 'type' => Controls_Manager::SWITCHER, 'label_on' => 'Da', 'label_off' => 'Nu', 'return_value' => 'yes', 'default' => '', 'selectors_dictionary' => array( 'yes' => '--pvl-' . $v . '-w: 100%; --pvl-' . $v . '-align: stretch;' ), 'selectors' => array( $scope => '{{VALUE}}' ) ) );
		}
	}

	/* setările panoului lateral, citite de oferta-lista.js */
	private function drawer_attrs( $s, $form ) {
		$a = array(
			'drawer'     => 'yes' === ( $s['drawer'] ?? 'yes' ) ? '1' : '0',
			'side'       => 'left' === ( $s['drawer_side'] ?? 'right' ) ? 'left' : 'right',
			'auto'       => 'yes' === ( $s['drawer_auto'] ?? '' ) ? '1' : '0',
			't-title'    => (string) ( $s['dw_title'] ?? '' ),
			't-cta'      => (string) ( $s['dw_cta'] ?? '' ),
			't-more'     => (string) ( $s['dw_more'] ?? '' ),
			't-empty'    => (string) ( $s['dw_empty'] ?? '' ),
			't-browse'   => (string) ( $s['dw_browse'] ?? '' ),
			'browse-url' => '' !== trim( (string) ( $s['dw_browse_url'] ?? '' ) ) ? Pavva_W_Lista::resolve_url( $s['dw_browse_url'] ) : '',
			'form'       => $form,
		);
		$out = '';
		foreach ( $a as $k => $v ) $out .= ' data-pvl-' . $k . '="' . esc_attr( $v ) . '"';
		return $out;
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$edit  = $this->is_edit_mode();
		$label = trim( (string) ( $s['text'] ?? '' ) );
		$url   = class_exists( 'Pavva_W_Lista' ) ? Pavva_W_Lista::resolve_url( $s['form_url'] ?? '' ) : home_url( '/solicita-oferta/' );
		$hide  = ! $edit && 'yes' === ( $s['hide_empty'] ?? '' );
		?>
		<div class="pvl-counter-wrap">
			<a class="pvl-counter<?php echo $edit ? '' : ' is-empty'; ?>" href="<?php echo esc_url( $url ); ?>" data-pvl-counter
				data-pvl-label="<?php echo esc_attr( '' !== $label ? $label : 'Oferta ta' ); ?>"<?php echo $hide ? ' data-pvl-hide-empty' : ''; ?><?php echo $edit ? ' data-pvl-preview' : ''; ?><?php echo $this->drawer_attrs( $s, $url ); // phpcs:ignore -- escapat în drawer_attrs ?>
				aria-label="<?php echo esc_attr( '' !== $label ? $label : 'Oferta ta' ); ?>">
				<span class="pvl-c-ico" aria-hidden="true">
					<?php Icons_Manager::render_icon( $s['icon'] ?? array(), array( 'aria-hidden' => 'true' ) ); ?>
					<?php if ( 'yes' === ( $s['show_count'] ?? 'yes' ) ) : ?>
						<span class="pvl-badge" data-pvl-count><?php echo $edit ? '3' : '0'; ?></span>
					<?php endif; ?>
				</span>
				<?php if ( '' !== $label ) : ?>
					<span class="pvl-c-text" aria-hidden="true"><?php echo esc_html( $label ); ?></span>
				<?php endif; ?>
			</a>
		</div>
		<?php
		if ( $edit ) $this->editor_preview( $s, $url );
	}

	/* bara de jos și panoul, doar în editor (aceleași clase ca pe site: stilurile din Style se văd imediat) */
	private function editor_preview( $s, $url ) {
		$bar    = 'yes' === ( $s['preview_bar'] ?? '' );
		$drawer = 'yes' === ( $s['preview_drawer'] ?? '' ) && 'yes' === ( $s['drawer'] ?? 'yes' );
		if ( ! $bar && ! $drawer ) return;
		$x     = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>';
		$items = array();
		foreach ( array_slice( array_keys( Pavva_W_Lista::product_options() ), 0, 3 ) as $pid ) {
			$it = Pavva_W_Lista::item( (int) $pid );
			if ( $it ) $items[] = $it;
		}
		$t = function ( $k, $def ) use ( $s ) { $v = trim( (string) ( $s[ $k ] ?? '' ) ); return '' !== $v ? $v : $def; };
		echo '<div class="pvl-editor-preview" data-pvl-preview>';
		if ( $bar ) {
			echo '<div class="pvl-toast is-on pvl-toast-preview"><span class="pvl-toast-t">' . esc_html( $t( 'dw_title', 'Oferta ta' ) ) . ': <strong>' . esc_html( count( $items ) . ' produse' ) . '</strong></span>'
				. '<a class="pvl-toast-a" href="' . esc_url( $url ) . '">Vezi oferta</a>'
				. '<button type="button" class="pvl-toast-x" aria-label="Ascunde bara">' . $x . '</button></div>'; // phpcs:ignore -- SVG fix
		}
		if ( $drawer ) {
			$empty = 'yes' === ( $s['preview_empty'] ?? '' );
			$side  = 'left' === ( $s['drawer_side'] ?? 'right' ) ? 'left' : 'right';
			echo '<div class="pvl-drawer pvl-drawer-' . esc_attr( $side ) . ' is-open pvl-drawer-preview' . ( $empty ? ' is-empty' : '' ) . '">';
			echo '<div class="pvl-dw-overlay"></div><div class="pvl-dw-panel">';
			echo '<div class="pvl-dw-head"><h2 class="pvl-dw-title">' . esc_html( $t( 'dw_title', 'Oferta ta' ) ) . '<span class="pvl-dw-count">' . ( $empty ? '' : ' (' . count( $items ) . ')' ) . '</span></h2><button type="button" class="pvl-dw-x" aria-label="Închide">' . $x . '</button></div>'; // phpcs:ignore -- SVG fix
			echo '<div class="pvl-dw-body"><div class="pvl-list pvl-dw-list" data-pvl-drawer-list data-pvl-preview><ul class="pvl-items">';
			foreach ( $items as $it ) {
				echo '<li class="pvl-item"><span class="pvl-media">' . ( $it['img'] ? '<img src="' . esc_url( $it['img'] ) . '" alt="">' : '' ) . '</span>'
					. '<span class="pvl-info"><a class="pvl-name" href="' . esc_url( $it['url'] ) . '">' . esc_html( $it['name'] ) . '</a>' . ( $it['cat'] ? '<span class="pvl-cat">' . esc_html( $it['cat'] ) . '</span>' : '' ) . '</span>'
					. '<button type="button" class="pvl-remove" aria-label="Scoate din ofertă">' . $x . '</button></li>'; // phpcs:ignore -- SVG fix
			}
			echo '</ul></div>';
			echo '<div class="pvl-dw-empty"><p>' . esc_html( $t( 'dw_empty', 'Nu ai adăugat încă niciun produs. Apasă „Adaugă la ofertă” pe produsele care te interesează.' ) ) . '</p><button type="button" class="pvl-dw-browse">' . esc_html( $t( 'dw_browse', 'Vezi produsele' ) ) . '</button></div></div>';
			echo '<div class="pvl-dw-foot"><a class="pvl-dw-cta" href="' . esc_url( $url ) . '">' . esc_html( $t( 'dw_cta', 'Solicită ofertă' ) ) . '</a><button type="button" class="pvl-dw-more">' . esc_html( $t( 'dw_more', 'Continuă să alegi' ) ) . '</button></div>';
			echo '</div></div>';
		}
		echo '</div>';
	}
}
