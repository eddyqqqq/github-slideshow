<?php
/**
 * Pavva Widgets – site în mai multe limbi (cu Polylang)
 *
 *  - pavva_t( 'text' ): textele fixe din pluginurile Pavva, traduse după limba paginii (dicționar în opțiunea pavva_i18n_strings)
 *  - REST (doar administratori): copiază o pagină / un produs / o categorie în altă limbă, cu design (Elementor) și câmpuri,
 *    apoi pune textele traduse; legătura dintre traduceri o face Polylang
 *  - în admin, Pagini și Produse se listează în ordinea lor (Atribute → Ordine), nu după dată
 *
 * @package Pavva_Widgets
 * @author  Imagient <https://imagient.ro/>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_W_I18n {

	const OPT = 'pavva_i18n_strings';

	/* câmpuri care nu se copiază în traducere (se refac singure) */
	const SKIP_META = array( '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date', '_pavva_lcp', '_elementor_css', '_elementor_element_cache', '_elementor_page_assets', '_elementor_screenshot', '_pavva_import_sursa' );

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'admin_order' ) );
	}

	public static function on(): bool {
		return function_exists( 'pll_current_language' ) && function_exists( 'pll_get_post' );
	}

	/* limba paginii afișate (slug: ro, en …); fără Polylang: '' */
	public static function lang(): string {
		if ( ! function_exists( 'pll_current_language' ) ) return '';
		$l = pll_current_language( 'slug' );
		return is_string( $l ) ? $l : '';
	}

	public static function default_lang(): string {
		return function_exists( 'pll_default_language' ) ? (string) pll_default_language( 'slug' ) : '';
	}

	/* textul în limba curentă; în limba implicită (română) sau fără traducere rămâne cel original */
	public static function t( $text ) {
		$l = self::lang();
		if ( '' === $l || $l === self::default_lang() ) return $text;
		static $dict = null;
		if ( null === $dict ) { $dict = get_option( self::OPT, array() ); if ( ! is_array( $dict ) ) $dict = array(); }
		return isset( $dict[ $l ][ $text ] ) && '' !== $dict[ $l ][ $text ] ? $dict[ $l ][ $text ] : $text;
	}

	/* ---------------------------------------------------------------- ordinea în admin */

	public static function admin_order( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || isset( $_GET['orderby'] ) ) return;
		global $pagenow;
		if ( 'edit.php' !== $pagenow ) return;
		$type = $q->get( 'post_type' );
		if ( in_array( $type, array( 'page', 'product' ), true ) ) {
			$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
		}
	}

	/* ---------------------------------------------------------------- REST */

	public static function routes() {
		$admin = function () { return current_user_can( 'manage_options' ); };
		$ns    = 'pavva/v1';
		register_rest_route( $ns, '/i18n/post/(?P<id>\d+)', array(
			array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'get_post' ), 'permission_callback' => $admin ),
		) );
		register_rest_route( $ns, '/i18n/post/(?P<id>\d+)/translate', array(
			array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'translate_post' ), 'permission_callback' => $admin ),
		) );
		register_rest_route( $ns, '/i18n/term/(?P<id>\d+)', array(
			array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'get_term' ), 'permission_callback' => $admin ),
		) );
		register_rest_route( $ns, '/i18n/term/(?P<id>\d+)/translate', array(
			array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'translate_term' ), 'permission_callback' => $admin ),
		) );
		register_rest_route( $ns, '/i18n/strings', array(
			array( 'methods' => 'GET', 'callback' => function () { return get_option( self::OPT, array() ); }, 'permission_callback' => $admin ),
			array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'save_strings' ), 'permission_callback' => $admin ),
		) );
		register_rest_route( $ns, '/i18n/order', array(
			array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'save_order' ), 'permission_callback' => $admin ),
		) );
	}

	private static function meta_of( $id ): array {
		$out = array();
		foreach ( get_post_meta( $id ) as $k => $vals ) {
			if ( in_array( $k, self::SKIP_META, true ) ) continue;
			$out[ $k ] = maybe_unserialize( $vals[0] );
		}
		return $out;
	}

	public static function get_post( WP_REST_Request $r ) {
		$p = get_post( (int) $r['id'] );
		if ( ! $p ) return new WP_Error( 'pavva', 'Nu există.', array( 'status' => 404 ) );
		return array(
			'id' => $p->ID, 'type' => $p->post_type, 'status' => $p->post_status, 'slug' => $p->post_name,
			'title' => $p->post_title, 'content' => $p->post_content, 'excerpt' => $p->post_excerpt,
			'menu_order' => $p->menu_order, 'parent' => $p->post_parent,
			'lang' => self::on() ? pll_get_post_language( $p->ID ) : '',
			'translations' => self::on() ? pll_get_post_translations( $p->ID ) : array(),
			'link' => get_permalink( $p ), 'meta' => self::meta_of( $p->ID ),
		);
	}

	/*
	 * Creează (sau actualizează) traducerea în limba „lang”: copie a originalului cu tot cu design și câmpuri,
	 * apoi textele trimise (title, content, excerpt, slug, status, meta{…}).
	 */
	public static function translate_post( WP_REST_Request $r ) {
		if ( ! self::on() ) return new WP_Error( 'pavva', 'Polylang nu e activ.', array( 'status' => 400 ) );
		$src  = get_post( (int) $r['id'] );
		$lang = sanitize_key( (string) $r['lang'] );
		if ( ! $src || ! $lang || ! in_array( $lang, pll_languages_list( array( 'fields' => 'slug' ) ), true ) ) {
			return new WP_Error( 'pavva', 'Produs/pagină sau limbă greșită.', array( 'status' => 400 ) );
		}
		$p   = $r->get_json_params();
		$tr  = (int) pll_get_post( $src->ID, $lang );
		$new = ! $tr;
		$arr = array();
		foreach ( array( 'title' => 'post_title', 'content' => 'post_content', 'excerpt' => 'post_excerpt', 'slug' => 'post_name', 'status' => 'post_status' ) as $k => $f ) {
			if ( isset( $p[ $k ] ) ) $arr[ $f ] = (string) $p[ $k ];
		}
		if ( $new ) {
			$parent = $src->post_parent ? (int) pll_get_post( $src->post_parent, $lang ) : 0;
			$arr    = array_merge( array(
				'post_type' => $src->post_type, 'post_status' => $src->post_status, 'post_title' => $src->post_title,
				'post_content' => $src->post_content, 'post_excerpt' => $src->post_excerpt, 'menu_order' => $src->menu_order,
				'post_parent' => $parent, 'post_author' => $src->post_author, 'comment_status' => $src->comment_status,
			), $arr );
			$tr = wp_insert_post( wp_slash( $arr ), true );
			if ( is_wp_error( $tr ) ) return $tr;
			foreach ( self::meta_of( $src->ID ) as $k => $v ) update_post_meta( $tr, $k, wp_slash( $v ) );
			pll_set_post_language( $tr, $lang );
			$links          = pll_get_post_translations( $src->ID );
			$links[ $lang ] = $tr;
			$links[ pll_get_post_language( $src->ID ) ] = $src->ID;
			pll_save_post_translations( $links );
			/* categoriile: varianta lor în limba traducerii, unde există; restul (tip produs, vizibilitate) la fel */
			foreach ( get_object_taxonomies( $src->post_type ) as $tax ) {
				$ids = wp_get_object_terms( $src->ID, $tax, array( 'fields' => 'ids' ) );
				if ( is_wp_error( $ids ) || ! $ids ) continue;
				if ( pll_is_translated_taxonomy( $tax ) ) {
					$ids = array_values( array_filter( array_map( function ( $t ) use ( $lang ) { return (int) pll_get_term( $t, $lang ); }, $ids ) ) );
				}
				wp_set_object_terms( $tr, $ids, $tax );
			}
		} elseif ( $arr ) {
			$arr['ID'] = $tr;
			$res       = wp_update_post( wp_slash( $arr ), true );
			if ( is_wp_error( $res ) ) return $res;
		}
		foreach ( (array) ( $p['meta'] ?? array() ) as $k => $v ) {
			$k = (string) $k;
			if ( '' === $k ) continue;
			if ( null === $v ) { delete_post_meta( $tr, $k ); continue; }
			update_post_meta( $tr, $k, wp_slash( $v ) );
		}
		self::refresh( $tr );
		return array( 'id' => $tr, 'created' => $new, 'link' => get_permalink( $tr ), 'translations' => pll_get_post_translations( $tr ) );
	}

	/* CSS-ul Elementor și cache-urile paginii se refac la următoarea afișare */
	private static function refresh( $id ) {
		delete_post_meta( $id, '_elementor_css' );
		delete_post_meta( $id, '_elementor_element_cache' );
		if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			$css = \Elementor\Core\Files\CSS\Post::create( $id );
			if ( $css ) $css->delete();
		}
		clean_post_cache( $id );
		if ( 'product' === get_post_type( $id ) && function_exists( 'wc_delete_product_transients' ) ) wc_delete_product_transients( $id );
		if ( class_exists( 'Pavva_W_Quote_Form' ) ) Pavva_W_Quote_Form::flush();
		do_action( 'litespeed_purge_post', $id );
	}

	public static function get_term( WP_REST_Request $r ) {
		$t = get_term( (int) $r['id'] );
		if ( ! $t || is_wp_error( $t ) ) return new WP_Error( 'pavva', 'Nu există.', array( 'status' => 404 ) );
		$meta = array();
		foreach ( get_term_meta( $t->term_id ) as $k => $vals ) $meta[ $k ] = maybe_unserialize( $vals[0] );
		return array(
			'id' => $t->term_id, 'taxonomy' => $t->taxonomy, 'name' => $t->name, 'slug' => $t->slug, 'description' => $t->description,
			'parent' => $t->parent, 'lang' => self::on() ? pll_get_term_language( $t->term_id ) : '',
			'translations' => self::on() ? pll_get_term_translations( $t->term_id ) : array(), 'link' => get_term_link( $t ), 'meta' => $meta,
		);
	}

	public static function translate_term( WP_REST_Request $r ) {
		if ( ! self::on() ) return new WP_Error( 'pavva', 'Polylang nu e activ.', array( 'status' => 400 ) );
		$src  = get_term( (int) $r['id'] );
		$lang = sanitize_key( (string) $r['lang'] );
		if ( ! $src || is_wp_error( $src ) || ! $lang ) return new WP_Error( 'pavva', 'Categorie sau limbă greșită.', array( 'status' => 400 ) );
		$p   = $r->get_json_params();
		$tr  = (int) pll_get_term( $src->term_id, $lang );
		$new = ! $tr;
		$args = array();
		if ( isset( $p['slug'] ) ) $args['slug'] = sanitize_title( $p['slug'] );
		if ( isset( $p['description'] ) ) $args['description'] = (string) $p['description'];
		if ( $new ) {
			if ( $src->parent ) $args['parent'] = (int) pll_get_term( $src->parent, $lang );
			$res = wp_insert_term( (string) ( $p['name'] ?? $src->name ), $src->taxonomy, wp_slash( $args ) );
			if ( is_wp_error( $res ) ) return $res;
			$tr = (int) $res['term_id'];
			foreach ( get_term_meta( $src->term_id ) as $k => $vals ) update_term_meta( $tr, $k, wp_slash( maybe_unserialize( $vals[0] ) ) );
			pll_set_term_language( $tr, $lang );
			$links          = pll_get_term_translations( $src->term_id );
			$links[ $lang ] = $tr;
			$links[ pll_get_term_language( $src->term_id ) ] = $src->term_id;
			pll_save_term_translations( $links );
		} else {
			if ( isset( $p['name'] ) ) $args['name'] = (string) $p['name'];
			if ( $args ) {
				$res = wp_update_term( $tr, $src->taxonomy, wp_slash( $args ) );
				if ( is_wp_error( $res ) ) return $res;
			}
		}
		foreach ( (array) ( $p['meta'] ?? array() ) as $k => $v ) {
			if ( null === $v ) { delete_term_meta( $tr, (string) $k ); continue; }
			update_term_meta( $tr, (string) $k, wp_slash( $v ) );
		}
		clean_term_cache( $tr, $src->taxonomy );
		return array( 'id' => $tr, 'created' => $new, 'link' => get_term_link( $tr ), 'translations' => pll_get_term_translations( $tr ) );
	}

	/* dicționarul textelor fixe: { "en": { "Adaugă în listă": "Add to list", … } } (se îmbină cu ce există) */
	public static function save_strings( WP_REST_Request $r ) {
		$in   = $r->get_json_params();
		$dict = get_option( self::OPT, array() );
		if ( ! is_array( $dict ) ) $dict = array();
		foreach ( (array) $in as $lang => $pairs ) {
			$lang = sanitize_key( $lang );
			if ( ! $lang || ! is_array( $pairs ) ) continue;
			foreach ( $pairs as $src => $tr ) {
				if ( null === $tr ) { unset( $dict[ $lang ][ $src ] ); continue; }
				$dict[ $lang ][ (string) $src ] = wp_kses_post( (string) $tr );
			}
		}
		update_option( self::OPT, $dict, true );
		do_action( 'litespeed_purge_all' );
		return $dict;
	}

	/* ordinea paginilor / produselor: { "ids": [12, 5, 7] } → 1, 2, 3 (traducerile primesc aceeași ordine prin Polylang) */
	public static function save_order( WP_REST_Request $r ) {
		$ids = array_map( 'absint', (array) $r->get_param( 'ids' ) );
		global $wpdb;
		foreach ( $ids as $i => $id ) {
			if ( ! $id ) continue;
			$wpdb->update( $wpdb->posts, array( 'menu_order' => $i + 1 ), array( 'ID' => $id ) );
			if ( self::on() ) {
				foreach ( pll_get_post_translations( $id ) as $tid ) $wpdb->update( $wpdb->posts, array( 'menu_order' => $i + 1 ), array( 'ID' => (int) $tid ) );
			}
			clean_post_cache( $id );
		}
		if ( function_exists( 'wc_delete_product_transients' ) ) wc_delete_product_transients();
		if ( class_exists( 'Pavva_W_Quote_Form' ) ) Pavva_W_Quote_Form::flush();
		return array( 'ok' => count( $ids ) );
	}
}

/* textele fixe din pluginurile Pavva, în limba paginii */
function pavva_t( $text ) {
	return Pavva_W_I18n::t( $text );
}
