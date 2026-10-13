<?php
/**
 * Pavva Widgets – Buton ofertă
 *
 * „Adaugă la ofertă”: pune produsul în lista de ofertă a vizitatorului (vezi assets/oferta-lista.js).
 * Produsul: cel al paginii (șablonul de produs) sau unul ales din listă.
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
use Elementor\Group_Control_Box_Shadow;

class Pavva_Oferta_Buton_Widget extends Widget_Base {

	public function get_name() { return 'pavva_oferta_buton'; }
	public function get_title() { return 'Pavva – Buton ofertă'; }
	public function get_icon() { return 'eicon-button'; }
	public function get_categories() { return array( 'pavva' ); }
	public function get_keywords() { return array( 'oferta', 'ofertă', 'lista', 'adauga', 'produs', 'buton', 'pavva' ); }

	/* produsul depinde de pagină */
	protected function is_dynamic_content(): bool { return true; }

	private function is_edit_mode() {
		return class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	private function sel( $inner = '' ) {
		return '{{WRAPPER}} .pvl-btn' . ( $inner ? ' ' . $inner : '' );
	}

	protected function register_controls() {

		$this->start_controls_section( 'section_content', array( 'label' => 'Buton ofertă' ) );

		$this->add_control(
			'source',
			array(
				'label'   => 'Produs',
				'type'    => Controls_Manager::SELECT,
				'default' => 'current',
				'options' => array(
					'current' => 'Produsul paginii (șablonul de produs)',
					'pick'    => 'Aleg eu produsul',
				),
			)
		);

		$this->add_control(
			'product_id',
			array(
				'label'       => 'Alege produsul',
				'type'        => Controls_Manager::SELECT2,
				'options'     => class_exists( 'Pavva_W_Lista' ) ? Pavva_W_Lista::product_options() : array(),
				'label_block' => true,
				'condition'   => array( 'source' => 'pick' ),
			)
		);

		$this->add_control(
			'mode',
			array(
				'label'     => 'La click',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'add',
				'separator' => 'before',
				'options'   => array(
					'add' => 'Adaugă în listă (vizitatorul rămâne pe pagină)',
					'go'  => 'Adaugă și deschide formularul',
				),
			)
		);

		$this->add_control(
			'again',
			array(
				'label'     => 'Click pe un produs deja adăugat',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'go',
				'options'   => array(
					'go'     => 'Deschide formularul',
					'remove' => 'Îl scoate din listă',
				),
				'condition' => array( 'mode' => 'add' ),
			)
		);

		$this->add_control(
			'toast',
			array(
				'label'        => 'Mesaj după adăugare',
				'description'  => 'Jos pe ecran, câteva secunde: „Adăugat în ofertă: … · Vezi oferta”.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'mode' => 'add' ),
			)
		);

		$this->add_control(
			'form_url',
			array(
				'label'       => 'Pagina formularului',
				'type'        => Controls_Manager::TEXT,
				'default'     => '/solicita-oferta/',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control( 'texts_heading', array( 'label' => 'Texte și iconițe', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );

		$this->add_control(
			'text_add',
			array(
				'label'       => 'Text',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Adaugă la ofertă',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);
		$this->add_control(
			'text_added',
			array(
				'label'       => 'Text după adăugare',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Adăugat în ofertă',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);
		$this->add_control(
			'icon_add',
			array(
				'label'   => 'Iconiță',
				'type'    => Controls_Manager::ICONS,
				'default' => array( 'value' => 'fas fa-plus', 'library' => 'fa-solid' ),
			)
		);
		$this->add_control(
			'icon_added',
			array(
				'label'   => 'Iconiță după adăugare',
				'type'    => Controls_Manager::ICONS,
				'default' => array( 'value' => 'fas fa-check', 'library' => 'fa-solid' ),
			)
		);
		$this->add_control(
			'icon_position',
			array(
				'label'   => 'Poziția iconiței',
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'before',
				'toggle'  => false,
				'options' => array(
					'before' => array( 'title' => 'Înainte', 'icon' => 'eicon-h-align-left' ),
					'after'  => array( 'title' => 'După', 'icon' => 'eicon-h-align-right' ),
				),
			)
		);

		$this->end_controls_section();

		/* ---------- Stil ---------- */
		$this->start_controls_section( 'section_style', array( 'label' => 'Buton', 'tab' => Controls_Manager::TAB_STYLE ) );

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
				'selectors' => array( '{{WRAPPER}} .pvl-btn-wrap' => 'justify-content: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'full',
			array(
				'label'        => 'Toată lățimea',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => '100%',
				'selectors'    => array( $this->sel() => 'width: {{VALUE}};' ),
			)
		);

		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'typo', 'selector' => $this->sel() ) );

		$this->add_responsive_control(
			'padding',
			array(
				'label'      => 'Spațiu interior',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
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

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'shadow', 'selector' => $this->sel() ) );

		$this->start_controls_tabs( 'tabs_colors' );
		foreach ( array(
			'normal' => array( 'Normal', '' ),
			'hover'  => array( 'Hover', ':hover' ),
			'added'  => array( 'Adăugat', '.is-added' ),
		) as $k => $t ) {
			$this->start_controls_tab( 'tab_' . $k, array( 'label' => $t[0] ) );
			/* variabile (vezi oferta-lista.css): așa tema nu mai poate colora butonul la click */
			$s   = '{{WRAPPER}} .pvl-btn';
			$sfx = array( 'normal' => '', 'hover' => '-h', 'added' => '-a' )[ $k ];
			$this->add_control( 'color_' . $k, array( 'label' => 'Culoare text', 'type' => Controls_Manager::COLOR, 'selectors' => array( $s => '--pvl-c' . $sfx . ': {{VALUE}};' ) ) );
			$this->add_control( 'bg_' . $k, array( 'label' => 'Fundal', 'type' => Controls_Manager::COLOR, 'selectors' => array( $s => '--pvl-bg' . $sfx . ': {{VALUE}};' ) ) );
			$this->add_control( 'bd_' . $k, array( 'label' => 'Culoare chenar', 'type' => Controls_Manager::COLOR, 'selectors' => array( $s => '--pvl-bd' . $sfx . ': {{VALUE}};' ) ) );
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();

		$this->add_control( 'icon_heading', array( 'label' => 'Iconiță', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_responsive_control(
			'icon_size',
			array(
				'label'      => 'Mărime',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 60 ) ),
				'selectors'  => array( $this->sel( '.pvl-ico' ) => 'font-size: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'icon_gap',
			array(
				'label'      => 'Distanța față de text',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array( $this->sel() => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function product_item( array $s ): array {
		if ( ! class_exists( 'Pavva_W_Lista' ) ) return array();
		if ( 'pick' === ( $s['source'] ?? 'current' ) ) return Pavva_W_Lista::item( absint( $s['product_id'] ?? 0 ) );
		$pid = class_exists( 'Pavva_W_Data' ) ? (int) Pavva_W_Data::resolve_product_id() : 0;
		return Pavva_W_Lista::item( $pid );
	}

	private function icon( $icon, $class ) {
		if ( empty( $icon['value'] ) ) return;
		echo '<span class="pvl-ico ' . esc_attr( $class ) . '" aria-hidden="true">';
		Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) );
		echo '</span>';
	}

	protected function render() {
		$s    = $this->get_settings_for_display();
		$item = $this->product_item( $s );
		$edit = $this->is_edit_mode();
		if ( ! $item ) {
			if ( ! $edit ) return;
			/* în editor (șablon fără produs de previzualizare): un exemplu, ca să se poată stiliza */
			$item = array( 'id' => 0, 'name' => 'Produs exemplu', 'cat' => '', 'url' => '', 'img' => '' );
		}
		$mode  = 'go' === ( $s['mode'] ?? 'add' ) ? 'go' : 'add';
		$again = 'remove' === ( $s['again'] ?? 'go' ) ? 'remove' : 'go';
		$form  = Pavva_W_Lista::resolve_url( $s['form_url'] ?? '' );
		$after = 'after' === ( $s['icon_position'] ?? 'before' );
		$add   = trim( (string) ( $s['text_add'] ?? '' ) );
		$done  = trim( (string) ( $s['text_added'] ?? '' ) );
		?>
		<div class="pvl-btn-wrap">
			<button type="button" class="pvl-btn" aria-pressed="false"
				data-pvl-add data-pvl="<?php echo esc_attr( wp_json_encode( $item ) ); ?>"
				data-pvl-mode="<?php echo esc_attr( $mode ); ?>" data-pvl-again="<?php echo esc_attr( $again ); ?>"
				data-pvl-form="<?php echo esc_url( $form ); ?>" data-pvl-toast="<?php echo 'yes' === ( $s['toast'] ?? 'yes' ) ? '1' : '0'; ?>">
				<?php
				if ( ! $after ) { $this->icon( $s['icon_add'] ?? array(), 'pvl-ico-add' ); $this->icon( $s['icon_added'] ?? array(), 'pvl-ico-added' ); }
				if ( '' !== $add ) echo '<span class="pvl-t pvl-t-add">' . esc_html( $add ) . '</span>';
				echo '<span class="pvl-t pvl-t-added">' . esc_html( '' !== $done ? $done : $add ) . '</span>';
				if ( $after ) { $this->icon( $s['icon_add'] ?? array(), 'pvl-ico-add' ); $this->icon( $s['icon_added'] ?? array(), 'pvl-ico-added' ); }
				if ( '' === $add ) echo '<span class="pvl-sr">' . esc_html( 'Adaugă la ofertă: ' . $item['name'] ) . '</span>';
				?>
			</button>
		</div>
		<?php
	}
}
