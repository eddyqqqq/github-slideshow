<?php
/**
 * Pavva Widgets – Limbă (schimbarea limbii, cu Polylang)
 *
 * „RO | EN” (sau numele limbilor, sau o listă care se deschide) pentru header. Fiecare link duce la aceeași pagină
 * în cealaltă limbă; dacă pagina nu are traducere, la prima pagină a limbii.
 *
 * @package Pavva_Widgets
 * @author  Imagient <https://imagient.ro/>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class Pavva_Limba_Widget extends Widget_Base {

	public function get_name() { return 'pavva_limba'; }
	public function get_title() { return 'Pavva – Limbă'; }
	public function get_icon() { return 'eicon-globe'; }
	public function get_categories() { return array( 'pavva' ); }
	public function get_keywords() { return array( 'limba', 'limbă', 'language', 'switcher', 'polylang', 'ro', 'en', 'pavva' ); }
	public function get_style_depends() { return array( 'pavva-limba' ); }

	protected function register_controls() {
		$this->start_controls_section( 'sec_content', array( 'label' => 'Limbă' ) );
		$this->add_control( 'note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => 'Limbile vin din Polylang (Limbi → Limbi). Fiecare link duce la aceeași pagină în cealaltă limbă; dacă pagina nu e tradusă, la prima pagină a limbii.',
			'content_classes' => 'elementor-descriptor',
		) );
		$this->add_control( 'mode', array(
			'label'   => 'Afișare',
			'type'    => Controls_Manager::SELECT,
			'default' => 'codes',
			'options' => array( 'codes' => 'Coduri: RO | EN', 'names' => 'Nume: Română | English', 'drop' => 'Listă care se deschide (RO ▾)' ),
		) );
		$this->add_control( 'sep', array(
			'label'     => 'Separator',
			'type'      => Controls_Manager::TEXT,
			'default'   => '|',
			'condition' => array( 'mode!' => 'drop' ),
		) );
		$this->add_control( 'hide_current', array(
			'label'        => 'Ascunde limba curentă',
			'description'  => 'Da = se vede doar limba în care poți trece (ex. pe pagina în română apare doar „EN”).',
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
			'condition'    => array( 'mode!' => 'drop' ),
		) );
		$this->add_responsive_control( 'align', array(
			'label'     => 'Aliniere',
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array( 'flex-start' => array( 'title' => 'Stânga', 'icon' => 'eicon-text-align-left' ), 'center' => array( 'title' => 'Centru', 'icon' => 'eicon-text-align-center' ), 'flex-end' => array( 'title' => 'Dreapta', 'icon' => 'eicon-text-align-right' ) ),
			'selectors' => array( '{{WRAPPER}} .pvlang' => 'justify-content: {{VALUE}};' ),
		) );
		$this->end_controls_section();

		/* ---------- stil: linkurile ---------- */
		$this->start_controls_section( 'sec_style', array( 'label' => 'Linkuri', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'typo', 'selector' => '{{WRAPPER}} .pvlang-a, {{WRAPPER}} .pvlang-btn' ) );
		$this->start_controls_tabs( 'tabs_link' );
		foreach ( array( '' => 'Normal', '_h' => 'Hover', '_on' => 'Activă' ) as $suf => $lab ) {
			$sel = '' === $suf ? '{{WRAPPER}} .pvlang-a, {{WRAPPER}} .pvlang-btn' : ( '_h' === $suf ? '{{WRAPPER}} .pvlang-a:hover, {{WRAPPER}} .pvlang-a:focus-visible, {{WRAPPER}} .pvlang-btn:hover' : '{{WRAPPER}} .pvlang-a.is-on' );
			$this->start_controls_tab( 'tab_link' . $suf, array( 'label' => $lab ) );
			$this->add_control( 'c' . $suf, array( 'label' => 'Text', 'type' => Controls_Manager::COLOR, 'default' => '_on' === $suf ? '#AB5525' : '', 'selectors' => array( $sel => 'color: {{VALUE}};' ) ) );
			$this->add_control( 'bg' . $suf, array( 'label' => 'Fundal', 'type' => Controls_Manager::COLOR, 'selectors' => array( $sel => 'background-color: {{VALUE}};' ) ) );
			$this->add_control( 'bd' . $suf, array( 'label' => 'Culoare chenar', 'type' => Controls_Manager::COLOR, 'selectors' => array( $sel => 'border-color: {{VALUE}};' ) ) );
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'border', 'selector' => '{{WRAPPER}} .pvlang-a, {{WRAPPER}} .pvlang-btn', 'separator' => 'before' ) );
		$this->add_responsive_control( 'radius', array( 'label' => 'Colțuri', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', '%' ), 'selectors' => array( '{{WRAPPER}} .pvlang-a, {{WRAPPER}} .pvlang-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'pad', array( 'label' => 'Padding', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .pvlang-a, {{WRAPPER}} .pvlang-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'height', array(
			'label'       => 'Înălțime minimă',
			'description' => 'Pune aceeași înălțime ca butonul „Solicită ofertă”, ca să stea aliniate.',
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => array( 'px' ),
			'range'       => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'selectors'   => array( '{{WRAPPER}} .pvlang-a, {{WRAPPER}} .pvlang-btn' => 'min-height: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'gap', array(
			'label'      => 'Spațiu între limbi',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'selectors'  => array( '{{WRAPPER}} .pvlang' => '--pvlang-gap: {{SIZE}}{{UNIT}};' ),
			'separator'  => 'before',
		) );
		$this->add_control( 'sep_c', array( 'label' => 'Culoare separator', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .pvlang-sep' => 'color: {{VALUE}};' ), 'condition' => array( 'mode!' => 'drop' ) ) );
		$this->add_control( 'ease', array(
			'label'     => 'Durată tranziție (ms)',
			'type'      => Controls_Manager::NUMBER,
			'default'   => 200,
			'selectors' => array( '{{WRAPPER}} .pvlang' => '--pvlang-ease: {{VALUE}}ms;' ),
		) );
		$this->end_controls_section();

		/* ---------- stil: lista care se deschide ---------- */
		$this->start_controls_section( 'sec_drop', array( 'label' => 'Listă (Afișare: listă)', 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'mode' => 'drop' ) ) );
		$this->add_control( 'drop_bg', array( 'label' => 'Fundal listă', 'type' => Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => array( '{{WRAPPER}} .pvlang-menu' => 'background-color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'drop_border', 'selector' => '{{WRAPPER}} .pvlang-menu' ) );
		$this->add_control( 'drop_r', array( 'label' => 'Colțuri listă', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 24 ) ), 'selectors' => array( '{{WRAPPER}} .pvlang-menu' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'drop_shadow', 'selector' => '{{WRAPPER}} .pvlang-menu' ) );
		$this->end_controls_section();
	}

	/* limbile Polylang, cu linkul către pagina curentă în fiecare limbă */
	private function languages(): array {
		if ( ! function_exists( 'pll_the_languages' ) ) return array();
		$raw = pll_the_languages( array( 'raw' => 1, 'hide_if_empty' => 0, 'hide_if_no_translation' => 0, 'force_home' => 0 ) );
		$out = array();
		foreach ( (array) $raw as $l ) {
			$out[] = array(
				'slug'   => (string) $l['slug'],
				'code'   => strtoupper( (string) $l['slug'] ),
				'name'   => (string) $l['name'],
				'url'    => (string) $l['url'],
				'locale' => str_replace( '_', '-', (string) $l['locale'] ),
				'on'     => ! empty( $l['current_lang'] ),
			);
		}
		return $out;
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$langs = $this->languages();
		if ( ! $langs ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) echo '<div class="pvlang-note">Pavva – Limbă: activează Polylang și adaugă limbile (Limbi → Limbi).</div>';
			return;
		}
		$mode  = in_array( $s['mode'] ?? 'codes', array( 'codes', 'names', 'drop' ), true ) ? $s['mode'] : 'codes';
		$label = function ( $l ) use ( $mode ) { return 'names' === $mode ? $l['name'] : $l['code']; };
		$link  = function ( $l ) use ( $label ) {
			return '<a class="pvlang-a' . ( $l['on'] ? ' is-on' : '' ) . '" href="' . esc_url( $l['url'] ) . '" hreflang="' . esc_attr( $l['locale'] ) . '" lang="' . esc_attr( $l['locale'] ) . '"'
				. ( $l['on'] ? ' aria-current="true"' : '' ) . ' title="' . esc_attr( $l['name'] ) . '">' . esc_html( $label( $l ) ) . '</a>';
		};

		if ( 'drop' === $mode ) {
			$cur = $langs[0];
			foreach ( $langs as $l ) if ( $l['on'] ) $cur = $l;
			echo '<div class="pvlang pvlang--drop"><button type="button" class="pvlang-btn" aria-expanded="false" aria-haspopup="true" aria-label="' . esc_attr( pavva_t( 'Limbă' ) . ': ' . $cur['name'] ) . '">'
				. esc_html( $cur['code'] ) . '<svg class="pvlang-chev" viewBox="0 0 24 24" width="12" height="12" aria-hidden="true"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>'
				. '<ul class="pvlang-menu" hidden>';
			foreach ( $langs as $l ) echo '<li>' . str_replace( '>' . esc_html( $l['code'] ) . '<', '>' . esc_html( $l['name'] ) . '<', $link( $l ) ) . '</li>'; // phpcs:ignore -- escapat în $link
			echo '</ul></div>';
			?><script>(function(){var w=document.currentScript.previousElementSibling;if(!w||w.dataset.pvlang)return;w.dataset.pvlang=1;var b=w.querySelector('.pvlang-btn'),m=w.querySelector('.pvlang-menu');function t(o){b.setAttribute('aria-expanded',o?'true':'false');m.hidden=!o;w.classList.toggle('is-open',o);}b.addEventListener('click',function(e){e.stopPropagation();t(m.hidden);});document.addEventListener('click',function(e){if(!w.contains(e.target))t(false);});document.addEventListener('keydown',function(e){if(e.key==='Escape')t(false);});})();</script><?php
			return;
		}

		$sep   = isset( $s['sep'] ) ? trim( (string) $s['sep'] ) : '|';
		$parts = array();
		foreach ( $langs as $l ) {
			if ( 'yes' === ( $s['hide_current'] ?? '' ) && $l['on'] ) continue;
			$parts[] = $link( $l );
		}
		echo '<nav class="pvlang pvlang--inline" aria-label="' . esc_attr( pavva_t( 'Limbă' ) ) . '">' . implode( '' !== $sep ? '<span class="pvlang-sep" aria-hidden="true">' . esc_html( $sep ) . '</span>' : '', $parts ) . '</nav>'; // phpcs:ignore -- escapat
	}
}
