<?php
/**
 * Pavva Widgets – lista de ofertă (mai multe produse într-o singură cerere)
 *
 * Scriptul assets/oferta-lista.js ține lista în browserul vizitatorului și o leagă de formularul de ofertă.
 * Aici: încărcarea scriptului, datele produsului paginii și setările (pagina formularului, textele).
 *
 * @package Pavva_Widgets
 * @author  Imagient <https://imagient.ro/>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_W_Lista {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_footer', array( __CLASS__, 'current_product' ), 5 );
	}

	/* pagina cu formularul (se poate schimba din fiecare widget; aici e valoarea implicită) */
	public static function form_url(): string {
		return (string) apply_filters( 'pavva_lista_form_url', home_url( '/solicita-oferta/' ) );
	}

	/* adresa aleasă în widget: relativă (/solicita-oferta/) sau completă */
	public static function resolve_url( $url ): string {
		$url = trim( (string) $url );
		if ( '' === $url ) return self::form_url();
		return preg_match( '#^https?://#i', $url ) ? $url : home_url( '/' . ltrim( $url, '/' ) );
	}

	public static function enqueue() {
		if ( is_admin() ) return;
		wp_enqueue_style( 'pavva-oferta-lista', PAVVA_WIDGETS_URL . 'assets/oferta-lista.css', array(), pavva_widgets_asset_version( 'assets/oferta-lista.css' ) );
		wp_enqueue_script( 'pavva-oferta-lista', PAVVA_WIDGETS_URL . 'assets/oferta-lista.js', array(), pavva_widgets_asset_version( 'assets/oferta-lista.js' ), true );
		wp_localize_script(
			'pavva-oferta-lista',
			'PavvaListaCfg',
			array(
				'form'     => self::form_url(),
				'autoList' => (bool) apply_filters( 'pavva_lista_auto_list', true ),
				/* bara de jos „Oferta ta · Vezi oferta”, cât lista are produse */
				'bar'      => (bool) apply_filters( 'pavva_lista_bar', true ),
				/* panoul lateral cu produsele (se deschide din iconița „Oferta ta”, meniu, bara de jos) */
				'drawer'   => (bool) apply_filters( 'pavva_lista_drawer', true ),
				'i18n'     => array(
					'added'     => 'Adăugat în ofertă',
					'removed'   => 'Scos din ofertă',
					'see'       => 'Vezi oferta',
					'remove'    => 'Scoate din ofertă',
					'pick'      => '+ Adaugă alt produs',
					'one'       => '1 produs',
					'many'      => 'produse',
					'title'     => 'Oferta ta',
					'empty'     => 'niciun produs',
					'cleared'   => 'Lista a fost golită',
					'full'      => 'Lista are deja numărul maxim de produse.',
					'listTitle' => 'Produsele tale',
					'listEmpty' => 'Alege produsele dorite din lista de mai jos. Poți adăuga mai multe.',
					'hide'      => 'Ascunde bara',
					'close'     => 'Închide',
					'cta'       => 'Solicită ofertă',
					'more'      => 'Continuă să alegi',
					'browse'    => 'Vezi produsele',
					'dwEmpty'   => 'Nu ai adăugat încă niciun produs. Apasă „Adaugă la ofertă” pe produsele care te interesează.',
				),
			)
		);
	}

	/* categoria principală a produsului: cea aleasă în Yoast, altfel prima */
	public static function primary_cat( int $pid ): string {
		$primary = absint( get_post_meta( $pid, '_yoast_wpseo_primary_product_cat', true ) );
		if ( $primary ) {
			$t = get_term( $primary, 'product_cat' );
			if ( $t instanceof WP_Term ) return self::txt( $t->name );
		}
		$terms = get_the_terms( $pid, 'product_cat' );
		if ( is_array( $terms ) ) {
			foreach ( $terms as $t ) {
				if ( 'uncategorized' !== $t->slug ) return self::txt( $t->name );
			}
		}
		return '';
	}

	private static function txt( $s ): string {
		return trim( wp_strip_all_tags( html_entity_decode( (string) $s, ENT_QUOTES, 'UTF-8' ) ) );
	}

	/* datele unui produs pentru listă */
	public static function item( int $pid ): array {
		if ( ! $pid || 'product' !== get_post_type( $pid ) ) return array();
		$thumb = get_post_thumbnail_id( $pid );
		$img   = $thumb ? wp_get_attachment_image_url( $thumb, 'thumbnail' ) : '';
		return array(
			'id'   => $pid,
			'name' => self::txt( get_the_title( $pid ) ),
			'cat'  => self::primary_cat( $pid ),
			'url'  => (string) get_permalink( $pid ),
			'img'  => $img ? (string) $img : '',
		);
	}

	/* variantele pe care clientul le poate alege la adăugare: culorile (cu thumbnail) și grosimile (din Dimensiuni) */
	public static function variants( int $pid ): array {
		$out = array( 'colors' => array(), 'thick' => array() );
		if ( ! $pid || ! class_exists( 'Pavva_W_Data' ) ) return $out;
		foreach ( Pavva_W_Data::visible_items( Pavva_W_Data::items( $pid, Pavva_W_Data::META_CULORI ) ) as $c ) {
			$name = self::txt( $c['denumire'] ?? '' );
			if ( '' === $name ) continue;
			$tid   = absint( $c['thumbnail_id'] ?? 0 );
			$thumb = $tid ? wp_get_attachment_image_url( $tid, 'thumbnail' ) : '';
			$out['colors'][] = array( 'n' => $name, 't' => $thumb ? (string) $thumb : '' );
		}
		$seen = array();
		foreach ( Pavva_W_Data::visible_items( Pavva_W_Data::items( $pid, Pavva_W_Data::META_DIM ) ) as $row ) {
			foreach ( Pavva_W_Data::dimension_item_specs( $row, $pid ) as $spec ) {
				$label = (string) ( $spec['eticheta'] ?? '' );
				if ( ! preg_match( '/grosim/i', remove_accents( $label ) ) ) continue;
				$val = self::txt( $spec['valoare'] ?? '' );
				if ( '' === $val ) continue;
				/* „4” cu eticheta „Grosime (cm)” → „4 cm” */
				if ( preg_match( '/^[\d.,]+$/', $val ) && preg_match( '/\((cm|mm)\)/i', $label, $u ) ) $val .= ' ' . strtolower( $u[1] );
				$k = strtolower( $val );
				if ( isset( $seen[ $k ] ) ) continue;
				$seen[ $k ]       = true;
				$out['thick'][] = $val;
			}
		}
		usort( $out['thick'], function ( $a, $b ) { return floatval( str_replace( ',', '.', $a ) ) <=> floatval( str_replace( ',', '.', $b ) ); } );
		return $out;
	}

	/* datele produsului + variantele (pentru butoanele „Adaugă la ofertă”) */
	public static function item_full( int $pid ): array {
		$it = self::item( $pid );
		if ( ! $it ) return $it;
		$v = self::variants( $pid );
		if ( $v['colors'] || $v['thick'] ) $it['opts'] = $v;
		return $it;
	}

	/* pe pagina unui produs: datele lui, pentru elementele cu clasa pv-oferta-add / pv-oferta-go */
	public static function current_product() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) return;
		$item = self::item_full( (int) get_queried_object_id() );
		if ( ! $item ) return;
		echo '<script id="pavva-lista-produs">window.PavvaListaCurrent=' . wp_json_encode( $item ) . ';</script>' . "\n"; // phpcs:ignore
	}

	/* toate produsele (pentru alegerea din widgetul Buton ofertă) */
	public static function product_options(): array {
		$out = array();
		$ids = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC', 'fields' => 'ids' ) );
		foreach ( $ids as $id ) $out[ $id ] = self::txt( get_the_title( $id ) );
		return $out;
	}
}
