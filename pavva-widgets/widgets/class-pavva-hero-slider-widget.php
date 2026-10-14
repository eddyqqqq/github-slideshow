<?php
/**
 * Pavva Widgets – Slider header
 *
 * Slider de imagini cu tranziții line, săgeți SVG proprii și text sub slider
 * (fix sau schimbat odată cu slide-ul).
 *
 * @package Pavva_Widgets
 * @author  Imagient <https://imagient.ro/>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Css_Filter;
use Elementor\Icons_Manager;
use Elementor\Utils;

class Pavva_Hero_Slider_Widget extends Widget_Base {

	public function get_name() {
		return 'pavva_hero_slider';
	}

	public function get_title() {
		return 'Pavva – Slider header';
	}

	public function get_icon() {
		return 'eicon-slides';
	}

	public function get_categories() {
		return array( 'pavva' );
	}

	public function get_keywords() {
		return array( 'slider', 'header', 'hero', 'carusel', 'imagini', 'pavva' );
	}

	public function get_style_depends() {
		return array( 'pavva-hero-slider' );
	}

	public function get_script_depends() {
		return array( 'pavva-hero-slider' );
	}

	/* Toți selectorii pornesc din .ps-hero, ca să bată stilurile globale ale temei. */
	private function sel( $inner = '' ) {
		return '{{WRAPPER}} .ps-hero' . ( $inner ? ' ' . $inner : '' );
	}

	protected function register_controls() {
		$this->content_slides();
		$this->content_text();
		$this->content_extras();
		$this->content_arrows();
		$this->content_pagination();
		$this->content_settings();

		$this->style_frame();
		$this->style_slider();
		$this->style_arrows();
		$this->style_pagination();
		$this->style_text_block();
		$this->style_title();
		$this->style_desc();
		$this->style_extras();
		$this->style_text_animation();
	}

	/* =====================================================================
	 * CONTENT – Slide-uri
	 * ===================================================================== */
	private function content_slides() {
		$this->start_controls_section(
			'section_slides',
			array(
				'label' => 'Slide-uri',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'slides_source',
			array(
				'label'       => 'Sursă imagini',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'manual',
				'options'     => array(
					'manual'   => 'Alese manual (static)',
					'product'  => 'Din produs (template produs)',
					'category' => 'Din categorie (template categorie)',
				),
				'description' => '„Alese manual” = slide-urile de mai jos, pentru orice pagină. Din produs = galeria WooCommerce a produsului (fără imaginea principală). Din categorie = câmpul „Imagini slider header” al categoriei.',
			)
		);

		$this->add_control(
			'slides_source_info',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'La produs: <strong>galeria WooCommerce</strong> (fără imaginea principală), în ordinea din galerie. La categorie: câmpul <strong>Imagini slider header</strong> al categoriei; dacă e gol, Imagine categorie. Dacă nu există nicio imagine, apar <strong>slide-urile manuale de mai jos</strong>, ca rezervă.',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition'       => array( 'slides_source!' => 'manual' ),
			)
		);


		$this->add_control(
			'slides_source_max',
			array(
				'label'       => 'Număr maxim de imagini',
				'description' => '0 = toate.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 30,
				'default'     => 0,
				'condition'   => array( 'slides_source!' => 'manual' ),
			)
		);

		$this->add_control(
			'slides_source_position',
			array(
				'label'     => 'Punctul de focus al imaginilor',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => $this->position_options(),
				'condition' => array( 'slides_source!' => 'manual' ),
				'selectors' => array(
					'{{WRAPPER}} .ps-hero .ps-slide-dyn .ps-img' => 'object-position: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'slides_source_bg',
			array(
				'label'     => 'Culoare fundal (cât se încarcă imaginea)',
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'slides_source!' => 'manual' ),
				'selectors' => array(
					'{{WRAPPER}} .ps-hero .ps-slide-dyn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$r = new Repeater();

		$r->add_control(
			'slide_image',
			array(
				'label'       => 'Imagine',
				'description' => 'Fără imagine, slide-ul afișează culoarea de fundal de mai jos.',
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$r->add_control(
			'slide_image_tablet',
			array(
				'label'       => 'Imagine pentru tabletă (opțional)',
				'description' => 'Dacă vrei altă încadrare pe tabletă. Gol = se folosește imaginea principală.',
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
			)
		);

		$r->add_control(
			'slide_image_mobile',
			array(
				'label'       => 'Imagine pentru mobil (opțional)',
				'description' => 'Ideal o variantă verticală a imaginii. Gol = se folosește imaginea principală.',
				'type'        => Controls_Manager::MEDIA,
				'default'     => array( 'url' => '' ),
			)
		);

		$r->add_control(
			'slide_bg',
			array(
				'label'       => 'Culoare fundal',
				'description' => 'Placeholder cât timp nu ai imagine, sau fundal în spatele unei imagini transparente.',
				'type'        => Controls_Manager::COLOR,
				'selectors'   => array(
					'{{WRAPPER}} .ps-hero {{CURRENT_ITEM}}' => 'background-color: {{VALUE}};',
				),
			)
		);

		$r->add_control(
			'slide_position',
			array(
				'label'     => 'Punctul de focus al imaginii',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => $this->position_options(),
				'selectors' => array(
					'{{WRAPPER}} .ps-hero {{CURRENT_ITEM}} .ps-img' => 'object-position: {{VALUE}};',
				),
			)
		);

		$r->add_control(
			'slide_alt',
			array(
				'label'       => 'Text alternativ (alt)',
				'description' => 'Pentru SEO și accesibilitate. Gol = se folosește textul alt din Media Library.',
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$r->add_control(
			'slide_link',
			array(
				'label'       => 'Link pe imagine (opțional)',
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://pavva.ro/...',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$r->add_control(
			'slide_text_heading',
			array(
				'label'     => 'Text pentru acest slide',
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$r->add_control(
			'slide_text_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'Se folosește doar dacă la <strong>Text sub slider</strong> ai ales „Se schimbă odată cu slide-ul”. Câmpurile goale preiau textul general.',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$r->add_control(
			'slide_title',
			array(
				'label'   => 'Titlu',
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 2,
				'dynamic' => array( 'active' => true ),
			)
		);

		$r->add_control(
			'slide_desc',
			array(
				'label'   => 'Descriere',
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'slides',
			array(
				'label'       => 'Slide-uri',
				'description' => 'La „Din produs / Din categorie” aceste slide-uri sunt rezerva, dacă pagina nu are imagini.',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $r->get_controls(),
				'title_field' => '{{{ slide_title ? slide_title : "Slide" }}}',
				'default'     => array(
					array(
						'slide_bg'    => '#b0552a',
						'slide_title' => "Fabricăm pavaje\ncu avantaje.",
						'slide_desc'  => 'Producem pavaje și produse din beton vibropresat în Constanța. Oferim produse de calitate superioară și livrare rapidă pentru amenajări exterioare reușite și durabile.',
					),
					array(
						'slide_bg'    => '#9a4a24',
						'slide_title' => "Curți frumoase,\nconstruite să dureze.",
						'slide_desc'  => 'Pavele rezistente la trafic, îngheț și timp, în formate și culori care se potrivesc oricărei case sau grădini.',
					),
					array(
						'slide_bg'    => '#c2693b',
						'slide_title' => "Totul pentru exterior,\ndintr-un singur loc.",
						'slide_desc'  => 'Pavele, dale, borduri, rigole și bolțari pentru alei, terase, parcări și curți. Te ajutăm să alegi soluția potrivită proiectului tău.',
					),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'image',
				'default'   => 'full',
				'exclude'   => array( 'custom' ),
				'separator' => 'before',
			)
		);

		$this->end_controls_section();
	}

	/* =====================================================================
	 * CONTENT – Text sub slider
	 * ===================================================================== */
	private function content_text() {
		$this->start_controls_section(
			'section_text',
			array(
				'label' => 'Text sub slider',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_text',
			array(
				'label'        => 'Afișează textul',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'text_mode',
			array(
				'label'     => 'Comportament text',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'slides',
				'options'   => array(
					'static' => 'Fix – același text pe toate slide-urile',
					'slides' => 'Se schimbă odată cu slide-ul',
				),
				'condition' => array( 'show_text' => 'yes' ),
			)
		);

		$this->add_control(
			'text_mode_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'Textul pentru fiecare slide îl completezi în <strong>Slide-uri</strong>, la fiecare slide. Unde lași gol, apare textul general de mai jos.',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition'       => array(
					'show_text' => 'yes',
					'text_mode' => 'slides',
				),
			)
		);

		$this->add_control(
			'static_title',
			array(
				'label'       => 'Titlu general',
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => "Fabricăm pavaje\ncu avantaje.",
				'description' => 'Enter = rând nou.',
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_text' => 'yes' ),
			)
		);

		$this->add_control(
			'static_desc',
			array(
				'label'     => 'Descriere generală',
				'type'      => Controls_Manager::TEXTAREA,
				'rows'      => 5,
				'default'   => 'Producem pavaje și produse din beton vibropresat în Constanța. Oferim produse de calitate superioară și livrare rapidă pentru amenajări exterioare reușite și durabile.',
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_text' => 'yes' ),
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label'        => 'Afișează titlul',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
				'condition'    => array( 'show_text' => 'yes' ),
			)
		);

		$this->add_control(
			'show_desc',
			array(
				'label'        => 'Afișează descrierea',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'show_text' => 'yes' ),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'     => 'Tag HTML titlu',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h1',
				'options'   => $this->tag_options(),
				'condition' => array( 'show_text' => 'yes' ),
			)
		);

		$this->add_control(
			'desc_tag',
			array(
				'label'     => 'Tag HTML descriere',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'p',
				'options'   => array(
					'p'    => 'p',
					'div'  => 'div',
					'span' => 'span',
				),
				'condition' => array( 'show_text' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/* =====================================================================
	 * CONTENT – Iconițe și buton (în coloana descrierii)
	 * ===================================================================== */
	private function content_extras() {
		$this->start_controls_section(
			'section_extras',
			array(
				'label'     => 'Iconițe și buton',
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array( 'show_text' => 'yes' ),
			)
		);

		$this->add_control(
			'icons_source',
			array(
				'label'       => 'Iconițe deasupra descrierii',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'none',
				'options'     => array(
					'none'    => 'Fără',
					'product' => 'Din produs – Caracteristici (doar cele bifate)',
					'manual'  => 'Alese manual',
				),
				'description' => '„Din produs” citește câmpul Caracteristici al produsului curent: apar doar elementele bifate „Afișează în pagina produsului”.',
			)
		);

		$r = new Repeater();
		$r->add_control(
			'icon_image',
			array(
				'label'   => 'Iconiță (imagine / SVG)',
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => '' ),
			)
		);
		$r->add_control(
			'icon_label',
			array(
				'label'       => 'Denumire',
				'description' => 'Folosită ca text alternativ; o poți afișa și sub iconiță.',
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);
		$this->add_control(
			'icons_manual',
			array(
				'label'       => 'Iconițe',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $r->get_controls(),
				'title_field' => '{{{ icon_label ? icon_label : "Iconiță" }}}',
				'condition'   => array( 'icons_source' => 'manual' ),
			)
		);

		$this->add_control(
			'icons_max',
			array(
				'label'       => 'Număr maxim de iconițe',
				'description' => '0 = toate.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 20,
				'default'     => 0,
				'condition'   => array( 'icons_source!' => 'none' ),
			)
		);

		$this->add_control(
			'icons_labels',
			array(
				'label'        => 'Afișează denumirea sub iconiță',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array( 'icons_source!' => 'none' ),
			)
		);

		$this->add_control(
			'show_cta',
			array(
				'label'        => 'Buton sub descriere',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'cta_text',
			array(
				'label'     => 'Text buton',
				'type'      => Controls_Manager::TEXT,
				'default'   => 'Solicită ofertă',
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_cta' => 'yes' ),
			)
		);

		$this->add_control(
			'cta_link',
			array(
				'label'       => 'Link buton',
				'description' => 'Sfat: la iconița de date dinamice alege „Pavva → Link «Solicită ofertă»”, ca formularul să primească automat numele produsului / categoriei.',
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
				'default'     => array( 'url' => '#' ),
				'condition'   => array( 'show_cta' => 'yes' ),
			)
		);

		/* lista de ofertă: al doilea buton, lângă „Solicită ofertă” */
		$this->add_control(
			'show_oferta',
			array(
				'label'        => 'Buton „Adaugă la ofertă”',
				'description'  => 'Pune produsul paginii în lista de ofertă (clientul poate aduna mai multe produse și le trimite odată). Apare doar pe paginile de produs.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
				'separator'    => 'before',
			)
		);
		$this->add_control(
			'oferta_text',
			array(
				'label'     => 'Text',
				'type'      => Controls_Manager::TEXT,
				'default'   => 'Adaugă la ofertă',
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_oferta' => 'yes' ),
			)
		);
		$this->add_control(
			'oferta_text_added',
			array(
				'label'     => 'Text după adăugare',
				'type'      => Controls_Manager::TEXT,
				'default'   => 'Adăugat în ofertă',
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_oferta' => 'yes' ),
			)
		);
		$this->add_control(
			'oferta_icon',
			array(
				'label'        => 'Iconiță + / ✓',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'show_oferta' => 'yes' ),
			)
		);
		$this->add_control(
			'oferta_order',
			array(
				'label'     => 'Poziție',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'after',
				'options'   => array(
					'after'  => 'După „Solicită ofertă”',
					'before' => 'Înainte de „Solicită ofertă”',
				),
				'condition' => array( 'show_oferta' => 'yes', 'show_cta' => 'yes' ),
			)
		);
		$this->add_control(
			'oferta_mode',
			array(
				'label'     => 'La click',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'add',
				'options'   => array(
					'add' => 'Adaugă în listă (rămâne pe pagină)',
					'go'  => 'Adaugă și deschide formularul',
				),
				'condition' => array( 'show_oferta' => 'yes' ),
			)
		);
		$this->add_control(
			'oferta_again',
			array(
				'label'     => 'Click pe un produs deja adăugat',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'go',
				'options'   => array(
					'go'     => 'Deschide formularul',
					'remove' => 'Îl scoate din listă',
				),
				'condition' => array( 'show_oferta' => 'yes', 'oferta_mode' => 'add' ),
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
				'condition'    => array( 'show_oferta' => 'yes', 'oferta_mode' => 'add' ),
			)
		);
		$this->add_control(
			'oferta_form',
			array(
				'label'       => 'Pagina formularului',
				'type'        => Controls_Manager::TEXT,
				'default'     => '/solicita-oferta/',
				'label_block' => true,
				'condition'   => array( 'show_oferta' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/* =====================================================================
	 * STYLE – Iconițe și buton
	 * ===================================================================== */
	private function style_extras() {
		$this->start_controls_section(
			'section_style_extras',
			array(
				'label'      => 'Iconițe și buton',
				'tab'        => Controls_Manager::TAB_STYLE,
				'conditions' => array(
					'relation' => 'or',
					'terms'    => array(
						array( 'name' => 'icons_source', 'operator' => '!==', 'value' => 'none' ),
						array( 'name' => 'show_cta', 'operator' => '===', 'value' => 'yes' ),
						array( 'name' => 'show_oferta', 'operator' => '===', 'value' => 'yes' ),
					),
				),
			)
		);

		$this->add_control( 'icons_heading', array( 'label' => 'Iconițe', 'type' => Controls_Manager::HEADING ) );

		$this->add_control(
			'icons_color_mode',
			array(
				'label'       => 'Colorare iconițe SVG',
				'description' => 'Funcționează pentru iconițele încărcate ca SVG (se pun direct în pagină). Pentru PNG / JPG folosește „Filtre iconițe” de mai jos sau încarcă varianta colorată.',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'original',
				'options'     => array(
					'original' => 'Culorile originale',
					'fill'     => 'Colorează umplerea',
					'stroke'   => 'Colorează conturul',
					'both'     => 'Umplere + contur',
				),
			)
		);

		$this->add_control(
			'icons_color',
			array(
				'label'     => 'Culoare iconițe',
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'icons_color_mode!' => 'original' ),
				'selectors' => array(
					$this->sel( '.ps-icon' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'icons_color_hover',
			array(
				'label'     => 'Culoare iconițe (hover)',
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'icons_color_mode!' => 'original' ),
				'selectors' => array(
					$this->sel( '.ps-icon:hover' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name'     => 'icons_filter',
				'label'    => 'Filtre iconițe',
				'selector' => $this->sel( '.ps-icon-img, {{WRAPPER}} .ps-hero .ps-icon svg' ),
			)
		);

		$this->add_responsive_control(
			'icons_size',
			array(
				'label'       => 'Înălțime iconiță',
				'description' => 'Implicit 52px (44px pe mobil).',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem' ),
				'range'       => array( 'px' => array( 'min' => 12, 'max' => 200 ) ),
				'selectors'   => array( $this->sel() => '--ps-icon-h: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'icons_gap',
			array(
				'label'      => 'Spațiu între iconițe',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors'  => array( $this->sel( '.ps-icons' ) => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'icons_space',
			array(
				'label'       => 'Distanță până la descriere',
				'description' => 'Implicit 24px.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'selectors'   => array( $this->sel( '.ps-icons' ) => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'icons_align',
			array(
				'label'     => 'Aliniere',
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array( 'title' => 'Stânga', 'icon' => 'eicon-h-align-left' ),
					'center'     => array( 'title' => 'Centru', 'icon' => 'eicon-h-align-center' ),
					'flex-end'   => array( 'title' => 'Dreapta', 'icon' => 'eicon-h-align-right' ),
				),
				'selectors' => array( $this->sel( '.ps-icons' ) => 'justify-content: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'icons_label_color',
			array(
				'label'     => 'Culoare denumire',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $this->sel( '.ps-icon-label' ) => 'color: {{VALUE}};' ),
				'condition' => array( 'icons_labels' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'icons_label_typography',
				'selector'  => $this->sel( '.ps-icon-label' ),
				'condition' => array( 'icons_labels' => 'yes' ),
			)
		);

		$this->add_control( 'cta_heading', array( 'label' => 'Buton', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'cta_typography',
				'selector' => $this->sel( '.ps-cta' ),
			)
		);

		$this->add_responsive_control(
			'cta_space',
			array(
				'label'       => 'Distanță față de descriere',
				'description' => 'Implicit 28px.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'selectors'   => array( $this->sel( '.ps-cta-wrap' ) => 'margin-top: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'cta_padding',
			array(
				'label'      => 'Padding',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array(
					$this->sel( '.ps-cta' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'cta_width',
			array(
				'label'                => 'Lățime',
				'type'                 => Controls_Manager::SELECT,
				'default'              => '',
				'options'              => array(
					''     => 'Implicit (după text)',
					'auto' => 'După text',
					'full' => 'Toată lățimea coloanei',
				),
				'selectors_dictionary' => array(
					'auto' => 'width: auto;',
					'full' => 'width: 100%;',
				),
				'selectors'            => array( $this->sel( '.ps-cta' ) => '{{VALUE}}' ),
			)
		);

		$this->add_control(
			'cta_border_width',
			array(
				'label'      => 'Grosime bordură',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 8, 'step' => 0.5 ) ),
				'selectors'  => array( $this->sel( '.ps-cta' ) => 'border-width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'cta_radius',
			array(
				'label'      => 'Colțuri rotunjite',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					$this->sel( '.ps-cta' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'cta_tabs' );
		$this->start_controls_tab( 'cta_tab_normal', array( 'label' => 'Normal' ) );
		$this->add_control( 'cta_color', array( 'label' => 'Culoare text', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--ps-cta-color: {{VALUE}};' ) ) );
		$this->add_control( 'cta_bg', array( 'label' => 'Fundal', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--ps-cta-bg: {{VALUE}};' ) ) );
		$this->add_control( 'cta_border', array( 'label' => 'Culoare bordură', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--ps-cta-border: {{VALUE}};' ) ) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'cta_tab_hover', array( 'label' => 'Hover' ) );
		$this->add_control( 'cta_color_hover', array( 'label' => 'Culoare text', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--ps-cta-color-hover: {{VALUE}};' ) ) );
		$this->add_control( 'cta_bg_hover', array( 'label' => 'Fundal', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--ps-cta-bg-hover: {{VALUE}};' ) ) );
		$this->add_control( 'cta_border_hover', array( 'label' => 'Culoare bordură', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--ps-cta-border-hover: {{VALUE}};' ) ) );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		/* butonul „Adaugă la ofertă”: același font, padding și colțuri ca butonul de mai sus; culorile lui proprii */
		$this->add_control(
			'oferta_style_heading',
			array(
				'label'       => 'Buton „Adaugă la ofertă”',
				'description' => 'Fontul, bordura, colțurile și padding-ul de mai jos sunt doar pentru acest buton. Lăsate goale, rămân ca la „Buton”, de mai sus.',
				'type'        => Controls_Manager::HEADING,
				'separator'   => 'before',
				'condition'   => array( 'show_oferta' => 'yes' ),
			)
		);
		$this->add_responsive_control(
			'oferta_gap',
			array(
				'label'      => 'Distanța dintre butoane',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'condition'  => array( 'show_oferta' => 'yes' ),
				'selectors'  => array( $this->sel( '.ps-cta-wrap' ) => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'oferta_icon_size',
			array(
				'label'      => 'Mărime iconiță',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 40 ) ),
				'condition'  => array( 'show_oferta' => 'yes', 'oferta_icon' => 'yes' ),
				'selectors'  => array( $this->sel( '.ps-cta-oferta svg' ) => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);
		/* stil propriu (gol = ca butonul de mai sus) */
		$of = $this->sel( 'button.ps-cta-oferta' );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'oferta_typo', 'label' => 'Font', 'selector' => $of, 'condition' => array( 'show_oferta' => 'yes' ) ) );
		$this->add_responsive_control(
			'oferta_bw',
			array(
				'label'      => 'Grosime bordură',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 8, 'step' => 0.5 ) ),
				'condition'  => array( 'show_oferta' => 'yes' ),
				'selectors'  => array( $of => 'border-width: {{SIZE}}{{UNIT}}; border-style: solid;' ),
			)
		);
		$this->add_control(
			'oferta_bs',
			array(
				'label'     => 'Tip bordură',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array( '' => 'Implicit', 'solid' => 'Linie', 'dashed' => 'Linie întreruptă', 'dotted' => 'Puncte', 'double' => 'Dublă', 'none' => 'Fără' ),
				'condition' => array( 'show_oferta' => 'yes' ),
				'selectors' => array( $of => 'border-style: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'oferta_radius',
			array(
				'label'      => 'Colțuri',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'condition'  => array( 'show_oferta' => 'yes' ),
				'selectors'  => array( $of => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'oferta_pad',
			array(
				'label'      => 'Padding',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'condition'  => array( 'show_oferta' => 'yes' ),
				'selectors'  => array( $of => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'oferta_minh',
			array(
				'label'      => 'Înălțime minimă',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 20, 'max' => 100 ) ),
				'condition'  => array( 'show_oferta' => 'yes' ),
				'selectors'  => array( $of => 'min-height: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'oferta_shadow', 'label' => 'Umbră', 'selector' => $of, 'condition' => array( 'show_oferta' => 'yes' ) ) );
		$this->start_controls_tabs( 'oferta_tabs', array( 'condition' => array( 'show_oferta' => 'yes' ) ) );
		foreach ( array( 'normal' => 'Normal', 'hover' => 'Hover', 'added' => 'Adăugat' ) as $k => $label ) {
			$sfx = 'normal' === $k ? '' : '-' . $k;
			$this->start_controls_tab( 'oferta_tab_' . $k, array( 'label' => $label ) );
			$this->add_control( 'oferta_color_' . $k, array( 'label' => 'Culoare text', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--ps-of-color' . $sfx . ': {{VALUE}};' ) ) );
			$this->add_control( 'oferta_bg_' . $k, array( 'label' => 'Fundal', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--ps-of-bg' . $sfx . ': {{VALUE}};' ) ) );
			$this->add_control( 'oferta_border_' . $k, array( 'label' => 'Culoare bordură', 'type' => Controls_Manager::COLOR, 'selectors' => array( $this->sel() => '--ps-of-border' . $sfx . ': {{VALUE}};' ) ) );
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/* =====================================================================
	 * CONTENT – Săgeți
	 * ===================================================================== */
	private function content_arrows() {
		$this->start_controls_section(
			'section_arrows',
			array(
				'label' => 'Săgeți',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_arrows',
			array(
				'label'        => 'Afișează săgețile',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'arrows_type',
			array(
				'label'     => 'Tip săgeți',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'default',
				'options'   => array(
					'default' => 'Implicite (linii subțiri, ca în design)',
					'upload'  => 'SVG sau imagine încărcată de mine',
					'icon'    => 'Iconițe din bibliotecă',
				),
				'condition' => array( 'show_arrows' => 'yes' ),
			)
		);

		$this->add_control(
			'prev_upload',
			array(
				'label'       => 'Săgeată stânga (fișier)',
				'description' => 'Recomandat SVG: i se poate schimba culoarea din Style → Săgeți.',
				'type'        => Controls_Manager::MEDIA,
				'media_types' => array( 'image', 'svg' ),
				'default'     => array( 'url' => '' ),
				'condition'   => array(
					'show_arrows' => 'yes',
					'arrows_type' => 'upload',
				),
			)
		);

		$this->add_control(
			'next_upload',
			array(
				'label'       => 'Săgeată dreapta (fișier)',
				'type'        => Controls_Manager::MEDIA,
				'media_types' => array( 'image', 'svg' ),
				'default'     => array( 'url' => '' ),
				'condition'   => array(
					'show_arrows' => 'yes',
					'arrows_type' => 'upload',
				),
			)
		);

		$this->add_control(
			'prev_icon',
			array(
				'label'     => 'Iconiță stânga',
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'fas fa-arrow-left',
					'library' => 'fa-solid',
				),
				'condition' => array(
					'show_arrows' => 'yes',
					'arrows_type' => 'icon',
				),
			)
		);

		$this->add_control(
			'next_icon',
			array(
				'label'     => 'Iconiță dreapta',
				'type'      => Controls_Manager::ICONS,
				'default'   => array(
					'value'   => 'fas fa-arrow-right',
					'library' => 'fa-solid',
				),
				'condition' => array(
					'show_arrows' => 'yes',
					'arrows_type' => 'icon',
				),
			)
		);

		$this->add_control(
			'prev_label',
			array(
				'label'       => 'Text accesibilitate – stânga',
				'description' => 'Nu se vede; îl citesc cititoarele de ecran.',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Slide-ul anterior',
				'separator'   => 'before',
				'condition'   => array( 'show_arrows' => 'yes' ),
			)
		);

		$this->add_control(
			'next_label',
			array(
				'label'     => 'Text accesibilitate – dreapta',
				'type'      => Controls_Manager::TEXT,
				'default'   => 'Slide-ul următor',
				'condition' => array( 'show_arrows' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/* =====================================================================
	 * CONTENT – Paginare
	 * ===================================================================== */
	private function content_pagination() {
		$this->start_controls_section(
			'section_pagination',
			array(
				'label' => 'Paginare',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'pagination',
			array(
				'label'   => 'Tip paginare',
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => array(
					'none'     => 'Fără (ca în design)',
					'dots'     => 'Puncte',
					'fraction' => 'Număr (1 / 3)',
				),
			)
		);

		$this->end_controls_section();
	}

	/* =====================================================================
	 * CONTENT – Setări slider
	 * ===================================================================== */
	private function content_settings() {
		$this->start_controls_section(
			'section_settings',
			array(
				'label' => 'Setări slider',
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'effect',
			array(
				'label'   => 'Efect tranziție',
				'type'    => Controls_Manager::SELECT,
				'default' => 'slide',
				'options' => array(
					'slide'     => 'Alunecare',
					'fade'      => 'Estompare (fade)',
					'fade-zoom' => 'Estompare cu zoom ușor',
				),
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'       => 'Durata tranziției (ms)',
				'description' => '1000 ms = 1 secundă. Mai mare = mai lent și mai fin.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 5000,
				'step'        => 50,
				'default'     => 1100,
			)
		);

		$this->add_control(
			'easing',
			array(
				'label'   => 'Curba mișcării',
				'type'    => Controls_Manager::SELECT,
				'default' => 'smooth',
				'options' => array(
					'smooth' => 'Lină (pornește și se oprește moale)',
					'expo'   => 'Pornire rapidă, oprire foarte lină',
					'soft'   => 'Moale',
					'linear' => 'Liniară',
				),
			)
		);

		$this->add_control(
			'parallax',
			array(
				'label'       => 'Efect de adâncime (%)',
				'description' => 'Cât se deplasează slide-ul vechi. 100 = e împins complet. 30 = noul slide alunecă peste cel vechi, mai elegant.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 100,
				'step'        => 5,
				'default'     => 30,
				'condition'   => array( 'effect' => 'slide' ),
			)
		);

		$this->add_control(
			'kenburns',
			array(
				'label'        => 'Zoom lent pe imagine',
				'description'  => 'Imaginea activă se mărește foarte încet (efect Ken Burns).',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'kenburns_scale',
			array(
				'label'     => 'Cât se mărește',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 1.5,
				'step'      => 0.01,
				'default'   => 1.08,
				'condition' => array( 'kenburns' => 'yes' ),
			)
		);

		$this->add_control(
			'kenburns_duration',
			array(
				'label'     => 'Durata zoom (ms)',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1000,
				'max'       => 30000,
				'step'      => 500,
				'default'   => 8000,
				'condition' => array( 'kenburns' => 'yes' ),
			)
		);

		$this->add_control(
			'loop',
			array(
				'label'        => 'Infinit (după ultimul vine primul)',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => 'Autoplay',
				'description'  => 'În editorul Elementor autoplay-ul e oprit, ca să poți lucra liniștit. Pe site funcționează.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'autoplay_delay',
			array(
				'label'     => 'Timp pe fiecare slide (ms)',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1000,
				'max'       => 30000,
				'step'      => 250,
				'default'   => 6000,
				'condition' => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'pause_hover',
			array(
				'label'        => 'Pauză când mouse-ul e pe slider',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'drag',
			array(
				'label'        => 'Trage cu mouse-ul / swipe pe telefon',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);

		$this->end_controls_section();
	}

	/* =====================================================================
	 * STYLE – Dimensiuni generale (slider + text)
	 * ===================================================================== */
	private function style_frame() {
		$this->start_controls_section(
			'section_style_frame',
			array(
				'label' => 'Dimensiuni widget',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'frame_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'Implicit sliderul ia <strong>toată lățimea containerului</strong> în care îl pui. Setează lățimea din container sau limitează-o aici (ex. Lățime maximă 1400px).',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_responsive_control(
			'frame_width',
			array(
				'label'      => 'Lățime',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%', 'px', 'vw', 'em', 'rem' ),
				'range'      => array(
					'%'  => array( 'min' => 10, 'max' => 100 ),
					'px' => array( 'min' => 200, 'max' => 2560 ),
					'vw' => array( 'min' => 10, 'max' => 100 ),
				),
				'selectors'  => array(
					$this->sel( '.ps-frame' ) => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'frame_max_width',
			array(
				'label'       => 'Lățime maximă',
				'description' => 'Implicit fără limită (cât containerul). Pentru design: 1400px.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', '%', 'vw', 'em', 'rem' ),
				'range'       => array(
					'px' => array( 'min' => 200, 'max' => 2560 ),
					'%'  => array( 'min' => 10, 'max' => 100 ),
					'vw' => array( 'min' => 10, 'max' => 100 ),
				),
				'selectors'   => array(
					$this->sel( '.ps-frame' ) => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'frame_align',
			array(
				'label'                => 'Aliniere',
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => $this->align_options(),
				'selectors_dictionary' => $this->align_margins(),
				'selectors'            => array(
					$this->sel( '.ps-frame' ) => '{{VALUE}}',
				),
			)
		);

		$this->end_controls_section();
	}

	/* =====================================================================
	 * STYLE – Slider
	 * ===================================================================== */
	private function style_slider() {
		$this->start_controls_section(
			'section_style_slider',
			array(
				'label' => 'Slider',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'height_mode',
			array(
				'label'   => 'Înălțime după',
				'type'    => Controls_Manager::SELECT,
				'default' => 'ratio',
				'options' => array(
					'ratio' => 'Proporție (se adaptează la lățime)',
					'fixed' => 'Înălțime fixă',
				),
			)
		);

		$this->add_responsive_control(
			'ratio',
			array(
				'label'       => 'Proporție lățime / înălțime',
				'description' => 'Ex.: 1.94 = de 1.94 ori mai lat decât înalt (1400 × 720). 1 = pătrat. 0.8 = portret. Implicit: 1.94 desktop, 1.6 tabletă, 1.1 mobil.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0.2,
				'max'         => 6,
				'step'        => 0.01,
				'selectors'   => array(
					$this->sel( '.ps-viewport' ) => 'aspect-ratio: {{VALUE}}; height: auto;',
				),
				'condition'   => array( 'height_mode' => 'ratio' ),
			)
		);

		$this->add_responsive_control(
			'height',
			array(
				'label'      => 'Înălțime',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh', 'svh', 'em', 'rem' ),
				'range'      => array(
					'px'  => array( 'min' => 100, 'max' => 1400 ),
					'vh'  => array( 'min' => 10, 'max' => 100 ),
					'svh' => array( 'min' => 10, 'max' => 100 ),
				),
				'default'    => array( 'size' => 720, 'unit' => 'px' ),
				'selectors'  => array(
					$this->sel( '.ps-viewport' ) => 'height: {{SIZE}}{{UNIT}}; aspect-ratio: auto;',
				),
				'condition'  => array( 'height_mode' => 'fixed' ),
			)
		);

		$this->add_responsive_control(
			'min_height',
			array(
				'label'      => 'Înălțime minimă',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh', 'svh', 'em', 'rem' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 1200 ),
					'vh' => array( 'min' => 0, 'max' => 100 ),
				),
				'selectors'  => array(
					$this->sel( '.ps-viewport' ) => 'min-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'slider_radius',
			array(
				'label'      => 'Colțuri rotunjite',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'separator'  => 'before',
				'selectors'  => array(
					$this->sel( '.ps-viewport' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'slider_bg',
			array(
				'label'     => 'Culoare fundal',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.ps-viewport' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'slider_border',
				'selector' => $this->sel( '.ps-viewport' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'slider_shadow',
				'selector' => $this->sel( '.ps-viewport' ),
			)
		);

		$this->add_control(
			'image_heading',
			array(
				'label'     => 'Imagini',
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'image_fit',
			array(
				'label'     => 'Încadrare imagine',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''        => 'Implicit (Cover – umple tot)',
					'cover'   => 'Cover – umple, taie marginile',
					'contain' => 'Contain – imaginea întreagă',
					'fill'    => 'Fill – întinde',
				),
				'selectors' => array(
					$this->sel( '.ps-img' ) => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_position',
			array(
				'label'       => 'Punct de focus (toate slide-urile)',
				'description' => 'Ce parte a imaginii rămâne vizibilă când se taie. Se poate seta și separat pe fiecare slide.',
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => $this->position_options(),
				'selectors'   => array(
					$this->sel( '.ps-img' ) => 'object-position: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'overlay_color',
			array(
				'label'       => 'Strat de culoare peste imagini',
				'description' => 'Folosește transparența din selectorul de culoare pentru o nuanță discretă.',
				'type'        => Controls_Manager::COLOR,
				'selectors'   => array(
					$this->sel( '.ps-overlay' ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name'     => 'image_filters',
				'selector' => $this->sel( '.ps-img' ),
			)
		);

		$this->end_controls_section();

		/* ================= STYLE – Calitate imagine ================= */
		$this->start_controls_section(
			'section_style_image_quality',
			array(
				'label' => 'Imagine – calitate și claritate',
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'image_quality',
			array(
				'label'       => 'Calitate imagine',
				'description' => '„Automat clar” alege varianta imaginii după mărimea reală afișată (inclusiv când imaginea e tăiată pe telefon și la ecrane Retina). „Mereu rezoluția maximă” încarcă imaginea la dimensiunea aleasă mai sus (implicit Full): cea mai clară, dar mai grea.',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'smart',
				'options'     => array(
					'smart' => 'Automat clar (recomandat)',
					'max'   => 'Mereu rezoluția maximă',
					'wp'    => 'Standard WordPress',
				),
			)
		);

		$this->add_control(
			'lcp_priority',
			array(
				'label'        => 'Slider sus pe pagină (încărcare prioritară)',
				'description'  => 'Da = prima imagine se încarcă prima, înaintea restului paginii (bun pentru header). Pune Nu dacă slider-ul e mai jos pe pagină: atunci se încarcă abia când ajungi la el.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'image_rendering',
			array(
				'label'       => 'Randare imagine',
				'description' => '„Contur mai clar” face imaginile micșorate de browser să pară mai crisp (mai ales în Safari / Chrome pe Windows).',
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array(
					''         => 'Implicit',
					'contrast' => 'Contur mai clar',
				),
				'selectors_dictionary' => array(
					''         => 'auto',
					'contrast' => '-webkit-optimize-contrast',
				),
				'selectors'   => array(
					$this->sel( '.ps-img' ) => 'image-rendering: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'safari_mask',
			array(
				'label'        => 'Colțuri rotunjite – corecție Safari vechi',
				'description'  => 'Pornește doar dacă pe un iPhone / Mac mai vechi colțurile rotunjite ale slider-ului apar drepte în timpul animației. Poate face imaginea puțin mai puțin clară în Safari.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->end_controls_section();
	}

	/* =====================================================================
	 * STYLE – Săgeți
	 * ===================================================================== */
	private function style_arrows() {
		$this->start_controls_section(
			'section_style_arrows',
			array(
				'label'     => 'Săgeți',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_arrows' => 'yes' ),
			)
		);

		$this->add_control(
			'arrows_layout',
			array(
				'label'   => 'Așezare',
				'type'    => Controls_Manager::SELECT,
				'default' => 'group',
				'options' => array(
					'group' => 'Împreună, într-un colț (ca în design)',
					'sides' => 'Pe laterale (stânga / dreapta)',
				),
			)
		);

		$this->add_responsive_control(
			'arrows_position',
			array(
				'label'                => 'Poziție',
				'type'                 => Controls_Manager::SELECT,
				'default'              => '',
				'options'              => array(
					''              => 'Implicit (jos dreapta)',
					'bottom-right'  => 'Jos dreapta',
					'bottom-center' => 'Jos centru',
					'bottom-left'   => 'Jos stânga',
					'top-right'     => 'Sus dreapta',
					'top-center'    => 'Sus centru',
					'top-left'      => 'Sus stânga',
				),
				'selectors_dictionary' => $this->corner_css( '--ps-arrows-x', '--ps-arrows-y' ),
				'selectors'            => array(
					'{{WRAPPER}} .ps-hero.ps-arrows-group .ps-arrows' => '{{VALUE}}',
				),
				'condition'            => array( 'arrows_layout' => 'group' ),
			)
		);

		$this->add_responsive_control(
			'arrows_offset_x',
			array(
				'label'       => 'Distanță orizontală de la margine',
				'description' => 'Poate fi și negativă, ca săgețile să iasă în afara sliderului.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', '%', 'em', 'rem', 'vw' ),
				'range'       => array(
					'px' => array( 'min' => -200, 'max' => 400 ),
					'%'  => array( 'min' => -20, 'max' => 50 ),
				),
				'selectors'   => array(
					$this->sel() => '--ps-arrows-x: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'arrows_offset_y',
			array(
				'label'      => 'Distanță verticală de la margine',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem', 'vw' ),
				'range'      => array(
					'px' => array( 'min' => -200, 'max' => 400 ),
					'%'  => array( 'min' => -20, 'max' => 50 ),
				),
				'selectors'  => array(
					$this->sel() => '--ps-arrows-y: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'arrows_layout' => 'group' ),
			)
		);

		$this->add_responsive_control(
			'arrows_gap',
			array(
				'label'      => 'Spațiu între săgeți',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 200 ) ),
				'selectors'  => array(
					$this->sel( '.ps-arrows' ) => 'gap: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'arrows_layout' => 'group' ),
			)
		);

		$this->add_responsive_control(
			'arrow_size',
			array(
				'label'       => 'Mărime (înălțime)',
				'description' => 'Lățimea se potrivește automat după forma săgeții.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem' ),
				'range'       => array( 'px' => array( 'min' => 6, 'max' => 120 ) ),
				'separator'   => 'before',
				'selectors'   => array(
					$this->sel( '.ps-arrow' ) => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'arrow_width',
			array(
				'label'       => 'Lățime forțată (opțional)',
				'description' => 'Lasă gol ca lățimea să se calculeze singură.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem' ),
				'range'       => array( 'px' => array( 'min' => 6, 'max' => 300 ) ),
				'selectors'   => array(
					$this->sel( '.ps-arrow svg' )      => 'width: {{SIZE}}{{UNIT}};',
					$this->sel( '.ps-arrow .ps-arrow-img' ) => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'arrows_svg_color_mode',
			array(
				'label'       => 'Cum se colorează SVG-ul',
				'description' => 'Dacă săgeata ta e desenată din linii, alege „Contur”.',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => array(
					'auto'     => 'Automat',
					'fill'     => 'Umplere (fill)',
					'stroke'   => 'Contur (stroke)',
					'both'     => 'Umplere + contur',
					'original' => 'Culorile originale ale fișierului',
				),
				'condition'   => array( 'arrows_type' => array( 'upload', 'icon' ) ),
			)
		);

		$this->start_controls_tabs( 'arrows_tabs' );

		$this->start_controls_tab( 'arrows_tab_normal', array( 'label' => 'Normal' ) );

		$this->add_control(
			'arrow_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--ps-arrow-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_bg',
			array(
				'label'     => 'Fundal',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--ps-arrow-bg: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'arrows_tab_hover', array( 'label' => 'Hover' ) );

		$this->add_control(
			'arrow_color_hover',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--ps-arrow-color-hover: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_bg_hover',
			array(
				'label'     => 'Fundal',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--ps-arrow-bg-hover: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_border_hover',
			array(
				'label'     => 'Culoare bordură',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.ps-arrow:hover' ) => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_shift',
			array(
				'label'      => 'Deplasare la hover',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					$this->sel() => '--ps-arrow-shift: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'arrow_padding',
			array(
				'label'      => 'Padding (zona de click)',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'separator'  => 'before',
				'selectors'  => array(
					$this->sel( '.ps-arrow' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'arrow_border',
				'selector' => $this->sel( '.ps-arrow' ),
			)
		);

		$this->add_responsive_control(
			'arrow_radius',
			array(
				'label'      => 'Colțuri rotunjite',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					$this->sel( '.ps-arrow' ) => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'arrow_disabled_opacity',
			array(
				'label'       => 'Opacitate săgeată inactivă',
				'description' => 'Când sliderul nu e infinit și ești la primul sau ultimul slide.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 1,
				'step'        => 0.05,
				'selectors'   => array(
					$this->sel() => '--ps-arrow-disabled: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_speed',
			array(
				'label'     => 'Durata efectelor hover (ms)',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 2000,
				'step'      => 50,
				'selectors' => array(
					$this->sel() => '--ps-arrow-speed: {{VALUE}}ms;',
				),
			)
		);

		$this->end_controls_section();
	}

	/* =====================================================================
	 * STYLE – Paginare
	 * ===================================================================== */
	private function style_pagination() {
		$this->start_controls_section(
			'section_style_pagination',
			array(
				'label'     => 'Paginare',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'pagination!' => 'none' ),
			)
		);

		$this->add_responsive_control(
			'pag_position',
			array(
				'label'                => 'Poziție',
				'type'                 => Controls_Manager::SELECT,
				'default'              => '',
				'options'              => array(
					''              => 'Implicit (jos centru)',
					'bottom-center' => 'Jos centru',
					'bottom-left'   => 'Jos stânga',
					'bottom-right'  => 'Jos dreapta',
					'top-center'    => 'Sus centru',
					'top-left'      => 'Sus stânga',
					'top-right'     => 'Sus dreapta',
				),
				'selectors_dictionary' => $this->corner_css( '--ps-pag-x', '--ps-pag-y' ),
				'selectors'            => array(
					$this->sel( '.ps-pagination' ) => '{{VALUE}}',
				),
			)
		);

		$this->add_responsive_control(
			'pag_offset_x',
			array(
				'label'      => 'Distanță orizontală',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => -200, 'max' => 400 ) ),
				'selectors'  => array(
					$this->sel() => '--ps-pag-x: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'pag_offset_y',
			array(
				'label'      => 'Distanță verticală',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => -200, 'max' => 400 ) ),
				'selectors'  => array(
					$this->sel() => '--ps-pag-y: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'dot_size',
			array(
				'label'      => 'Mărime punct',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 2, 'max' => 40 ) ),
				'separator'  => 'before',
				'selectors'  => array(
					$this->sel() => '--ps-dot-size: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'pagination' => 'dots' ),
			)
		);

		$this->add_responsive_control(
			'dot_active_width',
			array(
				'label'      => 'Lățime punct activ',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 2, 'max' => 120 ) ),
				'selectors'  => array(
					$this->sel() => '--ps-dot-active-w: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'pagination' => 'dots' ),
			)
		);

		$this->add_responsive_control(
			'dot_gap',
			array(
				'label'      => 'Spațiu între puncte',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array(
					$this->sel( '.ps-pagination' ) => 'gap: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'pagination' => 'dots' ),
			)
		);

		$this->add_control(
			'dot_color',
			array(
				'label'     => 'Culoare puncte',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--ps-dot-color: {{VALUE}};',
				),
				'condition' => array( 'pagination' => 'dots' ),
			)
		);

		$this->add_control(
			'dot_active_color',
			array(
				'label'     => 'Culoare punct activ',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel() => '--ps-dot-active: {{VALUE}};',
				),
				'condition' => array( 'pagination' => 'dots' ),
			)
		);

		$this->add_control(
			'fraction_color',
			array(
				'label'     => 'Culoare număr',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.ps-fraction' ) => 'color: {{VALUE}};',
				),
				'condition' => array( 'pagination' => 'fraction' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'fraction_typography',
				'selector'  => $this->sel( '.ps-fraction' ),
				'condition' => array( 'pagination' => 'fraction' ),
			)
		);

		$this->end_controls_section();
	}

	/* =====================================================================
	 * STYLE – Bloc text
	 * ===================================================================== */
	private function style_text_block() {
		$this->start_controls_section(
			'section_style_text_block',
			array(
				'label'     => 'Bloc text (cele două coloane)',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_text' => 'yes' ),
			)
		);

		$this->add_control(
			'text_block_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'Zona de text are <strong>două coloane</strong>: Coloana titlu și Coloana descriere. Aici setezi rândul care le conține (ca un container flexbox). Fiecare coloană are secțiunea ei mai jos.<br><br>Fiecare slide are propriul bloc de text: la schimbare nimic nu se mișcă din loc, iar distanța dintre titlu și descriere rămâne mereu cea setată.',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_responsive_control(
			'text_padding',
			array(
				'label'       => 'Spațiere în jurul textului',
				'description' => 'Sus = distanța față de slider. Implicit: desktop 56/0/0/60, tabletă 44/0/0/40, mobil 28/0/0/0 px.',
				'type'        => Controls_Manager::DIMENSIONS,
				'size_units'  => array( 'px', '%', 'em', 'rem', 'vw' ),
				'selectors'   => array(
					$this->sel( '.ps-text' ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'text_max_width',
			array(
				'label'       => 'Lățime maximă rând',
				'description' => 'Implicit 1120px, ca în design.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', '%', 'vw', 'em', 'rem' ),
				'range'       => array(
					'px' => array( 'min' => 200, 'max' => 2000 ),
					'%'  => array( 'min' => 10, 'max' => 100 ),
				),
				'selectors'   => array(
					$this->sel( '.ps-text-inner' ) => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'text_block_align',
			array(
				'label'                => 'Aliniere rând în pagină',
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => $this->align_options(),
				'selectors_dictionary' => $this->align_margins(),
				'selectors'            => array(
					$this->sel( '.ps-text-inner' ) => '{{VALUE}}',
				),
			)
		);

		$this->add_responsive_control(
			'text_direction',
			array(
				'label'                => 'Direcție coloane',
				'type'                 => Controls_Manager::SELECT,
				'default'              => '',
				'separator'            => 'before',
				'options'              => array(
					''       => 'Implicit (alături pe desktop/tabletă, una sub alta pe mobil)',
					'row'    => 'Alături (rând)',
					'column' => 'Una sub alta (coloană)',
				),
				'selectors_dictionary' => array(
					'row'    => 'flex-direction: row;',
					'column' => 'flex-direction: column; --ps-tcol-max: 100%; --ps-dcol-w: 100%; --ps-dcol-justify: flex-start; --ps-row-min: 0px;',
				),
				'selectors'            => array(
					$this->sel( '.ps-text-inner' ) => '{{VALUE}}',
				),
			)
		);

		$this->add_responsive_control(
			'text_justify',
			array(
				'label'       => 'Justify content',
				'description' => 'Cum se împart coloanele pe direcția principală.',
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array(
					''              => 'Implicit (space-between)',
					'flex-start'    => 'flex-start',
					'center'        => 'center',
					'flex-end'      => 'flex-end',
					'space-between' => 'space-between',
					'space-around'  => 'space-around',
					'space-evenly'  => 'space-evenly',
				),
				'selectors'   => array(
					$this->sel( '.ps-text-inner' ) => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'text_align_items',
			array(
				'label'       => 'Align items',
				'description' => '„stretch” = coloanele au aceeași înălțime (necesar ca descrierea să poată sta jos).',
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array(
					''           => 'Implicit (stretch)',
					'stretch'    => 'stretch',
					'flex-start' => 'flex-start',
					'center'     => 'center',
					'flex-end'   => 'flex-end',
					'baseline'   => 'baseline',
				),
				'selectors'   => array(
					$this->sel( '.ps-text-inner' ) => 'align-items: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'desc_position',
			array(
				'label' => 'Poziție coloană descriere',
				'type' => Controls_Manager::SELECT,
				'description' => '„Începe sub titlu”: iconițele, descrierea și butonul încep sub titlu, în coloana din dreapta (ca în designul paginii de produs). Pe mobil totul se așază unul sub altul.',
				'options' => array( 'beside' => 'Alături de titlu (sus aliniat)', 'below' => 'Începe sub titlu' ),
				'prefix_class' => 'ps-desc-pos-',
				'default' => 'beside',
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'desc_below_gap',
			array(
				'label' => 'Spațiu între titlu și coloana descriere',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range' => array( 'px' => array( 'min' => 0, 'max' => 200, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.ps-text-inner' ) => 'row-gap: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'desc_position' => 'below' ),
			)
		);

		$this->add_responsive_control(
			'desc_offset',
			array(
				'label' => 'Mută coloana descriere (sus / jos)',
				'type' => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range' => array( 'px' => array( 'min' => -200, 'max' => 300, 'step' => 1 ) ),
				'selectors' => array(
					$this->sel( '.ps-col-desc' ) => 'margin-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'text_gap',
			array(
				'label'       => 'Gap între coloane',
				'description' => 'Implicit 40px; pe mobil 16px.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem', '%', 'vw' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 300 ) ),
				'selectors'   => array(
					$this->sel( '.ps-text-inner' ) => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'text_min_height',
			array(
				'label'       => 'Înălțime minimă rând',
				'description' => 'Cu „stretch”, descrierea stă jos la această înălțime. Implicit 300px desktop, 220px tabletă, 0 mobil.',
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'em', 'rem', 'vh' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 900 ) ),
				'selectors'   => array(
					$this->sel( '.ps-text-inner' ) => 'min-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'title_align',
			array(
				'label'     => 'Aliniere text titlu',
				'type'      => Controls_Manager::CHOOSE,
				'options'   => $this->align_options( true ),
				'separator' => 'before',
				'selectors' => array(
					$this->sel( '.ps-title' ) => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'desc_align',
			array(
				'label'     => 'Aliniere text descriere',
				'type'      => Controls_Manager::CHOOSE,
				'options'   => $this->align_options( true ),
				'selectors' => array(
					$this->sel( '.ps-desc' ) => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->column_controls(
			'tcol',
			'Coloana titlu',
			'.ps-col-title',
			array( 'show_title' => 'yes' ),
			'Implicit: lățime după conținut, maxim 60% (100% pe mobil), titlul sus.'
		);

		$this->column_controls(
			'dcol',
			'Coloana descriere',
			'.ps-col-desc',
			array( 'show_desc' => 'yes' ),
			'Implicit: lățime 480px (400px tabletă, 100% mobil), descrierea jos în coloană (sus pe mobil).'
		);
	}

	/* Setările unei coloane (div flexbox). */
	private function column_controls( $p, $label, $col, $condition, $defaults_note ) {
		$this->start_controls_section(
			'section_style_' . $p,
			array(
				'label'     => $label,
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array_merge( array( 'show_text' => 'yes' ), $condition ),
			)
		);

		$this->add_control(
			$p . '_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => $defaults_note . ' Toate câmpurile goale păstrează valorile implicite.',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$units = array( 'px', '%', 'em', 'rem', 'vw', 'vh' );
		$range = array(
			'px' => array( 'min' => 0, 'max' => 1600 ),
			'%'  => array( 'min' => 0, 'max' => 100 ),
			'vw' => array( 'min' => 0, 'max' => 100 ),
			'vh' => array( 'min' => 0, 'max' => 100 ),
		);

		$sizes = array(
			'width'      => array( 'Lățime', 'width' ),
			'min_width'  => array( 'Lățime minimă', 'min-width' ),
			'max_width'  => array( 'Lățime maximă', 'max-width' ),
			'height'     => array( 'Înălțime', 'height' ),
			'min_height' => array( 'Înălțime minimă', 'min-height' ),
			'max_height' => array( 'Înălțime maximă', 'max-height' ),
		);
		foreach ( $sizes as $key => $info ) {
			$this->add_responsive_control(
				$p . '_' . $key,
				array(
					'label'      => $info[0],
					'type'       => Controls_Manager::SLIDER,
					'size_units' => $units,
					'range'      => $range,
					'selectors'  => array(
						$this->sel( $col ) => $info[1] . ': {{SIZE}}{{UNIT}};',
					),
					'separator'  => 'min_height' === $key || 'width' === $key ? 'before' : 'none',
				)
			);
		}

		$this->add_responsive_control(
			$p . '_flex',
			array(
				'label'     => 'Flex (cât spațiu ocupă)',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'separator' => 'before',
				'options'   => array(
					''          => 'Implicit',
					'0 1 auto'  => 'Cât are nevoie (se poate micșora)',
					'0 0 auto'  => 'Fixă (nu se micșorează)',
					'1 1 0%'    => 'Ocupă tot spațiul rămas',
					'1 1 auto'  => 'Crește de la mărimea ei',
				),
				'selectors' => array(
					$this->sel( $col ) => 'flex: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			$p . '_justify',
			array(
				'label'     => 'Poziție verticală conținut (justify-content)',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''              => 'Implicit',
					'flex-start'    => 'Sus (flex-start)',
					'center'        => 'Centru (center)',
					'flex-end'      => 'Jos (flex-end)',
					'space-between' => 'space-between',
				),
				'selectors' => array(
					$this->sel( $col ) => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			$p . '_align_items',
			array(
				'label'     => 'Poziție orizontală conținut (align-items)',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''           => 'Implicit (stretch)',
					'stretch'    => 'Pe toată lățimea (stretch)',
					'flex-start' => 'Stânga (flex-start)',
					'center'     => 'Centru (center)',
					'flex-end'   => 'Dreapta (flex-end)',
				),
				'selectors' => array(
					$this->sel( $col ) => 'align-items: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			$p . '_align_self',
			array(
				'label'     => 'Align self (poziția coloanei în rând)',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''           => 'Implicit',
					'stretch'    => 'stretch',
					'flex-start' => 'flex-start',
					'center'     => 'center',
					'flex-end'   => 'flex-end',
				),
				'selectors' => array(
					$this->sel( $col ) => 'align-self: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			$p . '_order',
			array(
				'label'       => 'Ordine',
				'description' => 'Ex.: pune 1 la titlu și 0 la descriere ca să le inversezi.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => -10,
				'max'         => 10,
				'selectors'   => array(
					$this->sel( $col ) => 'order: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			$p . '_padding',
			array(
				'label'      => 'Padding',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'vw' ),
				'separator'  => 'before',
				'selectors'  => array(
					$this->sel( $col ) => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			$p . '_margin',
			array(
				'label'      => 'Margin',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'vw' ),
				'selectors'  => array(
					$this->sel( $col ) => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			$p . '_bg',
			array(
				'label'     => 'Fundal coloană',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( $col ) => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/* =====================================================================
	 * STYLE – Titlu
	 * ===================================================================== */
	private function style_title() {
		$this->start_controls_section(
			'section_style_title',
			array(
				'label'     => 'Titlu',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_text'  => 'yes',
					'show_title' => 'yes',
				),
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.ps-title' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => $this->sel( '.ps-title' ),
			)
		);

		$this->end_controls_section();
	}

	/* =====================================================================
	 * STYLE – Descriere
	 * ===================================================================== */
	private function style_desc() {
		$this->start_controls_section(
			'section_style_desc',
			array(
				'label'     => 'Descriere',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_text' => 'yes',
					'show_desc' => 'yes',
				),
			)
		);

		$this->add_control(
			'desc_color',
			array(
				'label'     => 'Culoare',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$this->sel( '.ps-desc' ) => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'desc_typography',
				'selector' => $this->sel( '.ps-desc' ),
			)
		);

		$this->end_controls_section();
	}

	/* =====================================================================
	 * STYLE – Animație text
	 * ===================================================================== */
	private function style_text_animation() {
		$this->start_controls_section(
			'section_style_text_anim',
			array(
				'label'     => 'Animație text',
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_text' => 'yes' ),
			)
		);

		$this->add_control(
			'text_anim',
			array(
				'label'   => 'Tip animație',
				'type'    => Controls_Manager::SELECT,
				'default' => 'words',
				'options' => array(
					'words'      => 'Cuvânt cu cuvânt – urcă de sub linie (recomandat)',
					'words-blur' => 'Cuvânt cu cuvânt – estompare cu blur',
					'up'         => 'Tot textul – estompare + urcare',
					'fade'       => 'Tot textul – doar estompare',
					'none'       => 'Fără animație',
				),
			)
		);

		$this->add_control(
			'text_intro',
			array(
				'label'        => 'Animație și la încărcarea paginii',
				'description'  => 'Textul primului slide apare cuvânt cu cuvânt când se deschide pagina.',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => 'Da',
				'label_off'    => 'Nu',
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'text_anim' => array( 'words', 'words-blur' ) ),
			)
		);

		$this->add_control(
			'word_duration',
			array(
				'label'       => 'Durata unui cuvânt (ms)',
				'description' => 'Mai mare = mai lent și mai fin. Recomandat 900–1300.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 100,
				'max'         => 4000,
				'step'        => 50,
				'selectors'   => array(
					$this->sel() => '--ps-word-dur: {{VALUE}}ms;',
				),
				'condition'   => array( 'text_anim' => array( 'words', 'words-blur' ) ),
			)
		);

		$this->add_control(
			'word_step',
			array(
				'label'       => 'Pauză între cuvinte (ms)',
				'description' => 'Cât așteaptă fiecare cuvânt după cel dinainte. Descrierea merge automat mai repede, fiind mai lungă.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 400,
				'step'        => 5,
				'selectors'   => array(
					$this->sel() => '--ps-word-step: {{VALUE}}ms;',
				),
				'condition'   => array( 'text_anim' => array( 'words', 'words-blur' ) ),
			)
		);

		$this->add_control(
			'word_ease',
			array(
				'label'     => 'Curba mișcării',
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''                             => 'Implicit – foarte lină (ca GSAP expo.out)',
					'cubic-bezier(.25,1,.5,1)'     => 'Lină (quart.out)',
					'cubic-bezier(.22,.61,.36,1)'  => 'Moale',
					'cubic-bezier(.34,1.45,.64,1)' => 'Cu un mic rebound (back.out)',
				),
				'selectors' => array(
					$this->sel() => '--ps-word-ease: {{VALUE}};',
				),
				'condition' => array( 'text_anim' => array( 'words', 'words-blur' ) ),
			)
		);

		$this->add_control(
			'text_duration',
			array(
				'label'     => 'Durată (ms)',
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 3000,
				'step'      => 50,
				'selectors' => array(
					$this->sel() => '--ps-text-dur: {{VALUE}}ms;',
				),
				'condition' => array( 'text_anim' => array( 'up', 'fade' ) ),
			)
		);

		$this->add_control(
			'text_distance',
			array(
				'label'      => 'Distanță mișcare',
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors'  => array(
					$this->sel() => '--ps-text-dist: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'text_anim' => 'up' ),
			)
		);

		$this->add_control(
			'text_stagger',
			array(
				'label'       => 'Întârziere descriere față de titlu (ms)',
				'description' => 'Descrierea pornește puțin după titlu, pentru un efect mai fin.',
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 2000,
				'step'        => 10,
				'selectors'   => array(
					$this->sel() => '--ps-text-stagger: {{VALUE}}ms;',
				),
				'condition'   => array( 'text_anim!' => 'none' ),
			)
		);

		$this->end_controls_section();
	}

	/* =====================================================================
	 * Helpers
	 * ===================================================================== */
	private function tag_options() {
		return array(
			'h1'   => 'H1',
			'h2'   => 'H2',
			'h3'   => 'H3',
			'h4'   => 'H4',
			'h5'   => 'H5',
			'h6'   => 'H6',
			'div'  => 'div',
			'p'    => 'p',
			'span' => 'span',
		);
	}

	private function align_options( $justify = false ) {
		$options = array(
			'left'   => array( 'title' => 'Stânga', 'icon' => 'eicon-text-align-left' ),
			'center' => array( 'title' => 'Centru', 'icon' => 'eicon-text-align-center' ),
			'right'  => array( 'title' => 'Dreapta', 'icon' => 'eicon-text-align-right' ),
		);
		if ( $justify ) {
			$options['justify'] = array( 'title' => 'Justify', 'icon' => 'eicon-text-align-justify' );
		}
		return $options;
	}

	private function align_margins() {
		return array(
			'left'   => 'margin-left: 0; margin-right: auto;',
			'center' => 'margin-left: auto; margin-right: auto;',
			'right'  => 'margin-left: auto; margin-right: 0;',
		);
	}

	private function position_options() {
		return array(
			''              => 'Implicit (centru)',
			'center center' => 'Centru',
			'center top'    => 'Sus',
			'center bottom' => 'Jos',
			'left center'   => 'Stânga',
			'right center'  => 'Dreapta',
			'left top'      => 'Stânga sus',
			'right top'     => 'Dreapta sus',
			'left bottom'   => 'Stânga jos',
			'right bottom'  => 'Dreapta jos',
		);
	}

	/* CSS pentru poziționare în colțuri, folosind variabile pentru distanțe. */
	private function corner_css( $var_x, $var_y ) {
		$x = 'var(' . $var_x . ')';
		$y = 'var(' . $var_y . ')';
		return array(
			'bottom-right'  => "top: auto; bottom: {$y}; left: auto; right: {$x}; transform: none;",
			'bottom-center' => "top: auto; bottom: {$y}; right: auto; left: 50%; transform: translateX(-50%);",
			'bottom-left'   => "top: auto; bottom: {$y}; right: auto; left: {$x}; transform: none;",
			'top-right'     => "bottom: auto; top: {$y}; left: auto; right: {$x}; transform: none;",
			'top-center'    => "bottom: auto; top: {$y}; right: auto; left: 50%; transform: translateX(-50%);",
			'top-left'      => "bottom: auto; top: {$y}; right: auto; left: {$x}; transform: none;",
		);
	}

	/* Breakpoint-urile reale din Site Settings, pentru imaginile de tabletă / mobil. */
	private function get_breakpoints() {
		$bp = array(
			'mobile' => 767,
			'tablet' => 1024,
		);
		try {
			if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->breakpoints ) ) {
				$active = \Elementor\Plugin::$instance->breakpoints->get_active_breakpoints();
				if ( isset( $active['mobile'] ) ) {
					$bp['mobile'] = (int) $active['mobile']->get_value();
				}
				if ( isset( $active['tablet'] ) ) {
					$bp['tablet'] = (int) $active['tablet']->get_value();
				}
			}
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
			// rămân valorile implicite
		}
		return $bp;
	}

	private function media_url( $media, $size ) {
		if ( ! empty( $media['id'] ) ) {
			$url = wp_get_attachment_image_url( (int) $media['id'], $size );
			if ( $url ) {
				return $url;
			}
		}
		return ! empty( $media['url'] ) ? $media['url'] : '';
	}

	/* numele link-ului unui slide: titlul slide-ului, textul alternativ al imaginii sau adresa */
	private function slide_link_label( $slide ) {
		foreach ( array( 'slide_title', 'slide_alt' ) as $k ) {
			$t = isset( $slide[ $k ] ) ? trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $slide[ $k ] ) ) ) : '';
			if ( '' !== $t ) return $t;
		}
		$id = isset( $slide['slide_image']['id'] ) ? (int) $slide['slide_image']['id'] : 0;
		if ( $id ) {
			$t = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
			if ( '' !== $t ) return $t;
		}
		$url  = isset( $slide['slide_link']['url'] ) ? (string) $slide['slide_link']['url'] : '';
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
		if ( false !== strpos( $host, 'google.' ) && false !== strpos( $url, '/maps' ) ) return 'Deschide locația în Google Maps';
		return $host && $host !== wp_parse_url( home_url(), PHP_URL_HOST ) ? 'Deschide ' . preg_replace( '/^www\./', '', $host ) : 'Deschide';
	}

	private function get_picture_html( $slide, $index, $size, $bp, $quality = 'smart', $priority = true, $defer = false ) {
		$media = isset( $slide['slide_image'] ) ? $slide['slide_image'] : array();
		if ( empty( $media['url'] ) && empty( $media['id'] ) ) {
			return '';
		}

		$alt = isset( $slide['slide_alt'] ) ? trim( $slide['slide_alt'] ) : '';
		if ( '' === $alt && ! empty( $media['id'] ) ) {
			$alt = (string) get_post_meta( (int) $media['id'], '_wp_attachment_image_alt', true );
		}

		$attrs = array(
			'class'     => 'ps-img',
			'alt'       => $alt,
			'decoding'  => 'async',
			'draggable' => 'false',
			'loading'   => ( 0 === $index && $priority ) ? 'eager' : 'lazy',
		);
		if ( 0 === $index && $priority ) {
			$attrs['fetchpriority'] = 'high';
		}

		if ( ! empty( $media['id'] ) ) {
			$img = wp_get_attachment_image( (int) $media['id'], $size, false, $attrs );
			if ( 'max' === $quality ) {
				/* fără variante micșorate: browserul încarcă mereu dimensiunea aleasă */
				$img = preg_replace( '/\s(srcset|sizes)="[^"]*"/', '', $img );
			} elseif ( 'smart' === $quality ) {
				/* până calculează scriptul mărimea reală: toată lățimea ecranului */
				$img = preg_replace( '/\ssizes="[^"]*"/', ' sizes="100vw"', $img );
			}
		} else {
			$img = '';
		}
		if ( '' === $img ) {
			$attr_html = '';
			foreach ( $attrs as $name => $value ) {
				$attr_html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
			}
			$img = '<img src="' . esc_url( $media['url'] ) . '"' . $attr_html . '>';
		}

		$sources = '';
		$mobile  = isset( $slide['slide_image_mobile'] ) ? $this->media_url( $slide['slide_image_mobile'], $size ) : '';
		$tablet  = isset( $slide['slide_image_tablet'] ) ? $this->media_url( $slide['slide_image_tablet'], $size ) : '';
		if ( $mobile ) {
			$sources .= '<source media="(max-width: ' . (int) $bp['mobile'] . 'px)" srcset="' . esc_url( $mobile ) . '">';
		}
		if ( $tablet ) {
			$sources .= '<source media="(max-width: ' . (int) $bp['tablet'] . 'px)" srcset="' . esc_url( $tablet ) . '">';
		}

		$html = $sources ? '<picture class="ps-picture">' . $sources . $img . '</picture>' : $img;
		if ( $defer && $index > 0 ) {
			/* celelalte slide-uri: imaginea se încarcă după afișarea paginii (hero-slider.js), nu odată cu prima */
			$html = preg_replace( '/\s(src|srcset)="/', ' data-ps-$1="', $html );
			$html = preg_replace( '/<img\b/', '<img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=="', $html, 1 );
		}
		return $html;
	}

	/* butonul „Adaugă la ofertă” (lista de ofertă, assets/oferta-lista.js): produsul paginii */
	private function oferta_button_html( $s ) {
		if ( ! class_exists( 'Pavva_W_Lista' ) ) return '';
		$pid  = class_exists( 'Pavva_W_Data' ) ? (int) Pavva_W_Data::resolve_product_id() : 0;
		$item = Pavva_W_Lista::item_full( $pid );
		$edit = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode();
		if ( ! $item ) {
			if ( ! $edit ) return '';
			$item = array( 'id' => 0, 'name' => 'Produs exemplu', 'cat' => '', 'url' => '', 'img' => '' );
		}
		$text  = trim( (string) ( $s['oferta_text'] ?? '' ) );
		$added = trim( (string) ( $s['oferta_text_added'] ?? '' ) );
		if ( '' === $text ) $text = 'Adaugă la ofertă';
		if ( '' === $added ) $added = $text;
		$icons = 'yes' === ( $s['oferta_icon'] ?? 'yes' )
			? '<svg class="pvl-ico-add" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>'
				. '<svg class="pvl-ico-added" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12.5l4.5 4.5L19 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>'
			: '';
		return sprintf(
			'<button type="button" class="ps-cta ps-cta-oferta" aria-pressed="false" data-pvl-add data-pvl="%1$s" data-pvl-mode="%2$s" data-pvl-again="%3$s" data-pvl-form="%4$s" data-pvl-toast="%5$s">%6$s<span class="pvl-t pvl-t-add">%7$s</span><span class="pvl-t pvl-t-added">%8$s</span></button>',
			esc_attr( wp_json_encode( $item ) ),
			'go' === ( $s['oferta_mode'] ?? 'add' ) ? 'go' : 'add',
			'remove' === ( $s['oferta_again'] ?? 'go' ) ? 'remove' : 'go',
			esc_url( Pavva_W_Lista::resolve_url( $s['oferta_form'] ?? '' ) ),
			'yes' === ( $s['oferta_toast'] ?? 'yes' ) ? '1' : '0',
			$icons, // phpcs:ignore -- SVG fix
			esc_html( $text ),
			esc_html( $added )
		);
	}

	/* fișierele primei imagini (pentru preîncărcarea din <head>), cu variantele de telefon / tabletă */
	private function lcp_preload( $slide, $size, $bp, $quality ) {
		$media = isset( $slide['slide_image'] ) ? $slide['slide_image'] : array();
		$out   = array();
		$max   = 0;
		$mobile = isset( $slide['slide_image_mobile'] ) ? $this->media_url( $slide['slide_image_mobile'], $size ) : '';
		$tablet = isset( $slide['slide_image_tablet'] ) ? $this->media_url( $slide['slide_image_tablet'], $size ) : '';
		if ( $mobile ) {
			$out[] = array( 'href' => $mobile, 'media' => '(max-width: ' . (int) $bp['mobile'] . 'px)' );
			$max   = (int) $bp['mobile'];
		}
		if ( $tablet ) {
			$out[] = array( 'href' => $tablet, 'media' => ( $max ? '(min-width: ' . ( $max + 1 ) . 'px) and ' : '' ) . '(max-width: ' . (int) $bp['tablet'] . 'px)' );
			$max   = (int) $bp['tablet'];
		}
		$main = array( 'href' => '' );
		if ( ! empty( $media['id'] ) ) {
			$src = wp_get_attachment_image_src( (int) $media['id'], $size );
			if ( $src ) $main['href'] = $src[0];
			if ( 'max' !== $quality ) {
				$set = wp_get_attachment_image_srcset( (int) $media['id'], $size );
				if ( $set ) {
					$main['srcset'] = $set;
					$main['sizes']  = '100vw';
				}
			}
		} elseif ( ! empty( $media['url'] ) ) {
			$main['href'] = $media['url'];
		}
		if ( $main['href'] ) {
			if ( $max ) $main['media'] = '(min-width: ' . ( $max + 1 ) . 'px)';
			$out[] = $main;
		}
		return $out;
	}

	private function default_arrow_svg( $dir ) {
		$path = 'next' === $dir
			? 'M1 7h53M47.5 1l6.5 6-6.5 6'
			: 'M55 7H2M8.5 1L2 7l6.5 6';
		return '<svg class="ps-arrow-default" viewBox="0 0 56 14" aria-hidden="true" focusable="false"><path d="' . $path . '" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}

	private function get_arrow_html( $s, $dir ) {
		$type = ! empty( $s['arrows_type'] ) ? $s['arrows_type'] : 'default';

		if ( 'icon' === $type ) {
			$key = $dir . '_icon';
			if ( ! empty( $s[ $key ]['value'] ) ) {
				ob_start();
				Icons_Manager::render_icon( $s[ $key ], array( 'aria-hidden' => 'true' ) );
				return ob_get_clean();
			}
		}

		if ( 'upload' === $type ) {
			$key = $dir . '_upload';
			$url = ! empty( $s[ $key ]['url'] ) ? $s[ $key ]['url'] : '';
			$id  = ! empty( $s[ $key ]['id'] ) ? (int) $s[ $key ]['id'] : 0;
			if ( '' !== $url ) {
				$path   = wp_parse_url( $url, PHP_URL_PATH );
				$is_svg = $path && 'svg' === strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
				if ( $is_svg ) {
					ob_start();
					Icons_Manager::render_icon(
						array(
							'library' => 'svg',
							'value'   => array(
								'id'  => $id,
								'url' => $url,
							),
						),
						array( 'aria-hidden' => 'true' )
					);
					$inline = trim( ob_get_clean() );
					if ( '' !== $inline && false !== stripos( $inline, '<svg' ) ) {
						return $inline;
					}
				}
				return '<img class="ps-arrow-img" src="' . esc_url( $url ) . '" alt="" draggable="false">';
			}
		}

		return $this->default_arrow_svg( $dir );
	}

	/* Iconițele: din Caracteristici produs sau alese manual. */
	private function get_icons( $s ) {
		$source = isset( $s['icons_source'] ) ? $s['icons_source'] : 'none';
		$list   = array();

		if ( 'product' === $source && class_exists( 'Pavva_W_Data' ) ) {
			$items = Pavva_W_Data::visible_items( Pavva_W_Data::instance_data( Pavva_W_Data::META_CARACT ) );
			foreach ( $items as $it ) {
				$id = ! empty( $it['icon_id'] ) ? absint( $it['icon_id'] ) : 0;
				if ( ! $id ) continue;
				$list[] = array( 'id' => $id, 'url' => '', 'label' => isset( $it['denumire'] ) ? (string) $it['denumire'] : '' );
			}
		} elseif ( 'manual' === $source && ! empty( $s['icons_manual'] ) && is_array( $s['icons_manual'] ) ) {
			foreach ( $s['icons_manual'] as $it ) {
				$id  = ! empty( $it['icon_image']['id'] ) ? absint( $it['icon_image']['id'] ) : 0;
				$url = ! empty( $it['icon_image']['url'] ) ? (string) $it['icon_image']['url'] : '';
				if ( ! $id && '' === $url ) continue;
				$list[] = array( 'id' => $id, 'url' => $url, 'label' => isset( $it['icon_label'] ) ? (string) $it['icon_label'] : '' );
			}
		}

		$max = isset( $s['icons_max'] ) ? absint( $s['icons_max'] ) : 0;
		if ( $max > 0 ) {
			$list = array_slice( $list, 0, $max );
		}
		return $list;
	}

	/* Iconiță SVG pusă direct în pagină (ca să i se poată schimba culoarea). '' dacă nu e SVG. */
	private function inline_svg_icon( $ic ) {
		$url = $ic['url'];
		if ( '' === $url && $ic['id'] ) {
			$url = (string) wp_get_attachment_url( $ic['id'] );
		}
		$path = $url ? wp_parse_url( $url, PHP_URL_PATH ) : '';
		if ( ! $path || 'svg' !== strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) ) {
			return '';
		}
		ob_start();
		Icons_Manager::render_icon(
			array(
				'library' => 'svg',
				'value'   => array( 'id' => (int) $ic['id'], 'url' => $url ),
			),
			array( 'aria-hidden' => 'true' )
		);
		$svg = trim( ob_get_clean() );
		if ( '' === $svg || false === stripos( $svg, '<svg' ) ) {
			return '';
		}
		$label = wp_strip_all_tags( $ic['label'] );
		return '<span class="ps-icon-svg" role="img"' . ( '' !== $label ? ' aria-label="' . esc_attr( $label ) . '"' : ' aria-hidden="true"' ) . '>' . $svg . '</span>';
	}

	private function icons_html( $icons, $show_labels ) {
		if ( empty( $icons ) ) return '';
		$out = '<div class="ps-extra ps-icons">';
		foreach ( $icons as $ic ) {
			$alt = wp_strip_all_tags( $ic['label'] );
			$img = $this->inline_svg_icon( $ic );
			if ( '' === $img && $ic['id'] ) {
				$img = wp_get_attachment_image( $ic['id'], 'medium', false, array( 'class' => 'ps-icon-img', 'alt' => $alt, 'loading' => 'lazy', 'decoding' => 'async' ) );
			}
			if ( '' === $img && '' !== $ic['url'] ) {
				$img = '<img class="ps-icon-img" src="' . esc_url( $ic['url'] ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async">';
			}
			if ( '' === $img ) continue;
			$out .= '<span class="ps-icon">' . $img;
			if ( $show_labels && '' !== $alt ) {
				$out .= '<span class="ps-icon-label">' . esc_html( $alt ) . '</span>';
			}
			$out .= '</span>';
		}
		return $out . '</div>';
	}

	private function format_text( $text ) {
		return nl2br( esc_html( $text ) );
	}

	/* =====================================================================
	 * RENDER
	 * ===================================================================== */
	/*
	 * Slide-urile din produs (galeria WooCommerce) / categorie (câmpul „Imagini slider header”), cu rezerve.
	 * Întoarce array gol când trebuie folosite slide-urile manuale.
	 */
	private function dynamic_slides( $s ) {
		$source = isset( $s['slides_source'] ) ? $s['slides_source'] : 'manual';
		if ( ! in_array( $source, array( 'product', 'category' ), true ) || ! class_exists( 'Pavva_W_Data' ) ) {
			return array();
		}

		$images = array();
		if ( 'product' === $source ) {
			$pid    = (int) Pavva_W_Data::resolve_product_id();
			/* doar galeria WooCommerce, fără imaginea principală (singura sursă din produs) */
			$images = Pavva_W_Data::product_gallery_images( $pid );
		} else {
			$tid    = (int) Pavva_W_Data::current_term_id();
			$images = Pavva_W_Data::term_slider_images( $tid );
			if ( ! $images && $tid ) {
				$hero = absint( get_term_meta( $tid, Pavva_W_Data::META_HERO_ID, true ) );
				if ( $hero ) {
					$images = array( array( 'id' => $hero, 'mobile' => 0 ) );
				}
			}
		}

		$max = isset( $s['slides_source_max'] ) ? absint( $s['slides_source_max'] ) : 0;
		if ( $max > 0 ) {
			$images = array_slice( $images, 0, $max );
		}

		$slides = array();
		foreach ( $images as $i => $img ) {
			$url = wp_get_attachment_image_url( $img['id'], 'full' );
			if ( ! $url ) {
				continue;
			}
			$mob_url  = $img['mobile'] ? wp_get_attachment_image_url( $img['mobile'], 'full' ) : '';
			$slides[] = array(
				'_id'                => 'pvdyn' . $i,
				'_pavva_dyn'         => true,
				'slide_image'        => array( 'id' => $img['id'], 'url' => $url ),
				'slide_image_tablet' => array( 'id' => 0, 'url' => '' ),
				'slide_image_mobile' => $mob_url ? array( 'id' => $img['mobile'], 'url' => $mob_url ) : array( 'id' => 0, 'url' => '' ),
				'slide_alt'          => '',
				'slide_link'         => array( 'url' => '' ),
				'slide_title'        => '',
				'slide_desc'         => '',
			);
		}
		return $slides;
	}

	protected function render() {
		$s      = $this->get_settings_for_display();
		$slides = $this->dynamic_slides( $s );
		if ( ! $slides ) {
			$slides = ( isset( $s['slides'] ) && is_array( $s['slides'] ) ) ? array_values( $s['slides'] ) : array();
		}
		$count  = count( $slides );
		if ( 0 === $count ) {
			return;
		}

		$is_yes = function ( $key ) use ( $s ) {
			return isset( $s[ $key ] ) && 'yes' === $s[ $key ];
		};

		$effect = isset( $s['effect'] ) && in_array( $s['effect'], array( 'slide', 'fade', 'fade-zoom' ), true ) ? $s['effect'] : 'slide';
		$easing = isset( $s['easing'] ) && in_array( $s['easing'], array( 'smooth', 'expo', 'soft', 'linear' ), true ) ? $s['easing'] : 'smooth';
		$mode   = ( isset( $s['text_mode'] ) && 'slides' === $s['text_mode'] ) ? 'slides' : 'static';

		$options = array(
			'effect'     => $effect,
			'speed'      => isset( $s['speed'] ) && '' !== $s['speed'] ? max( 0, (int) $s['speed'] ) : 1100,
			'easing'     => $easing,
			'parallax'   => isset( $s['parallax'] ) && '' !== $s['parallax'] ? max( 0, min( 100, (float) $s['parallax'] ) ) : 30,
			'loop'       => $is_yes( 'loop' ),
			'autoplay'   => $is_yes( 'autoplay' ),
			'delay'      => ! empty( $s['autoplay_delay'] ) ? max( 1000, (int) $s['autoplay_delay'] ) : 6000,
			'pauseHover' => $is_yes( 'pause_hover' ),
			'drag'       => $is_yes( 'drag' ),
			'kenburns'   => $is_yes( 'kenburns' ),
			'textMode'   => $mode,
			'imgQuality' => ( isset( $s['image_quality'] ) && in_array( $s['image_quality'], array( 'smart', 'max', 'wp' ), true ) ) ? $s['image_quality'] : 'smart',
		);
		$priority = ! isset( $s['lcp_priority'] ) || 'yes' === $s['lcp_priority'];
		$editing  = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode();

		$svg_mode = isset( $s['arrows_svg_color_mode'] ) && in_array( $s['arrows_svg_color_mode'], array( 'auto', 'fill', 'stroke', 'both', 'original' ), true ) ? $s['arrows_svg_color_mode'] : 'auto';
		$layout   = ( isset( $s['arrows_layout'] ) && 'sides' === $s['arrows_layout'] ) ? 'sides' : 'group';
		$anim     = isset( $s['text_anim'] ) && in_array( $s['text_anim'], array( 'words', 'words-blur', 'up', 'fade', 'none' ), true ) ? $s['text_anim'] : 'words';
		$is_words = in_array( $anim, array( 'words', 'words-blur' ), true );
		$intro    = $is_words && isset( $s['text_intro'] ) && 'yes' === $s['text_intro'];
		$options['textAnim'] = $anim;
		$options['intro']    = $intro;

		$icons_mode = isset( $s['icons_color_mode'] ) && in_array( $s['icons_color_mode'], array( 'original', 'fill', 'stroke', 'both' ), true ) ? $s['icons_color_mode'] : 'original';
		$classes = array(
			'ps-hero',
			'ps-icons-' . $icons_mode,
			'ps-effect-' . $effect,
			'ps-arrows-' . $layout,
			'ps-svg-' . $svg_mode,
			'ps-text-anim-' . $anim,
		);
		if ( $options['drag'] && $count > 1 ) {
			$classes[] = 'ps-draggable';
		}
		if ( $options['kenburns'] ) {
			$classes[] = 'ps-kenburns';
		}
		if ( isset( $s['safari_mask'] ) && 'yes' === $s['safari_mask'] ) {
			$classes[] = 'ps-safari-mask';
		}
		if ( $is_words ) {
			$classes[] = 'ps-words';
		}
		if ( $intro ) {
			$classes[] = 'ps-intro';
		}

		$style_vars = '';
		if ( $options['kenburns'] ) {
			$kb_scale    = ! empty( $s['kenburns_scale'] ) ? (float) $s['kenburns_scale'] : 1.08;
			$kb_duration = ! empty( $s['kenburns_duration'] ) ? (int) $s['kenburns_duration'] : 8000;
			$style_vars  = '--ps-kb-scale:' . $kb_scale . ';--ps-kb-dur:' . $kb_duration . 'ms;';
		}

		$size = ! empty( $s['image_size'] ) ? $s['image_size'] : 'full';
		$bp   = $this->get_breakpoints();

		/* prima imagine se preîncarcă din <head> la următoarea afișare a paginii (Pavva_W_Perf) */
		if ( $priority && ! $editing && class_exists( 'Pavva_W_Perf' ) ) {
			Pavva_W_Perf::remember_lcp( $this->lcp_preload( $slides[0], $size, $bp, $options['imgQuality'] ) );
		}

		/* ---------- texte ---------- */
		$show_text   = $is_yes( 'show_text' );
		$show_title  = $show_text && $is_yes( 'show_title' );
		$show_desc   = $show_text && $is_yes( 'show_desc' );
		$static_t    = isset( $s['static_title'] ) ? (string) $s['static_title'] : '';
		$static_d    = isset( $s['static_desc'] ) ? (string) $s['static_desc'] : '';
		$title_tag   = Utils::validate_html_tag( ! empty( $s['title_tag'] ) ? $s['title_tag'] : 'h1' );
		$desc_tag    = Utils::validate_html_tag( ! empty( $s['desc_tag'] ) ? $s['desc_tag'] : 'p' );

		$titles = array();
		$descs  = array();
		if ( 'slides' === $mode ) {
			foreach ( $slides as $slide ) {
				$t        = isset( $slide['slide_title'] ) ? trim( (string) $slide['slide_title'] ) : '';
				$d        = isset( $slide['slide_desc'] ) ? trim( (string) $slide['slide_desc'] ) : '';
				$titles[] = '' !== $t ? $t : $static_t;
				$descs[]  = '' !== $d ? $d : $static_d;
			}
		} else {
			$titles[] = $static_t;
			$descs[]  = $static_d;
		}
		$has_titles = $show_title && '' !== trim( implode( '', $titles ) );
		$has_descs  = $show_desc && '' !== trim( implode( '', $descs ) );

		/* iconițe + buton (în coloana descrierii) */
		$icons_html = $show_text ? $this->icons_html( $this->get_icons( $s ), $is_yes( 'icons_labels' ) ) : '';
		$cta_html   = '';
		if ( $show_text && $is_yes( 'show_cta' ) && ! empty( $s['cta_text'] ) ) {
			$this->add_render_attribute( 'ps_cta', 'class', 'ps-cta' );
			if ( ! empty( $s['cta_link']['url'] ) ) {
				$this->add_link_attributes( 'ps_cta', $s['cta_link'] );
			} else {
				$this->add_render_attribute( 'ps_cta', 'href', '#' );
			}
			$cta_html = '<a ' . $this->get_render_attribute_string( 'ps_cta' ) . '>' . esc_html( $s['cta_text'] ) . '</a>';
		}
		$oferta_html = $show_text && $is_yes( 'show_oferta' ) ? $this->oferta_button_html( $s ) : '';
		if ( '' !== $oferta_html ) {
			$cta_html = 'before' === ( $s['oferta_order'] ?? 'after' ) ? $oferta_html . $cta_html : $cta_html . $oferta_html;
		}
		if ( '' !== $cta_html ) {
			$cta_html = '<div class="ps-extra ps-cta-wrap">' . $cta_html . '</div>';
		}
		$has_extras = '' !== $icons_html || '' !== $cta_html;

		$slider_label = '' !== trim( $static_t ) ? trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $static_t ) ) ) : 'Slider';
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-ps="<?php echo esc_attr( wp_json_encode( $options ) ); ?>"<?php echo $style_vars ? ' style="' . esc_attr( $style_vars ) . '"' : ''; ?>>
			<div class="ps-frame">
				<div class="ps-slider" role="region" aria-roledescription="carousel" aria-label="<?php echo esc_attr( $slider_label ); ?>" tabindex="0">
					<div class="ps-viewport">
						<?php
						foreach ( $slides as $i => $slide ) :
							$key = 'ps_slide_' . $i;
							$this->add_render_attribute(
								$key,
								array(
									'class'                => array( 'ps-slide', 'elementor-repeater-item-' . ( isset( $slide['_id'] ) ? $slide['_id'] : $i ), ! empty( $slide['_pavva_dyn'] ) ? 'ps-slide-dyn' : '' ),
									'role'                 => 'group',
									'aria-roledescription' => 'slide',
									'aria-label'           => ( $i + 1 ) . ' din ' . $count,
									'aria-hidden'          => 0 === $i ? 'false' : 'true',
								)
							);
							if ( 0 === $i ) {
								$this->add_render_attribute( $key, 'class', 'is-active' );
							}

							$has_link = ! empty( $slide['slide_link']['url'] );
							$link_key = 'ps_link_' . $i;
							if ( $has_link ) {
								$this->add_link_attributes( $link_key, $slide['slide_link'] );
								$this->add_render_attribute( $link_key, 'class', 'ps-slide-inner ps-slide-link' );
								$this->add_render_attribute( $link_key, 'draggable', 'false' );
								/* link fără text vizibil (doar imagine): primește un nume pentru cititoare de ecran și agenți AI */
								$this->add_render_attribute( $link_key, 'aria-label', $this->slide_link_label( $slide ), true );
								if ( 0 !== $i ) {
									$this->add_render_attribute( $link_key, 'tabindex', '-1' );
								}
							}
							?>
							<div <?php echo $this->get_render_attribute_string( $key ); // phpcs:ignore ?>>
								<?php if ( $has_link ) : ?>
									<a <?php echo $this->get_render_attribute_string( $link_key ); // phpcs:ignore ?>>
								<?php else : ?>
									<div class="ps-slide-inner">
								<?php endif; ?>
									<?php echo $this->get_picture_html( $slide, $i, $size, $bp, $options['imgQuality'], $priority, ! $editing ); // phpcs:ignore -- generat cu funcțiile WordPress ?>
									<span class="ps-overlay" aria-hidden="true"></span>
								<?php if ( $has_link ) : ?>
									</a>
								<?php else : ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>

					<?php if ( $is_yes( 'show_arrows' ) && $count > 1 ) : ?>
						<div class="ps-arrows">
							<button type="button" class="ps-arrow ps-prev" aria-label="<?php echo esc_attr( ! empty( $s['prev_label'] ) ? $s['prev_label'] : 'Slide-ul anterior' ); ?>"><?php echo $this->get_arrow_html( $s, 'prev' ); // phpcs:ignore ?></button>
							<button type="button" class="ps-arrow ps-next" aria-label="<?php echo esc_attr( ! empty( $s['next_label'] ) ? $s['next_label'] : 'Slide-ul următor' ); ?>"><?php echo $this->get_arrow_html( $s, 'next' ); // phpcs:ignore ?></button>
						</div>
					<?php endif; ?>

					<?php if ( $count > 1 && isset( $s['pagination'] ) && 'dots' === $s['pagination'] ) : ?>
						<div class="ps-pagination ps-dots">
							<?php for ( $i = 0; $i < $count; $i++ ) : ?>
								<button type="button" class="ps-dot<?php echo 0 === $i ? ' is-active' : ''; ?>" aria-label="<?php echo esc_attr( 'Mergi la slide-ul ' . ( $i + 1 ) ); ?>"<?php echo 0 === $i ? ' aria-current="true"' : ''; ?>></button>
							<?php endfor; ?>
						</div>
					<?php elseif ( $count > 1 && isset( $s['pagination'] ) && 'fraction' === $s['pagination'] ) : ?>
						<div class="ps-pagination ps-fraction" aria-hidden="true">
							<span class="ps-frac-current">1</span><span class="ps-frac-sep"> / </span><span class="ps-frac-total"><?php echo (int) $count; ?></span>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( $has_titles || $has_descs || $has_extras ) : ?>
					<div class="ps-text">
						<div class="ps-text-groups">
							<?php
							$group_count = 'slides' === $mode ? $count : 1;
							for ( $g = 0; $g < $group_count; $g++ ) :
								$t      = isset( $titles[ $g ] ) ? $titles[ $g ] : '';
								$d      = isset( $descs[ $g ] ) ? $descs[ $g ] : '';
								$active = 0 === $g;
								?>
								<div class="ps-text-inner ps-group<?php echo $active ? ' is-active' : ''; ?>"<?php echo $active ? '' : ' aria-hidden="true"'; ?>>
									<?php if ( $has_titles ) : ?>
										<div class="ps-col ps-col-title">
											<?php if ( '' !== trim( $t ) ) : ?>
												<<?php echo $title_tag; // phpcs:ignore ?> class="ps-t ps-title<?php echo $active ? ' is-active' : ''; ?>"><?php echo $this->format_text( $t ); // phpcs:ignore -- escapat în format_text ?></<?php echo $title_tag; // phpcs:ignore ?>>
											<?php endif; ?>
										</div>
									<?php endif; ?>
									<?php if ( $has_descs || $has_extras ) : ?>
										<div class="ps-col ps-col-desc">
											<?php echo $icons_html; // phpcs:ignore -- generat cu funcțiile WordPress, escapat ?>
											<?php if ( $has_descs && '' !== trim( $d ) ) : ?>
												<<?php echo $desc_tag; // phpcs:ignore ?> class="ps-t ps-desc<?php echo $active ? ' is-active' : ''; ?>"><?php echo $this->format_text( $d ); // phpcs:ignore -- escapat în format_text ?></<?php echo $desc_tag; // phpcs:ignore ?>>
											<?php endif; ?>
											<?php echo $cta_html; // phpcs:ignore -- escapat ?>
										</div>
									<?php endif; ?>
								</div>
							<?php endfor; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
