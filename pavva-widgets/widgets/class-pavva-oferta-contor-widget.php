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
		$this->add_control( 'bar_x_color', array( 'label' => 'Culoare', 'type' => Controls_Manager::COLOR, 'selectors' => array( 'body .pvl-toast .pvl-toast-x' => 'color: {{VALUE}}; opacity: 1;' ) ) );
		$this->add_control( 'bar_x_color_h', array( 'label' => 'Culoare la hover', 'type' => Controls_Manager::COLOR, 'selectors' => array( 'body .pvl-toast .pvl-toast-x:hover, body .pvl-toast .pvl-toast-x:focus-visible' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'bar_x_bg_h', array( 'label' => 'Fundal la hover', 'type' => Controls_Manager::COLOR, 'selectors' => array( 'body .pvl-toast .pvl-toast-x:hover, body .pvl-toast .pvl-toast-x:focus-visible' => 'background-color: {{VALUE}};' ) ) );
		$this->add_responsive_control(
			'bar_x_size',
			array(
				'label'      => 'Mărime ×',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 32 ) ),
				'selectors'  => array( 'body .pvl-toast .pvl-toast-x svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();
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
				data-pvl-label="<?php echo esc_attr( '' !== $label ? $label : 'Oferta ta' ); ?>"<?php echo $hide ? ' data-pvl-hide-empty' : ''; ?><?php echo $edit ? ' data-pvl-preview' : ''; ?>
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
	}
}
