<?php
/**
 * Pavva Widgets – listele din formularele site-ului (categorie, produs, județ, localitate, suprafață)
 *
 * Scriptul assets/oferta.js lucrează în orice formular de pe site. Aici sunt
 * datele pentru el (categorii / produse) și completarea din server pentru Elementor.
 *
 * În formularul Elementor Pro, la un câmp Select cu ID-ul (Advanced → ID):
 *   categorie → primește automat categoriile de produse WooCommerce;
 *   produs    → primește automat produsele; în pagină arată doar produsele
 *               din categoria aleasă (se schimbă imediat, fără reîncărcare).
 * Prima opțiune scrisă în câmp (ex.: „Produs solicitat”) rămâne ca text de început.
 * Produsele / categoriile noi apar singure în formular.
 *
 * @package Pavva_Widgets
 * @author  Imagient <https://imagient.ro/>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_W_Quote_Form {

    /** ID-urile de câmp recunoscute (Elementor → câmp → Advanced → ID). */
    const CAT_IDS  = array( 'categorie', 'categorie_produs', 'category' );
    const PROD_IDS = array( 'produs', 'produs_solicitat', 'product' );

    /** @var array|null */
    private static $data = null;

    const CACHE_KEY = 'pavva_w_oferta_data_v3';

    public static function init() {
        /* Elementor Pro: listele cu ID categorie / produs vin deja completate din server */
        add_filter( 'elementor_pro/forms/render/item', array( __CLASS__, 'fill_select' ), 10, 3 );
        /* datele pentru script (funcționează și cu pagini din cache) */
        add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
        /* lista se recalculează când se schimbă produsele / categoriile */
        foreach ( array( 'save_post_product', 'deleted_post', 'created_product_cat', 'edited_product_cat', 'delete_product_cat' ) as $hook ) {
            add_action( $hook, array( __CLASS__, 'flush' ) );
        }
    }

    public static function flush() {
        delete_transient( self::CACHE_KEY );
    }

    /* Numele parametrilor din adresă (aceleași ca în setările linkului „Solicită ofertă”). */
    public static function params(): array {
        return (array) apply_filters( 'pavva_w_prefill_params', array( 'produs' => 'produs', 'categorie' => 'categorie' ) );
    }

    public static function register_route() {
        register_rest_route(
            'pavva/v1',
            '/oferta',
            array(
                'methods'             => 'GET',
                'permission_callback' => '__return_true', /* doar nume publice de categorii / produse */
                'callback'            => function () {
                    return rest_ensure_response( self::data() );
                },
            )
        );
    }

    public static function enqueue() {
        if ( is_admin() ) return;
        $p = self::params();
        wp_enqueue_script( 'pavva-oferta', PAVVA_WIDGETS_URL . 'assets/oferta.js', array(), pavva_widgets_asset_version( 'assets/oferta.js' ), true );
        wp_localize_script(
            'pavva-oferta',
            'PavvaOferta',
            array(
                'rest'   => esc_url_raw( rest_url( 'pavva/v1/oferta' ) ),
                'places' => PAVVA_WIDGETS_URL . 'assets/data/localitati.json?v=' . pavva_widgets_asset_version( 'assets/data/localitati.json' ),
                'params' => array( 'produs' => (string) $p['produs'], 'categorie' => (string) $p['categorie'] ),
            )
        );
    }

    private static function role( $item ) {
        $id = strtolower( trim( (string) ( isset( $item['custom_id'] ) ? $item['custom_id'] : '' ) ) );
        if ( in_array( $id, self::CAT_IDS, true ) ) return 'categorie';
        if ( in_array( $id, self::PROD_IDS, true ) ) return 'produs';
        return '';
    }

    /* textul fără caracterul „|” (separă eticheta de valoare în Elementor) */
    private static function txt( $s ) {
        return trim( str_replace( array( '|', "\n", "\r" ), array( '/', ' ', ' ' ), wp_strip_all_tags( html_entity_decode( (string) $s, ENT_QUOTES, 'UTF-8' ) ) ) );
    }

    /* Categoriile (ierarhic, în ordinea din WooCommerce) și produsele cu categoriile lor. */
    public static function data(): array {
        if ( null !== self::$data ) return self::$data;
        $cached = get_transient( self::CACHE_KEY );
        if ( is_array( $cached ) && isset( $cached['cats'], $cached['prods'] ) ) {
            self::$data = $cached;
            return self::$data;
        }

        $cats  = array();
        $terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
        if ( ! is_wp_error( $terms ) ) {
            $terms = array_values( array_filter( $terms, function ( $t ) { return 'uncategorized' !== $t->slug; } ) );
            usort( $terms, function ( $a, $b ) {
                $oa = (int) get_term_meta( $a->term_id, 'order', true );
                $ob = (int) get_term_meta( $b->term_id, 'order', true );
                return $oa === $ob ? strcasecmp( $a->name, $b->name ) : $oa - $ob;
            } );
            $by_parent = array();
            foreach ( $terms as $t ) $by_parent[ (int) $t->parent ][] = $t;
            $ids  = wp_list_pluck( $terms, 'term_id' );
            $walk = function ( $parent, $depth ) use ( &$walk, &$cats, $by_parent ) {
                if ( empty( $by_parent[ $parent ] ) ) return;
                foreach ( $by_parent[ $parent ] as $t ) {
                    $cats[] = array( 'name' => self::txt( $t->name ), 'depth' => $depth );
                    $walk( (int) $t->term_id, $depth + 1 );
                }
            };
            /* rădăcini: fără părinte sau cu părintele ascuns (gol) */
            foreach ( $by_parent as $parent => $list ) {
                if ( 0 === $parent || ! in_array( $parent, $ids, true ) ) $walk( $parent, 0 );
            }
        }

        $prods = array();
        $args  = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
            'fields'         => 'ids',
            'no_found_rows'  => true,
        );
        $hidden = get_term_by( 'name', 'exclude-from-catalog', 'product_visibility' );
        if ( $hidden instanceof WP_Term ) {
            $args['tax_query'] = array( array( 'taxonomy' => 'product_visibility', 'field' => 'term_taxonomy_id', 'terms' => array( (int) $hidden->term_taxonomy_id ), 'operator' => 'NOT IN' ) ); // phpcs:ignore
        }
        foreach ( get_posts( $args ) as $pid ) {
            $names = array();
            $pterms = get_the_terms( $pid, 'product_cat' );
            if ( is_array( $pterms ) ) {
                foreach ( $pterms as $t ) {
                    $names[] = self::txt( $t->name );
                    /* și categoriile părinte: alegi „Pavele” și vezi și produsele din subcategorii */
                    foreach ( get_ancestors( $t->term_id, 'product_cat', 'taxonomy' ) as $aid ) {
                        $a = get_term( $aid, 'product_cat' );
                        if ( $a instanceof WP_Term ) $names[] = self::txt( $a->name );
                    }
                }
            }
            /* id, categoria principală, adresa și poza: pentru lista de ofertă (oferta-lista.js) */
            $item    = class_exists( 'Pavva_W_Lista' ) ? Pavva_W_Lista::item( (int) $pid ) : array();
            $prods[] = array_merge(
                array( 'name' => self::txt( get_the_title( $pid ) ), 'cats' => array_values( array_unique( $names ) ) ),
                $item ? array( 'id' => $item['id'], 'cat' => $item['cat'], 'url' => $item['url'], 'img' => $item['img'] ) : array(),
                /* culori + grosimi (alegerea variantei în formular) */
                class_exists( 'Pavva_W_Lista' ) && ( $v = Pavva_W_Lista::variants( (int) $pid ) ) && ( $v['colors'] || $v['thick'] ) ? array( 'opts' => $v ) : array()
            );
        }

        self::$data = array( 'cats' => $cats, 'prods' => $prods );
        set_transient( self::CACHE_KEY, self::$data, 12 * HOUR_IN_SECONDS );
        return self::$data;
    }

    /* Elementor Pro: completează opțiunile listelor marcate cu ID-ul categorie / produs. */
    public static function fill_select( $item, $index = 0, $form = null ) {
        if ( ! class_exists( 'WooCommerce' ) || ! is_array( $item ) ) return $item;
        if ( ! isset( $item['field_type'] ) || 'select' !== $item['field_type'] ) return $item;
        $role = self::role( $item );
        if ( ! $role ) return $item;

        /* prima opțiune scrisă de tine rămâne text de început (fără valoare) */
        $existing = isset( $item['field_options'] ) ? preg_split( '/\R/', (string) $item['field_options'] ) : array();
        $first    = $existing ? trim( (string) $existing[0] ) : '';
        if ( false !== strpos( $first, '|' ) ) $first = trim( strtok( $first, '|' ) );
        if ( '' === $first ) {
            $first = ! empty( $item['field_label'] ) ? self::txt( $item['field_label'] ) : ( 'categorie' === $role ? 'Categorie produs' : 'Produs solicitat' );
        }

        $d     = self::data();
        $lines = array( self::txt( $first ) . '|' );
        if ( 'categorie' === $role ) {
            foreach ( $d['cats'] as $c ) {
                $lines[] = str_repeat( '— ', (int) $c['depth'] ) . $c['name'] . '|' . $c['name'];
            }
        } else {
            foreach ( $d['prods'] as $p ) {
                $lines[] = $p['name'] . '|' . $p['name'];
            }
        }
        $item['field_options'] = implode( "\n", $lines );
        return $item;
    }
}
