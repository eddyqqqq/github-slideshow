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

class Pavva_Oferta_Lista_Widget extends Widget_Base {

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

		$this->add_control( 'rm_heading', array( 'label' => 'Butonul de ștergere', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'rm_color', array( 'label' => 'Culoare', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel( '.pvl-remove' ) => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'rm_color_hover', array( 'label' => 'Culoare la hover', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel( '.pvl-remove:hover' ) => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'rm_bg_hover', array( 'label' => 'Fundal la hover', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel( '.pvl-remove:hover' ) => 'background-color: {{VALUE}};' ) ) );
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
			foreach ( array_slice( array_keys( Pavva_W_Lista::product_options() ), 0, 2 ) as $pid ) $sample[] = Pavva_W_Lista::item( (int) $pid );
		}
		$classes = 'pvl-list' . ( $edit ? '' : ' is-empty' ) . ( '' === $empty ? ' pvl-hide-empty' : '' );
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" data-pvl-list data-pvl-img="<?php echo $img ? '1' : '0'; ?>" data-pvl-cat="<?php echo $cat ? '1' : '0'; ?>"<?php echo $edit ? ' data-pvl-preview' : ''; ?>>
			<?php if ( '' !== $title ) : ?>
				<<?php echo $tag; // phpcs:ignore ?> class="pvl-title"><?php echo esc_html( $title ); ?><?php if ( 'yes' === ( $s['title_count'] ?? 'yes' ) ) : ?><span class="pvl-title-count"><?php echo $edit && $sample ? ' (' . count( $sample ) . ')' : ''; ?></span><?php endif; ?></<?php echo $tag; // phpcs:ignore ?>>
			<?php endif; ?>
			<ul class="pvl-items">
				<?php foreach ( $sample as $it ) : ?>
					<li class="pvl-item">
						<?php if ( $img ) : ?><span class="pvl-media"><?php if ( $it['img'] ) : ?><img src="<?php echo esc_url( $it['img'] ); ?>" alt=""><?php endif; ?></span><?php endif; ?>
						<span class="pvl-info"><span class="pvl-name"><?php echo esc_html( $it['name'] ); ?></span><?php if ( $cat && $it['cat'] ) : ?><span class="pvl-cat"><?php echo esc_html( $it['cat'] ); ?></span><?php endif; ?></span>
						<button type="button" class="pvl-remove" aria-label="Scoate din ofertă"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg></button>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( '' !== $empty ) : ?><p class="pvl-empty"><?php echo esc_html( $empty ); ?></p><?php endif; ?>
			<?php if ( 'yes' === ( $s['show_clear'] ?? '' ) ) : ?><button type="button" class="pvl-clear" data-pvl-clear><?php echo esc_html( $s['clear_text'] ?? 'Golește lista' ); ?></button><?php endif; ?>
		</div>
		<?php
	}
}
