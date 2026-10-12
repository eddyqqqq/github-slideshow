<?php
/**
 * Pavva Widgets – câmpuri selectabile în Elementor (Dynamic Tags)
 *
 * În orice widget, la iconița de date dinamice, apar grupurile
 * „Pavva – Produs” și „Pavva – Categorie”. Citesc produsul / categoria curentă.
 *
 * @package Pavva_Widgets
 * @author  Imagient <https://imagient.ro/>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module as TagsModule;

/* ======================================================================
 * Baza pentru tag-urile de text: setarea „Format” (text simplu / HTML)
 * ====================================================================== */
abstract class Pavva_W_Text_Tag extends \Elementor\Core\DynamicTags\Tag {

	/** Formatul implicit al tag-ului: 'text' sau 'html'. */
	protected function default_format() {
		return 'text';
	}

	/** Valoarea brută (poate conține HTML). */
	abstract protected function raw_value();

	public function get_categories() {
		return array( TagsModule::TEXT_CATEGORY );
	}

	protected function register_controls() {
		$this->add_control(
			'pavva_format',
			array(
				'label'       => 'Format',
				'type'        => Controls_Manager::SELECT,
				'default'     => $this->default_format(),
				'options'     => array(
					'text' => 'Text simplu (pentru titluri, slider)',
					'html' => 'HTML – paragrafe, bold (pentru Text Editor)',
				),
			)
		);
	}

	public function render() {
		$raw = (string) $this->raw_value();
		if ( '' === trim( $raw ) ) {
			return;
		}
		$format = $this->get_settings( 'pavva_format' );
		if ( 'html' === $format ) {
			echo wp_kses_post( wpautop( $raw ) ); // phpcs:ignore
			return;
		}
		echo self::to_plain_text( $raw ); // phpcs:ignore -- text fără tag-uri
	}

	/** HTML → text simplu, păstrând paragrafele ca rânduri noi. */
	public static function to_plain_text( $html ) {
		$t = (string) $html;
		$t = preg_replace( '#<br\s*/?>#i', "\n", $t );
		$t = preg_replace( '#</(p|div|li|h[1-6])>#i', "\n", $t );
		$t = wp_strip_all_tags( $t, false );
		$t = html_entity_decode( $t, ENT_QUOTES, 'UTF-8' );
		$t = wp_strip_all_tags( $t, false );            // siguranță după decodare
		$t = preg_replace( "/[ \t]+\n/", "\n", $t );
		$t = preg_replace( "/\n{3,}/", "\n\n", $t );
		return trim( $t );
	}
}

/* ======================================================================
 * PRODUS
 * ====================================================================== */
abstract class Pavva_W_Product_Text_Tag extends Pavva_W_Text_Tag {
	public function get_group() {
		return 'pavva-produs';
	}
	protected function product_id() {
		return (int) Pavva_W_Data::resolve_product_id();
	}
}

class Pavva_W_Tag_Product_Name extends Pavva_W_Product_Text_Tag {
	public function get_name() { return 'pavva-produs-nume'; }
	public function get_title() { return 'Nume produs'; }
	protected function register_controls() {}
	protected function raw_value() {
		$id = $this->product_id();
		return $id ? get_the_title( $id ) : '';
	}
	public function render() {
		echo self::to_plain_text( (string) $this->raw_value() ); // phpcs:ignore -- text fără tag-uri, ca „Post Title” din Elementor
	}
}

class Pavva_W_Tag_Product_Short_Desc extends Pavva_W_Product_Text_Tag {
	public function get_name() { return 'pavva-produs-descriere-scurta'; }
	public function get_title() { return 'Descriere scurtă'; }
	protected function raw_value() {
		$id = $this->product_id();
		return $id ? get_post_field( 'post_excerpt', $id ) : '';
	}
}

class Pavva_W_Tag_Product_Summary extends Pavva_W_Product_Text_Tag {
	public function get_name() { return 'pavva-produs-descriere-sinteza'; }
	public function get_title() { return 'Descriere sinteză (carduri)'; }
	protected function raw_value() {
		$id = $this->product_id();
		return $id ? get_post_meta( $id, Pavva_W_Data::META_DESCRIERE_SINTEZA, true ) : '';
	}
}

class Pavva_W_Tag_Product_Description extends Pavva_W_Product_Text_Tag {
	public function get_name() { return 'pavva-produs-descriere'; }
	public function get_title() { return 'Descriere produs'; }
	protected function default_format() { return 'html'; }
	protected function raw_value() {
		$id = $this->product_id();
		if ( ! $id ) return '';
		/* Fără filtrul the_content: evită conținut dublat sau conflicte cu Elementor. */
		return do_shortcode( (string) get_post_field( 'post_content', $id ) );
	}
}

