<?php
/**
 * Pavva Widgets – controale de stil comune pentru lista de ofertă
 *
 * Etichetele de variantă (culoare / grosime) din listă, cardurile de culoare și butoanele de grosime din fereastra
 * „Alege varianta” și din formular, categoriile și dalele de produse din „Adaugă produs”.
 * Toate se aplică prin variabile CSS (--pvl-*), citite de assets/oferta-lista.css: așa stilul temei nu le mai suprascrie.
 *
 * @package Pavva_Widgets
 * @author  Imagient <https://imagient.ro/>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;

trait Pavva_Oferta_Stil {

	private function pvl_color( $id, $label, $scope, $var, $extra = array() ) {
		$this->add_control( $id, array_merge( array( 'label' => $label, 'type' => Controls_Manager::COLOR, 'selectors' => array( $scope => '--pvl-' . $var . ': {{VALUE}};' ) ), $extra ) );
	}

	private function pvl_size( $id, $label, $scope, $var, $max = 40, $units = array( 'px' ), $extra = array() ) {
		$range = array( 'px' => array( 'min' => 0, 'max' => $max, 'step' => $max <= 8 ? 0.5 : 1 ), '%' => array( 'min' => 0, 'max' => 50 ), 'em' => array( 'min' => 0.5, 'max' => 2, 'step' => 0.05 ) );
		$this->add_responsive_control( $id, array_merge( array( 'label' => $label, 'type' => Controls_Manager::SLIDER, 'size_units' => $units, 'range' => $range, 'selectors' => array( $scope => '--pvl-' . $var . ': {{SIZE}}{{UNIT}};' ) ), $extra ) );
	}

	/* mărime text: orice valoare (px / em / rem), cu limite largi */
	private function pvl_fs( $id, $label, $scope, $var ) {
		$this->add_responsive_control( $id, array( 'label' => $label, 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', 'em', 'rem' ), 'range' => array( 'px' => array( 'min' => 6, 'max' => 120 ), 'em' => array( 'min' => 0.3, 'max' => 6, 'step' => 0.05 ), 'rem' => array( 'min' => 0.3, 'max' => 6, 'step' => 0.05 ) ), 'selectors' => array( $scope => '--pvl-' . $var . ': {{SIZE}}{{UNIT}};' ) ) );
	}

	/* font complet (familie, mărime, grosime...) pe un element; la butoane și pe :hover / :focus, ca tema să nu-l schimbe */
	protected function pvl_typo( $name, $label, $selectors, $button = false ) {
		$sel = array();
		foreach ( (array) $selectors as $x ) {
			$sel[] = $x;
			if ( $button ) { $sel[] = $x . ':hover'; $sel[] = $x . ':focus'; }
		}
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => $name, 'label' => $label, 'selector' => implode( ', ', $sel ) ) );
	}

	private function pvl_pad( $id, $label, $scope, $var ) {
		$this->add_responsive_control( $id, array( 'label' => $label, 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px' ), 'selectors' => array( $scope => '--pvl-' . $var . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
	}

	/* culori pe stări (normal / hover / selectat): fundal, text, chenar */
	private function pvl_state_tabs( $id, $scope, $v, $states ) {
		$this->start_controls_tabs( $id . '_tabs' );
		foreach ( $states as $suf => $label ) {
			$k = str_replace( '-', '_', $suf );
			$this->start_controls_tab( $id . '_tab' . $k, array( 'label' => $label ) );
			$this->pvl_color( $id . '_bg' . $k, 'Fundal', $scope, $v . '-bg' . $suf );
			$this->pvl_color( $id . '_c' . $k, 'Text', $scope, $v . '-c' . $suf );
			$this->pvl_color( $id . '_bd' . $k, 'Culoare chenar', $scope, $v . '-bd' . $suf );
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();
	}

	/* etichetele „Portocaliu”, „6 cm” de sub numele produsului (în listă / în panou) */
	protected function pvl_tag_controls( $id, $scope ) {
		$this->pvl_color( $id . '_bg', 'Fundal', $scope, 'tag-bg' );
		$this->pvl_color( $id . '_c', 'Text', $scope, 'tag-c' );
		$this->pvl_color( $id . '_bd', 'Culoare chenar', $scope, 'tag-bd' );
		$this->pvl_size( $id . '_bw', 'Grosime chenar', $scope, 'tag-bw', 6 );
		$this->pvl_size( $id . '_r', 'Colțuri', $scope, 'tag-r', 40, array( 'px', '%' ) );
		$this->pvl_pad( $id . '_p', 'Padding', $scope, 'tag-p' );
		$this->pvl_fs( $id . '_fs', 'Mărime text', $scope, 'tag-fs' );
		$this->add_control( $id . '_fw', array( 'label' => 'Grosime text', 'type' => Controls_Manager::SELECT, 'options' => array( '' => 'Implicit', '400' => 'Normal', '500' => 'Medium', '600' => 'Semibold', '700' => 'Bold' ), 'selectors' => array( $scope => '--pvl-tag-fw: {{VALUE}};' ) ) );
		$this->pvl_typo( $id . '_typo', 'Font etichete (complet)', array( $scope . ' .pvl-var .pvl-var-c', $scope . ' .pvl-var .pvl-var-t' ) );
		$this->pvl_size( $id . '_gap', 'Spațiu între etichete', $scope, 'tag-gap', 24 );
		$this->add_control( $id . '_h_sw', array( 'label' => 'Poza culorii din etichetă', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->pvl_size( $id . '_sw', 'Mărime', $scope, 'tag-sw', 48 );
		$this->pvl_size( $id . '_sw_r', 'Formă (colțuri: 50% = cerc, 0 = pătrat)', $scope, 'tag-sw-r', 24, array( '%', 'px' ) );
		$this->pvl_color( $id . '_sw_bd', 'Culoare chenar', $scope, 'tag-sw-bd' );
		$this->pvl_size( $id . '_sw_bw', 'Grosime chenar', $scope, 'tag-sw-bw', 4 );
		$this->pvl_size( $id . '_psw', 'Spațiu în stânga pozei', $scope, 'tag-p-sw', 20 );
		$this->add_control( $id . '_h_un', array( 'label' => 'Eticheta „Nu am decis”', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->pvl_color( $id . '_un_bd', 'Culoare chenar punctat', $scope, 'tag-un-bd' );
		$this->pvl_color( $id . '_un_bg', 'Fundal', $scope, 'tag-un-bg' );
		$this->pvl_color( $id . '_un_c', 'Text', $scope, 'tag-un-c' );
	}

	/* cardurile de culoare și butoanele de grosime (fereastra „Alege varianta” + formularul) */
	protected function pvl_choice_controls( $id, $scope, $title_sel ) {
		$this->add_control( $id . '_h_lbl', array( 'label' => 'Titlurile „Culoare” / „Grosime”', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( $id . '_lbl_c', array( 'label' => 'Culoare', 'type' => Controls_Manager::COLOR, 'selectors' => array( $title_sel( '.pvl-pk-label' ) => 'color: {{VALUE}};' ) ) );
		$this->pvl_typo( $id . '_lbl_typo', 'Font', $title_sel( '.pvl-pk-label' ) );
		$this->add_control( $id . '_h_cc', array( 'label' => 'Culori (carduri cu poză)', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->pvl_state_tabs( $id . '_cc', $scope, 'cc', array( '' => 'Normal', '-h' => 'Hover', '-on' => 'Selectat' ) );
		$this->pvl_size( $id . '_cc_bw', 'Grosime chenar card', $scope, 'cc-bw', 6, array( 'px' ), array( 'separator' => 'before' ) );
		$this->pvl_size( $id . '_cc_r', 'Colțuri card', $scope, 'cc-r', 30 );
		$this->pvl_pad( $id . '_cc_p', 'Padding card', $scope, 'cc-p' );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => $id . '_cc_typo', 'label' => 'Font nume culoare', 'selector' => $title_sel( '.pvl-pk-cn' ) ) );
		$this->add_control( $id . '_h_sw', array( 'label' => 'Miniatura culorii', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->pvl_size( $id . '_sw_r', 'Formă (colțuri: 50% = cerc, 0 = pătrat)', $scope, 'sw-r', 40, array( 'px', '%' ) );
		$this->pvl_color( $id . '_sw_bd', 'Culoare chenar', $scope, 'sw-bd' );
		$this->pvl_color( $id . '_sw_bd_on', 'Culoare chenar (selectat)', $scope, 'sw-bd-on' );
		$this->pvl_size( $id . '_sw_bw', 'Grosime chenar', $scope, 'sw-bw', 6 );

		$this->add_control( $id . '_h_tp', array( 'label' => 'Grosimi (butoane text)', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->pvl_state_tabs( $id . '_tp', $scope, 'tp', array( '' => 'Normal', '-h' => 'Hover', '-on' => 'Selectat' ) );
		$this->pvl_size( $id . '_tp_bw', 'Grosime chenar', $scope, 'tp-bw', 6, array( 'px' ), array( 'separator' => 'before' ) );
		$this->pvl_size( $id . '_tp_r', 'Colțuri', $scope, 'tp-r', 40, array( 'px', '%' ) );
		$this->pvl_pad( $id . '_tp_p', 'Padding', $scope, 'tp-p' );
		$this->pvl_fs( $id . '_tp_fs', 'Mărime text', $scope, 'tp-fs' );
		$this->pvl_typo( $id . '_tp_typo', 'Font grosimi (complet)', array_map( function ( $x ) { return $x . ' button.pvl-pk-t'; }, array_map( 'trim', explode( ',', $scope ) ) ), true );

		$this->add_control( $id . '_h_un', array( 'label' => '„Nu am decis”', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->pvl_color( $id . '_un_bd', 'Culoare chenar punctat', $scope, 'un-bd' );
	}
}
