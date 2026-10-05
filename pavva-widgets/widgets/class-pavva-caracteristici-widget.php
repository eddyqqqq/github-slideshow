<?php
/**
 * Pavva Widgets – Caracteristici
 *
 * Iconițele din câmpul „Caracteristici” al produsului curent
 * (doar cele bifate „Afișează în pagina produsului”).
 * Aceleași iconițe le poate afișa și Slider header, deasupra descrierii.
 *
 * @package Pavva_Widgets
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

class Pavva_Caracteristici_Widget extends Widget_Base {

	public function get_name() {
		return 'pavva_caracteristici';
	}

	public function get_title() {
		return 'Pavva – Caracteristici';
	}

	public function get_icon() {
		return 'eicon-icon-box';
	}

	public function get_categories() {
		return array( 'pavva' );
	}

	public function get_keywords() {
		return array( 'caracteristici', 'iconite', 'iconițe', 'garantie', 'produs', 'pavva' );
	}

	public function get_style_depends() {
		return array( 'pavva-caracteristici', 'pavva-sectiuni-goale' );
	}

	public function get_script_depends() {
		return array( 'pavva-sectiuni-goale' );
	}

	/* Conținutul depinde de produsul paginii: Elementor nu are voie să-l țină în cache. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/* Fără caracteristici: widgetul primește o clasă care ascunde containerul (vezi sectiuni-goale.css). */
	protected function get_html_wrapper_class(): string {
		$class = parent::get_html_wrapper_class();
		if ( ! $this->is_edit_mode() && ! $this->icons_for_page( $this->get_settings_for_display() ) ) {
			$class .= ' pavva-w-is-empty';
		}
		return $class;
	}

	private function sel( $inner = '' ) {
		return '{{WRAPPER}} .pvk' . ( $inner ? ' ' . $inner : '' );
	}

	protected function register_controls() {

		/* ---------- Conținut ---------- */
		$this->start_controls_section(
			'section_content',
			array(
				'label' => 'Caracteristici',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'info',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'Iconițele se iau din produs: <strong>Caracteristici</strong>. Apar doar cele bifate „Afișează în pagina produsului”.',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control(
			'max',
			array(
				'label'       => 'Număr maxim de iconițe',
				'description' => '0 = toate.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 30,
				'default'     => 0,
			)
		);

		$this->add_control(
			'show_labels',
			array(
				'label'        => 'Afișează denumirea',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_responsive_control(
			'label_position',
			array(
				'label'                => 'Poziție denumire',
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'below',
				'options'              => array(
					'below' => 'Sub iconiță',
					'right' => 'În dreapta iconiței',
				),
				'selectors_dictionary' => array(
					'below' => 'flex-direction: column; align-items: center; text-align: center;',
					'right' => 'flex-direction: row; align-items: center; text-align: left;',
				),
				'selectors'            => array(
					$this->sel( '.pvk-item' ) => '{{VALUE}}',
				),
				'condition'            => array( 'show_labels' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => 'Aliniere',
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start'    => array( 'title' => 'Stânga', 'icon' => 'eicon-h-align-left' ),
					'center'        => array( 'title' => 'Centru', 'icon' => 'eicon-h-align-center' ),
					'flex-end'      => array( 'title' => 'Dreapta', 'icon' => 'eicon-h-align-right' ),
					'space-between' => array( 'title' => 'Distribuite', 'icon' => 'eicon-h-align-stretch' ),
				),
				'selectors' => array(
					$this->sel() => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		/* ---------- Stil ---------- */
		$this->start_controls_section(
			'section_style',
			array(
				'label' => 'Iconițe',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'icon_height',
			array(
				'label'       => 'Înălțime iconiță',
				'description' => 'Implicit 52px (44px pe mobil), ca în Slider header.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem' ),
				'range'       => array( 'px' => array( 'min' => 12, 'max' => 200 ) ),
				'selectors'   => array(
					$this->sel() => '--pvk-h: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'color_mode',
			array(
				'label' => 'Colorare iconițe SVG',
				'type' => Controls_Manager::SELECT,
				'description' => 'Pentru iconițele SVG. PNG / JPG: folosește „Filtre iconiță” sau încarcă varianta colorată.',
				'options' => array( 'original' => 'Culorile originale', 'fill' => 'Colorează umplerea', 'stroke' => 'Colorează conturul', 'both' => 'Umplere + contur' ),
				'default' => 'original',
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label' => 'Culoare iconițe',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvk-item' ) => 'color: {{VALUE}};',
				),
				'condition' => array( 'color_mode!' => 'original' ),
			)
		);

		$this->add_control(
			'icon_color_hover',
			array(
				'label' => 'Culoare iconițe (hover)',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvk-item:hover' ) => 'color: {{VALUE}};',
				),
				'condition' => array( 'color_mode!' => 'original' ),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => 'Spațiu între iconițe',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'selectors'  => array(
					$this->sel() => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'opacity',
			array(
				'label'     => 'Opacitate',
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0.1, 'max' => 1, 'step' => 0.05 ) ),
				'selectors' => array(
					$this->sel( '.pvk-img' ) => 'opacity: {{SIZE}};',
					$this->sel( '.pvk-svg' ) => 'opacity: {{SIZE}};',
				),
			)
		);

		$this->add_control(
			'opacity_hover',
			array(
				'label' => 'Opacitate la hover',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 0.1, 'max' => 1, 'step' => 0.05 ) ),
				'selectors' => array(
					$this->sel( '.pvk-item:hover .pvk-img, {{WRAPPER}} .pvk .pvk-item:hover .pvk-svg' ) => 'opacity: {{SIZE}};',
				),
			)
		);

		$this->add_control(
			'scale_hover',
			array(
				'label' => 'Mărire la hover',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range' => array( 'px' => array( 'min' => 1, 'max' => 1.4, 'step' => 0.01 ) ),
				'selectors' => array(
					$this->sel( '.pvk-item:hover .pvk-img, {{WRAPPER}} .pvk .pvk-item:hover .pvk-svg' ) => 'transform: scale({{SIZE}});',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name' => 'img_filter',
				'label' => 'Filtre iconiță (ex.: alb-negru)',
				'selector' => $this->sel( '.pvk-img, {{WRAPPER}} .pvk .pvk-svg' ),
			)
		);

		$this->add_control(
			'item_box_heading',
			array(
				'label' => 'Chenar fiecare iconiță',
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'item_padding',
			array(
				'label' => 'Padding',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors' => array(
					$this->sel( '.pvk-item' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'item_bg',
			array(
				'label' => 'Fundal',
				'type' => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.pvk-item' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'item_radius',
			array(
				'label' => 'Colțuri rotunjite (fiecare colț)',
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors' => array(
					$this->sel( '.pvk-item' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name' => 'item_border',
				'label' => 'Bordură',
				'selector' => $this->sel( '.pvk-item' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name' => 'item_shadow',
				'label' => 'Umbră',
				'selector' => $this->sel( '.pvk-item' ),
			)
		);

		$this->add_control(
			'label_heading',
			array(
				'label'     => 'Denumire',
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array( 'show_labels' => 'yes' ),
			)
		);

		$this->add_control(
			'label_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'show_labels' => 'yes' ),
				'selectors' => array(
					$this->sel( '.pvk-label' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'label_typo',
				'selector'  => $this->sel( '.pvk-label' ),
				'condition' => array( 'show_labels' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'label_gap',
			array(
				'label'      => 'Spațiu iconiță – denumire',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'condition'  => array( 'show_labels' => 'yes' ),
				'selectors'  => array(
					$this->sel( '.pvk-item' ) => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name' => 'label_tshadow',
				'label' => 'Umbră text',
				'selector' => $this->sel( '.pvk-label' ),
				'condition' => array( 'show_labels' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'label_max',
			array(
				'label' => 'Lățime maximă denumire',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range' => array( 'px' => array( 'min' => 40, 'max' => 400, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.pvk-label' ) => 'max-width: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'show_labels' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function is_edit_mode() {
		return class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	/** @var array|null Iconițele produsului paginii (calculate o singură dată). */
	private $icons_cache = null;

	private function icons_for_page( $s ) {
		if ( null !== $this->icons_cache ) {
			return $this->icons_cache;
		}
		$list = array();
		foreach ( Pavva_W_Data::visible_items( Pavva_W_Data::instance_data( Pavva_W_Data::META_CARACT ) ) as $it ) {
			$id = ! empty( $it['icon_id'] ) ? absint( $it['icon_id'] ) : 0;
			if ( ! $id || ! wp_get_attachment_image_url( $id, 'thumbnail' ) ) {
				continue;
			}
			$list[] = array(
				'id'    => $id,
				'label' => isset( $it['denumire'] ) ? (string) $it['denumire'] : '',
			);
		}
		$max = isset( $s['max'] ) ? absint( $s['max'] ) : 0;
		if ( $max > 0 ) {
			$list = array_slice( $list, 0, $max );
		}
		$this->icons_cache = $list;
		return $list;
	}

	/* Iconiță SVG pusă direct în pagină (colorabilă). '' dacă fișierul nu e SVG. */
	private function inline_svg( $id, $label ) {
		$url  = (string) wp_get_attachment_url( $id );
		$path = $url ? wp_parse_url( $url, PHP_URL_PATH ) : '';
		if ( ! $path || 'svg' !== strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) ) {
			return '';
		}
		ob_start();
		Icons_Manager::render_icon( array( 'library' => 'svg', 'value' => array( 'id' => (int) $id, 'url' => $url ) ), array( 'aria-hidden' => 'true' ) );
		$svg = trim( ob_get_clean() );
		if ( '' === $svg || false === stripos( $svg, '<svg' ) ) {
			return '';
		}
		return '<span class="pvk-svg" role="img"' . ( '' !== $label ? ' aria-label="' . esc_attr( $label ) . '"' : ' aria-hidden="true"' ) . '>' . $svg . '</span>';
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$icons = $this->icons_for_page( $s );

		if ( ! $icons ) {
			if ( $this->is_edit_mode() ) {
				echo '<div class="pavva-editor-notice" style="padding:16px 18px;border:1px dashed #c9c4bb;border-radius:6px;background:#faf8f5;color:#6b6964;font-size:13px;line-height:1.5;"><strong>Caracteristici:</strong> '
					. ( Pavva_W_Data::resolve_product_id() ? 'produsul acesta nu are caracteristici bifate „Afișează în pagina produsului”. Pe site containerul se ascunde.' : 'nu găsesc produsul. În template, alege un produs la <em>rotița Setări → Preview Settings</em>.' )
					. '</div>';
			} else {
				echo '<span class="pavva-w-empty" hidden></span>';
			}
			return;
		}

		$labels = isset( $s['show_labels'] ) && 'yes' === $s['show_labels'];
		$mode   = isset( $s['color_mode'] ) && in_array( $s['color_mode'], array( 'original', 'fill', 'stroke', 'both' ), true ) ? $s['color_mode'] : 'original';
		?>
		<div class="pvk pvk-color-<?php echo esc_attr( $mode ); ?>">
			<?php foreach ( $icons as $ic ) : ?>
				<?php $alt = wp_strip_all_tags( $ic['label'] ); ?>
				<span class="pvk-item">
					<?php
					$svg = $this->inline_svg( $ic['id'], $alt );
					echo '' !== $svg ? $svg : wp_get_attachment_image( // phpcs:ignore -- HTML generat de WordPress / SVG prin Elementor
						$ic['id'],
						'medium',
						false,
						array(
							'class'    => 'pvk-img',
							'alt'      => $alt,
							'loading'  => 'lazy',
							'decoding' => 'async',
						)
					);
					?>
					<?php if ( $labels && '' !== $alt ) : ?>
						<span class="pvk-label"><?php echo esc_html( $alt ); ?></span>
					<?php endif; ?>
				</span>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