/*
 * Link „Solicită ofertă” – trimite numele produsului / categoriei în formular.
 * Câte un tag în fiecare grup: Elementor afișează în listă doar tag-urile
 * cu un singur grup (un tag cu două grupuri nu apărea nicăieri).
 */
abstract class Pavva_W_Quote_Link_Base extends \Elementor\Core\DynamicTags\Data_Tag {
	public function get_title() { return 'Link „Solicită ofertă”'; }
	public function get_categories() { return array( TagsModule::URL_CATEGORY ); }

	protected function register_controls() {
		$this->add_control(
			'pavva_page',
			array(
				'label'       => 'Pagina formularului',
				'description' => 'Adresa paginii cu formularul de ofertă.',
				'type'        => Controls_Manager::TEXT,
				'default'     => '/solicita-oferta/',
			)
		);
		$this->add_control(
			'pavva_param',
			array(
				'label'       => 'Parametru produs',
				'description' => 'Numele produsului ajunge în adresă ca ?produs=… (gol = nu se trimite).',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'produs',
			)
		);
		$this->add_control(
			'pavva_cat_param',
			array(
				'label'       => 'Parametru categorie',
				'description' => 'Categoria produsului (sau categoria paginii) ajunge ca ?categorie=… (gol = nu se trimite). Pavva Widgets le completează singur în formular.',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'categorie',
			)
		);
	}

	/* Categoria principală a produsului: cea aleasă în Yoast, altfel prima. */
	private static function product_category_name( $product_id ) {
		$primary = absint( get_post_meta( $product_id, '_yoast_wpseo_primary_product_cat', true ) );
		if ( $primary ) {
			$term = get_term( $primary, 'product_cat' );
			if ( $term instanceof WP_Term ) return $term->name;
		}
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( 'uncategorized' !== $term->slug ) return $term->name;
			}
		}
		return '';
	}

	private static function clean( $text ) {
		return rawurlencode( wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) ) );
	}

	public function get_value( array $options = array() ) {
		$page      = trim( (string) $this->get_settings( 'pavva_page' ) );
		$param     = sanitize_key( (string) $this->get_settings( 'pavva_param' ) );
		$cat_param = $this->get_settings( 'pavva_cat_param' );
		$cat_param = null === $cat_param ? 'categorie' : sanitize_key( (string) $cat_param );
		$url       = '' === $page ? home_url( '/' ) : ( preg_match( '#^https?://#i', $page ) ? $page : home_url( '/' . ltrim( $page, '/' ) ) );

		$args = array();
		/* pe pagina unei categorii, „produsul curent” ar fi doar primul produs din listă: acolo trimitem numai categoria */
		$on_cat = is_tax( 'product_cat' );
		$pid    = $on_cat ? 0 : (int) Pavva_W_Data::resolve_product_id();
		if ( $pid ) {
			if ( '' !== $param ) $args[ $param ] = self::clean( get_the_title( $pid ) );
			$cat = self::product_category_name( $pid );
			if ( '' !== $cat_param && '' !== $cat ) $args[ $cat_param ] = self::clean( $cat );
		} else {
			$tid = (int) Pavva_W_Data::current_term_id();
			$term = $tid ? get_term( $tid, 'product_cat' ) : null;
			if ( $term instanceof WP_Term ) {
				/* pe o categorie: doar categoria */
				$key = '' !== $cat_param ? $cat_param : $param;
				if ( '' !== $key ) $args[ $key ] = self::clean( $term->name );
			}
		}
		$args = array_filter( $args, 'strlen' );
		if ( $args ) {
			$url = add_query_arg( $args, $url );
		}
		return esc_url_raw( $url );
	}
}

/* Numele de tag „pavva-link-oferta” rămâne la produs: linkurile deja setate merg în continuare. */
class Pavva_W_Tag_Quote_Link extends Pavva_W_Quote_Link_Base {
	public function get_name() { return 'pavva-link-oferta'; }
	public function get_group() { return 'pavva-produs'; }
}

class Pavva_W_Tag_Category_Quote_Link extends Pavva_W_Quote_Link_Base {
	public function get_name() { return 'pavva-categorie-link-oferta'; }
	public function get_group() { return 'pavva-categorie'; }
}

/* ======================================================================
 * CATEGORIE
 * ====================================================================== */
abstract class Pavva_W_Category_Text_Tag extends Pavva_W_Text_Tag {
	public function get_group() {
		return 'pavva-categorie';
	}
	protected function term_id() {
		return (int) Pavva_W_Data::current_term_id();
	}
	protected function meta( $key ) {
		$id = $this->term_id();
		return $id ? (string) get_term_meta( $id, $key, true ) : '';
	}
}

