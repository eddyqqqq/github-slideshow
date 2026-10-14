<?php
/**
 * Pavva Widgets – Listă ofertă
 *
 * Produsele alese de vizitator (cu poză, categorie și buton de ștergere). Se pune de obicei pe pagina
 * Solicită ofertă, deasupra sau lângă formular. Fără acest widget, lista apare automat în formular.
 * Rândurile le scrie assets/oferta-lista.js (lista e în browserul vizitatorului).
 *
 * @package Pavva_Widgets
 * @author  Imagient <https://imagient.ro/>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Utils;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

require_once __DIR__ . '/trait-pavva-oferta-stil.php';

class Pavva_Oferta_Lista_Widget extends Widget_Base {
	use Pavva_Oferta_Stil;

	public function get_name() { return 'pavva_oferta_lista'; }
	public function get_title() { return 'Pavva – Listă ofertă'; }
	public function get_icon() { return 'eicon-bullet-list'; }
	public function get_categories() { return array( 'pavva' ); }
	public function get_keywords() { return array( 'oferta', 'ofertă', 'lista', 'produse', 'formular', 'pavva' ); }

	private function is_edit_mode() {
		return class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	private function sel( $inner = '' ) {
		return '{{WRAPPER}} .pvl-list' . ( $inner ? ' ' . $inner : '' );
	}

	protected function register_controls() {

		$this->start_controls_section( 'section_content', array( 'label' => 'Listă ofertă' ) );

		$this->add_control(
			'title',
			array(
				'label'       => 'Titlu',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Produsele tale',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);
		$this->add_control(
			'title_tag',
			array(
				'label'   => 'Tag HTML titlu',
				'type'    => Controls_Manager::SELECT,
				'default' => 'p',
				'options' => array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6', 'p' => 'p', 'div' => 'div' ),
			)
		);
		$this->add_control(
			'title_count',
			array(
				'label'        => 'Numărul de produse lângă titlu',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
		$this->add_control(
			'empty_text',
			array(
				'label'       => 'Text când lista e goală',
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => 'Alege produsele dorite din lista de mai jos. Poți adăuga mai multe.',
				'description' => 'Gol = cât lista e goală, widgetul nu se vede deloc.',
			)
		);
		$this->add_control(
			'into_form',
			array(
				'label'        => 'Așază în formular',
				'description'  => 'Pe site, lista intră în formularul de ofertă, deasupra câmpului „Categorie produs” (în editor rămâne unde ai pus-o, ca s-o poți stiliza). Nu = rămâne exact unde e pusă.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);
		$this->add_control(
			'picker',
			array(
				'label'        => 'Selector „Adaugă produs”',
				'description'  => 'În formular: categorie → produs (cu poză) → culoare → grosime. Listele „Categorie produs” și „Produs solicitat” se ascund (datele lor se trimit în continuare). Nu = rămân listele.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'into_form' => 'yes' ),
			)
		);
		$this->add_control(
			'pos',
			array(
				'label'     => 'Poziție în formular',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'top',
				'options'   => array( 'top' => 'Sus, la începutul formularului', 'fields' => 'În locul câmpurilor Categorie / Produs' ),
				'condition' => array( 'into_form' => 'yes', 'picker' => 'yes' ),
			)
		);
		$this->add_control(
			'align_fields',
			array(
				'label'        => 'Textul aliniat cu câmpurile',
				'description'  => 'Titlul și textul încep la fel ca textul din câmpurile formularului.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'into_form' => 'yes' ),
			)
		);
		$this->add_control(
			'preview_adder',
			array(
				'label'        => 'Arată panoul „Adaugă produs” în editor',
				'description'  => 'Doar în editor, ca să poți stiliza categoriile, produsele și etichetele.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'into_form' => 'yes', 'picker' => 'yes' ),
			)
		);
		$this->add_control(
			'show_image',
			array(
				'label'        => 'Poza produsului',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);
		$this->add_control(
			'show_cat',
			array(
				'label'        => 'Categoria',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
		$this->add_control(
			'show_clear',
			array(
				'label'        => 'Link „Golește lista”',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
			)
		);
		$this->add_control(
			'clear_text',
			array(
				'label'     => 'Text link',
				'type'      => Controls_Manager::TEXT,
				'default'   => 'Golește lista',
				'condition' => array( 'show_clear' => 'yes' ),
			)
		);

		$this->end_controls_section();

		/* ---------- Stil: titlu ---------- */
		$this->start_controls_section( 'section_style_title', array( 'label' => 'Titlu și text', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'title_color', array( 'label' => 'Culoare titlu', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel( '.pvl-title' ) => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'title_typo', 'label' => 'Font titlu', 'selector' => $this->sel( '.pvl-title' ) ) );
		$this->add_control( 'empty_color', array( 'label' => 'Culoare text listă goală', 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => array( $this->sel( '.pvl-empty' ) => 'color: {{VALUE}}; opacity: 1;' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'empty_typo', 'label' => 'Font text listă goală', 'selector' => $this->sel( '.pvl-empty' ) ) );
		$this->add_responsive_control(
			'gap',
			array(
				'label'      => 'Distanța titlu – listă',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'separator'  => 'before',
				'selectors'  => array( $this->sel() => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();

		/* ---------- Stil: rânduri ---------- */
		$this->start_controls_section( 'section_style_items', array( 'label' => 'Produse', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control(
			'item_gap',
			array(
				'label'      => 'Distanța dintre produse',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( $this->sel( '.pvl-items' ) => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control( 'item_bg', array( 'label' => 'Fundal', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel( '.pvl-item' ) => 'background-color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'item_border', 'selector' => $this->sel( '.pvl-item' ) ) );
		$this->add_responsive_control(
			'item_radius',
			array(
				'label'      => 'Colțuri rotunjite',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'selectors'  => array( $this->sel( '.pvl-item' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'item_padding',
			array(
				'label'      => 'Spațiu interior',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'selectors'  => array( $this->sel( '.pvl-item' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'img_size',
			array(
				'label'      => 'Mărime poză',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 24, 'max' => 140 ) ),
				'separator'  => 'before',
				'condition'  => array( 'show_image' => 'yes' ),
				'selectors'  => array( $this->sel( '.pvl-media' ) => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'img_radius',
			array(
				'label'      => 'Colțuri poză',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'condition'  => array( 'show_image' => 'yes' ),
				'selectors'  => array( $this->sel( '.pvl-media' ) => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control( 'name_color', array( 'label' => 'Culoare nume', 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => array( $this->sel( '.pvl-name' ) => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'name_typo', 'label' => 'Font nume', 'selector' => $this->sel( '.pvl-name' ) ) );
		$this->add_control( 'cat_color', array( 'label' => 'Culoare categorie', 'type' => Controls_Manager::COLOR, 'condition' => array( 'show_cat' => 'yes' ), 'selectors' => array( $this->sel( '.pvl-cat' ) => 'color: {{VALUE}}; opacity: 1;' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'cat_typo', 'label' => 'Font categorie', 'condition' => array( 'show_cat' => 'yes' ), 'selector' => $this->sel( '.pvl-cat' ) ) );

		$this->add_control( 'add_heading', array( 'label' => 'Butonul „Adaugă produs”', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'add_c', array( 'label' => 'Culoare text', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pvl-add-c: {{VALUE}};' ) ) );
		$this->add_control( 'add_bg', array( 'label' => 'Fundal', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pvl-add-bg: {{VALUE}};' ) ) );
		$this->add_control( 'add_bd', array( 'label' => 'Culoare chenar', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pvl-add-bd: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'add_typo', 'label' => 'Font', 'selector' => '{{WRAPPER}} .pvl-in-form button.pvl-add-btn' ) );
		$this->add_control( 'edit_c', array( 'label' => 'Culoare „Modifică”', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pvl-edit-c: {{VALUE}};' ) ) );

		$this->add_control( 'rm_heading', array( 'label' => 'Butonul de ștergere', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'rm_color', array( 'label' => 'Culoare', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pvl-rm-c: {{VALUE}};' ) ) );
		$this->add_control( 'rm_color_hover', array( 'label' => 'Culoare la hover', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pvl-rm-c-h: {{VALUE}};' ) ) );
		$this->add_control( 'rm_bg', array( 'label' => 'Fundal', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pvl-rm-bg: {{VALUE}};' ) ) );
		$this->add_control( 'rm_bg_hover', array( 'label' => 'Fundal la hover', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--pvl-rm-bg-h: {{VALUE}};' ) ) );
		$this->add_responsive_control(
			'rm_size',
			array(
				'label'      => 'Mărime buton',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 20, 'max' => 64 ) ),
				'selectors'  => array( $this->sel( '.pvl-remove' ) => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'rm_icon',
			array(
				'label'      => 'Mărime ×',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 32 ) ),
				'selectors'  => array( $this->sel( '.pvl-remove svg' ) => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'rm_stroke',
			array(
				'label'     => 'Grosime linie ×',
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 1, 'max' => 4, 'step' => 0.5 ) ),
				'selectors' => array( $this->sel( '.pvl-remove svg path' ) => 'stroke-width: {{SIZE}};' ),
			)
		);
		$this->add_responsive_control(
			'rm_radius',
			array(
				'label'      => 'Colțuri',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( $this->sel( '.pvl-remove' ) => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();

		/* ---------- Stil: etichetele de variantă („Portocaliu”, „6 cm”) ---------- */
		$this->start_controls_section( 'section_style_tags', array( 'label' => 'Etichete culoare / grosime', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->pvl_tag_controls( 'tag', $this->sel() );
		$this->end_controls_section();

		/* ---------- Stil: panoul „Adaugă produs” din formular ---------- */
		$a = $this->sel( '.pvl-adder' );
		$this->start_controls_section( 'section_style_adder', array( 'label' => 'Panoul „Adaugă produs”', 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'into_form' => 'yes', 'picker' => 'yes' ) ) );
		$this->add_control( 'ad_bg', array( 'label' => 'Fundal panou', 'type' => Controls_Manager::COLOR, 'selectors' => array( $a . ' .pvl-adder-panel' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'ad_bd', array( 'label' => 'Culoare chenar panou', 'type' => Controls_Manager::COLOR, 'selectors' => array( $a . ' .pvl-adder-panel' => 'border-color: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'ad_bw', array( 'label' => 'Grosime chenar panou', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 6 ) ), 'selectors' => array( $a . ' .pvl-adder-panel' => 'border-width: {{SIZE}}{{UNIT}}; border-style: solid;' ) ) );
		$this->add_responsive_control( 'ad_r', array( 'label' => 'Colțuri panou', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 30 ) ), 'selectors' => array( $a . ' .pvl-adder-panel' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'ad_p', array( 'label' => 'Padding panou', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px' ), 'selectors' => array( $a . ' .pvl-adder-panel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_control( 'ad_title_c', array( 'label' => 'Culoare titlu „Alege produsul”', 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => array( $a . ' .pvl-adder-title' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'ad_title_typo', 'label' => 'Font titlu', 'selector' => $a . ' .pvl-adder-title' ) );
		$this->add_control( 'ad_step_c', array( 'label' => 'Culoare „Categorie” / „Produs”', 'type' => Controls_Manager::COLOR, 'selectors' => array( $a . ' .pvl-step' => 'color: {{VALUE}}; opacity: 1;' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'ad_step_typo', 'label' => 'Font „Categorie” / „Produs”', 'selector' => $a . ' .pvl-step' ) );

		$this->add_control( 'ad_h_ch', array( 'label' => 'Categorii (butoane)', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->pvl_state_tabs( 'ad_ch', $a, 'ch', array( '' => 'Normal', '-h' => 'Hover', '-on' => 'Selectat' ) );
		$this->pvl_size( 'ad_ch_bw', 'Grosime chenar', $a, 'ch-bw', 6, array( 'px' ), array( 'separator' => 'before' ) );
		$this->pvl_size( 'ad_ch_r', 'Colțuri', $a, 'ch-r', 40, array( 'px', '%' ) );
		$this->pvl_pad( 'ad_ch_p', 'Padding', $a, 'ch-p' );
		$this->pvl_size( 'ad_ch_fs', 'Mărime text', $a, 'ch-fs', 24 );

		$this->add_control( 'ad_h_ti', array( 'label' => 'Produse (carduri cu poză)', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->pvl_state_tabs( 'ad_ti', $a, 'ti', array( '' => 'Normal', '-h' => 'Hover' ) );
		$this->pvl_size( 'ad_ti_bw', 'Grosime chenar', $a, 'ti-bw', 6, array( 'px' ), array( 'separator' => 'before' ) );
		$this->pvl_size( 'ad_ti_r', 'Colțuri card', $a, 'ti-r', 30 );
		$this->pvl_size( 'ad_ti_img_r', 'Colțuri poză', $a, 'ti-img-r', 30 );
		$this->pvl_pad( 'ad_ti_p', 'Padding card', $a, 'ti-p' );
		$this->pvl_size( 'ad_ti_gap', 'Spațiu între carduri', $a, 'ti-gap', 40 );
		$this->pvl_size( 'ad_ti_mh', 'Înălțime listă (apoi scroll)', $a, 'ti-mh', 900, array( 'px', 'vh' ), array( 'range' => array( 'px' => array( 'min' => 200, 'max' => 900 ), 'vh' => array( 'min' => 20, 'max' => 90 ) ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'ad_ti_typo', 'label' => 'Font nume produs', 'selector' => $a . ' .pvl-tile-n' ) );
		$this->add_control( 'ad_tc_c', array( 'label' => 'Culoare titlu categorie (la „Toate”)', 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => array( $a . ' .pvl-tiles-cat' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'ad_tc_typo', 'label' => 'Font titlu categorie', 'selector' => $a . ' .pvl-tiles-cat' ) );
		$this->add_control( 'ad_note', array( 'type' => Controls_Manager::RAW_HTML, 'separator' => 'before', 'raw' => 'Culorile și grosimile de alegere (cardurile cu poză, butoanele de grosime) se stilizează din widgetul <strong>Oferta ta</strong> → Stil → <em>Fereastra culoare / grosime</em>: același stil e folosit și în fereastra de pe pagina produsului, și aici.', 'content_classes' => 'elementor-descriptor' ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$edit  = $this->is_edit_mode();
		$tag   = Utils::validate_html_tag( $s['title_tag'] ?? 'p' );
		$title = trim( (string) ( $s['title'] ?? '' ) );
		$empty = trim( (string) ( $s['empty_text'] ?? '' ) );
		$img   = 'yes' === ( $s['show_image'] ?? 'yes' );
		$cat   = 'yes' === ( $s['show_cat'] ?? 'yes' );

		/* în editor: câteva produse reale ca exemplu, ca să se poată stiliza */
		$sample = array();
		if ( $edit && class_exists( 'Pavva_W_Lista' ) ) {
			foreach ( Pavva_W_Lista::preview_ids( 2 ) as $i => $pid ) $sample[] = Pavva_W_Lista::item( (int) $pid ) + array( 'tags' => Pavva_W_Lista::preview_tags( (int) $pid, 1 === $i ) );
		}
		$classes = 'pvl-list' . ( $edit ? '' : ' is-empty' ) . ( '' === $empty ? ' pvl-hide-empty' : '' );
		/* în editor arată ca în formular (butonul „Adaugă produs”, două coloane) */
		if ( $edit && 'yes' === ( $s['into_form'] ?? 'yes' ) && 'yes' === ( $s['picker'] ?? 'yes' ) ) $classes .= ' pvl-in-form is-multi';
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" data-pvl-list data-pvl-img="<?php echo $img ? '1' : '0'; ?>" data-pvl-cat="<?php echo $cat ? '1' : '0'; ?>"<?php echo $edit ? ' data-pvl-preview' : ''; ?><?php echo ! $edit && 'yes' === ( $s['into_form'] ?? 'yes' ) ? ' data-pvl-into-form' : ''; ?> data-pvl-align="<?php echo 'yes' === ( $s['align_fields'] ?? 'yes' ) ? '1' : '0'; ?>" data-pvl-picker="<?php echo 'yes' === ( $s['picker'] ?? 'yes' ) ? '1' : '0'; ?>" data-pvl-pos="<?php echo 'fields' === ( $s['pos'] ?? 'top' ) ? 'fields' : 'top'; ?>">
			<?php if ( '' !== $title ) : ?>
				<<?php echo $tag; // phpcs:ignore ?> class="pvl-title"><?php echo esc_html( $title ); ?><?php if ( 'yes' === ( $s['title_count'] ?? 'yes' ) ) : ?><span class="pvl-title-count"><?php echo $edit && $sample ? ' (' . count( $sample ) . ')' : ''; ?></span><?php endif; ?></<?php echo $tag; // phpcs:ignore ?>>
			<?php endif; ?>
			<ul class="pvl-items">
				<?php foreach ( $sample as $it ) : ?>
					<li class="pvl-item">
						<?php if ( $img ) : ?><span class="pvl-media"><?php if ( $it['img'] ) : ?><img src="<?php echo esc_url( $it['img'] ); ?>" alt=""><?php endif; ?></span><?php endif; ?>
						<span class="pvl-info"><span class="pvl-name"><?php echo esc_html( $it['name'] ); ?></span><?php if ( $cat && $it['cat'] ) : ?><span class="pvl-cat"><?php echo esc_html( $it['cat'] ); ?></span><?php endif; ?><?php echo $it['tags']; // phpcs:ignore -- HTML escapat în preview_tags ?></span>
						<button type="button" class="pvl-remove" aria-label="Scoate din ofertă"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg></button>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( '' !== $empty ) : ?><p class="pvl-empty"><?php echo esc_html( $empty ); ?></p><?php endif; ?>
			<?php if ( 'yes' === ( $s['show_clear'] ?? '' ) ) : ?><button type="button" class="pvl-clear" data-pvl-clear><?php echo esc_html( $s['clear_text'] ?? 'Golește lista' ); ?></button><?php endif; ?>
			<?php if ( $edit && 'yes' === ( $s['into_form'] ?? 'yes' ) && 'yes' === ( $s['picker'] ?? 'yes' ) && 'yes' === ( $s['preview_adder'] ?? 'yes' ) ) $this->adder_preview(); ?>
		</div>
		<?php
	}

	/* editorul Elementor: panoul „Adaugă produs” deschis, cu categorii și câteva produse reale (pe site îl face oferta-lista.js) */
	private function adder_preview() {
		if ( ! class_exists( 'Pavva_W_Lista' ) ) return;
		$items = array();
		foreach ( array_slice( array_keys( Pavva_W_Lista::product_options() ), 0, 8 ) as $pid ) {
			$it = Pavva_W_Lista::item( (int) $pid );
			if ( $it ) $items[] = $it;
		}
		$cats = array();
		foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0 ) ) as $t ) {
			if ( ! is_wp_error( $t ) && 'uncategorized' !== $t->slug ) $cats[] = $t->name;
		}
		$x = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>';
		echo '<div class="pvl-adder is-open">';
		echo '<button type="button" class="pvl-add-btn" aria-expanded="true"><span class="pvl-add-plus" aria-hidden="true">+</span><span class="pvl-add-t">Adaugă încă un produs</span></button>';
		echo '<div class="pvl-adder-panel"><div class="pvl-adder-head"><p class="pvl-adder-title">Alege produsul</p><button type="button" class="pvl-adder-x" aria-label="Închide">' . $x . '</button></div>'; // phpcs:ignore -- SVG fix
		echo '<p class="pvl-step">Categorie</p><div class="pvl-chips"><button type="button" class="pvl-chip is-on">Toate</button>';
		foreach ( array_slice( $cats, 0, 6 ) as $c ) echo '<button type="button" class="pvl-chip">' . esc_html( $c ) . '</button>';
		echo '</div><p class="pvl-step">Produs</p><div class="pvl-tiles">';
		if ( $items ) echo '<p class="pvl-tiles-cat">' . esc_html( $items[0]['cat'] ?: 'Produse' ) . '</p>';
		foreach ( $items as $it ) {
			echo '<button type="button" class="pvl-tile"><span class="pvl-tile-img">' . ( $it['img'] ? '<img src="' . esc_url( $it['img'] ) . '" alt="">' : '' ) . '</span><span class="pvl-tile-n">' . esc_html( $it['name'] ) . '</span></button>';
		}
		echo '</div></div></div>';
	}
}
