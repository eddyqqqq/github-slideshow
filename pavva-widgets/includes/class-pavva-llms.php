<?php
/**
 * Pavva Widgets – llms.txt (Setări → Pavva – Viteză)
 *
 * Un „cuprins” în text simplu pentru asistenții AI (ChatGPT, Gemini, Claude…): ce este firma și ce pagini,
 * categorii și produse are site-ul. Se generează din conținutul WordPress și se actualizează singur.
 * Dacă există un fișier llms.txt real în rădăcina site-ului (ex. creat de Yoast SEO), serverul îl
 * trimite pe acela și codul de aici nu mai rulează.
 *
 * @package Pavva_Widgets
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_W_Llms {

	const CACHE = 'pavva_llms_txt_2';

	/* textul de prezentare implicit (se poate schimba din setări) */
	const INTRO = "Producător de pavaje și prefabricate din beton vibropresat din Constanța, pe o linie de producție complet automatizată: pavele premium și standard, dale, borduri, rigole, bolțari, garduri și elemente pentru amenajarea grădinii. Livrare direct din fabrică în 24–48 de ore, cu transport și descărcare, și consultanță pentru alegerea produselor.";

	public static function init() {
		add_action( 'parse_request', array( __CLASS__, 'serve' ), 0 );
		foreach ( array( 'save_post', 'deleted_post', 'edited_term', 'created_term', 'delete_term' ) as $h ) {
			add_action( $h, array( __CLASS__, 'flush' ) );
		}
	}

	public static function flush() {
		delete_transient( self::CACHE );
	}

	public static function serve( $wp ) {
		$path = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore
		$base = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		if ( ( $base ? $base . '/' : '' ) . 'llms.txt' !== $path ) return;
		$txt = get_transient( self::CACHE );
		if ( ! is_string( $txt ) || '' === $txt ) {
			$txt = self::build();
			set_transient( self::CACHE, $txt, 12 * HOUR_IN_SECONDS );
		}
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		header( 'Cache-Control: public, max-age=3600' );
		echo $txt; // phpcs:ignore -- text simplu
		exit;
	}

	private static function clean( $t, int $max = 200 ): string {
		$t = (string) $t;
		/* unele descrieri vin codate de două ori (&amp;lt;p…): decodăm până nu se mai schimbă nimic */
		for ( $i = 0; $i < 3; $i++ ) {
			$d = html_entity_decode( $t, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( $d === $t ) break;
			$t = $d;
		}
		$t = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $t ) ) );
		if ( mb_strlen( $t ) > $max ) $t = rtrim( mb_substr( $t, 0, $max - 1 ), " ,.;:-–" ) . '…';
		return $t;
	}

	private static function noindex_post( int $id ): bool {
		return '1' === (string) get_post_meta( $id, '_yoast_wpseo_meta-robots-noindex', true );
	}

	private static function line( string $title, string $url, string $desc ): string {
		$title = html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return '- [' . str_replace( array( '[', ']' ), '', $title ) . '](' . esc_url_raw( $url ) . ')' . ( '' !== $desc ? ': ' . $desc : '' ) . "\n";
	}

	public static function build(): string {
		$s     = class_exists( 'Pavva_W_Perf' ) ? Pavva_W_Perf::settings() : array();
		$name  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$intro = isset( $s['llms_intro'] ) && '' !== trim( (string) $s['llms_intro'] ) ? (string) $s['llms_intro'] : self::INTRO;
		$out   = '# ' . $name . "\n\n> " . self::clean( $intro, 600 ) . "\n\n";
		$out  .= 'Site: ' . home_url( '/' ) . "\n";
		$out  .= 'Limba: română' . "\n\n";

		/* pagini (fără coș / checkout / cont și fără cele marcate noindex în Yoast) */
		$skip = array();
		foreach ( array( 'cart', 'checkout', 'myaccount', 'shop' ) as $wc ) {
			$id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( $wc ) : 0;
			if ( $id > 0 ) $skip[] = $id;
		}
		$pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC', 'exclude' => $skip ) );
		$front = (int) get_option( 'page_on_front' );
		$main  = '';
		$legal = '';
		foreach ( $pages as $p ) {
			if ( self::noindex_post( $p->ID ) || 'multumim' === $p->post_name ) continue;
			$desc = self::clean( get_post_meta( $p->ID, '_yoast_wpseo_metadesc', true ) ?: $p->post_excerpt );
			$title = $p->ID === $front ? 'Prima pagină' : get_the_title( $p );
			$row = self::line( $title, get_permalink( $p ), $desc );
			if ( preg_match( '/politica|termeni|cookie|gdpr|confidential/i', $p->post_name ) ) $legal .= $row; else $main .= $row;
		}
		if ( $main ) $out .= "## Pagini\n\n" . $main . "\n";

		/* categorii de produse */
		if ( taxonomy_exists( 'product_cat' ) ) {
			$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'menu_order' ) );
			if ( ! is_wp_error( $terms ) && $terms ) {
				$out .= "## Categorii de produse\n\n";
				foreach ( $terms as $t ) {
					if ( 'uncategorized' === $t->slug || 'necategorizat' === $t->slug ) continue;
					$link = get_term_link( $t );
					if ( is_wp_error( $link ) ) continue;
					$out .= self::line( $t->name, $link, self::clean( $t->description ) );
				}
				$out .= "\n";
			}
		}

		/* produse */
		if ( post_type_exists( 'product' ) ) {
			$products = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 300, 'orderby' => 'title', 'order' => 'ASC' ) );
			if ( $products ) {
				$out .= "## Produse\n\n";
				foreach ( $products as $p ) {
					if ( self::noindex_post( $p->ID ) ) continue;
					$out .= self::line( get_the_title( $p ), get_permalink( $p ), self::clean( $p->post_excerpt ?: $p->post_content, 160 ) );
				}
				$out .= "\n";
			}
		}

		if ( $legal ) $out .= "## Informații legale\n\n" . $legal . "\n";
		return $out;
	}
}