class Pavva_W_Tag_Category_Name extends Pavva_W_Category_Text_Tag {
	public function get_name() { return 'pavva-categorie-nume'; }
	public function get_title() { return 'Nume categorie'; }
	protected function register_controls() {}
	protected function raw_value() {
		$id = $this->term_id();
		if ( ! $id ) return '';
		$term = get_term( $id, 'product_cat' );
		return $term instanceof WP_Term ? $term->name : '';
	}
	public function render() {
		echo self::to_plain_text( (string) $this->raw_value() ); // phpcs:ignore -- text fără tag-uri, ca „Post Title” din Elementor
	}
}

class Pavva_W_Tag_Category_Intro extends Pavva_W_Category_Text_Tag {
	public function get_name() { return 'pavva-categorie-intro'; }
	public function get_title() { return 'Text intro categorie'; }
	protected function raw_value() { return $this->meta( Pavva_W_Data::META_INTRO ); }
}

class Pavva_W_Tag_Category_Benefits_Title extends Pavva_W_Category_Text_Tag {
	public function get_name() { return 'pavva-categorie-avantaje-titlu'; }
	public function get_title() { return 'Titlu secțiune Avantaje'; }
	protected function raw_value() { return $this->meta( Pavva_W_Data::META_BENEFITS_TITLE ); }
}

class Pavva_W_Tag_Category_Benefits_Content extends Pavva_W_Category_Text_Tag {
	public function get_name() { return 'pavva-categorie-avantaje-continut'; }
	public function get_title() { return 'Conținut Avantaje'; }
	protected function default_format() { return 'html'; }
	protected function raw_value() { return $this->meta( Pavva_W_Data::META_BENEFITS_CONTENT ); }
}

class Pavva_W_Tag_Category_Why_Title extends Pavva_W_Category_Text_Tag {
	public function get_name() { return 'pavva-categorie-de-ce-titlu'; }
	public function get_title() { return 'Titlu secțiune De ce să alegi'; }
	protected function raw_value() { return $this->meta( Pavva_W_Data::META_WHY_TITLE ); }
}

class Pavva_W_Tag_Category_Why_Content extends Pavva_W_Category_Text_Tag {
	public function get_name() { return 'pavva-categorie-de-ce-continut'; }
	public function get_title() { return 'Conținut De ce să alegi'; }
	protected function default_format() { return 'html'; }
	protected function raw_value() { return $this->meta( Pavva_W_Data::META_WHY_CONTENT ); }
}

class Pavva_W_Tag_Category_Image extends \Elementor\Core\DynamicTags\Data_Tag {
	public function get_name() { return 'pavva-categorie-imagine'; }
	public function get_title() { return 'Imagine categorie'; }
	public function get_group() { return 'pavva-categorie'; }
	public function get_categories() { return array( TagsModule::IMAGE_CATEGORY ); }

	public function get_value( array $options = array() ) {
		$tid = (int) Pavva_W_Data::current_term_id();
		$id  = $tid ? absint( get_term_meta( $tid, Pavva_W_Data::META_HERO_ID, true ) ) : 0;
		if ( ! $id ) {
			return array( 'id' => '', 'url' => '' );
		}
		return array(
			'id'  => $id,
			'url' => (string) wp_get_attachment_image_url( $id, 'full' ),
		);
	}
}

/* ======================================================================
 * Înregistrare
 * ====================================================================== */
final class Pavva_W_Dynamic_Tags {

	public static function register( $manager ) {
		$manager->register_group( 'pavva-produs', array( 'title' => 'Pavva – Produs' ) );
		$manager->register_group( 'pavva-categorie', array( 'title' => 'Pavva – Categorie' ) );

		$tags = array(
			'Pavva_W_Tag_Product_Name',
			'Pavva_W_Tag_Product_Short_Desc',
			'Pavva_W_Tag_Product_Summary',
			'Pavva_W_Tag_Product_Description',
			'Pavva_W_Tag_Quote_Link',
			'Pavva_W_Tag_Category_Name',
			'Pavva_W_Tag_Category_Image',
			'Pavva_W_Tag_Category_Intro',
			'Pavva_W_Tag_Category_Benefits_Title',
			'Pavva_W_Tag_Category_Benefits_Content',
			'Pavva_W_Tag_Category_Why_Title',
			'Pavva_W_Tag_Category_Why_Content',
			'Pavva_W_Tag_Category_Quote_Link',
		);
		foreach ( $tags as $class ) {
			try {
				$manager->register( new $class() );
			} catch ( \Throwable $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Pavva Widgets – tag ' . $class . ': ' . $e->getMessage() ); // phpcs:ignore
				}
			}
		}
	}
}
