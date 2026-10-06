<?php
/**
 * Pavva Widgets – listele „Categorie produs” / „Produs solicitat” din formularul Elementor
 *
 * În formularul Elementor Pro, la un câmp Select cu ID-ul (Advanced → ID):
 *   categorie → primește automat categoriile de produse WooCommerce;
 *   produs    → primește automat produsele; în pagină arată doar produsele
 *               din categoria aleasă (se schimbă imediat, fără reîncărcare).
 * Prima opțiune scrisă în câmp (ex.: „Produs solicitat”) rămâne ca text de început.
 * Produsele / categoriile noi apar singure în formular.
 *
 * @package Pavva_Widgets
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_W_Quote_Form {

    /** ID-urile de câmp recunoscute (Elementor → câmp → Advanced → ID). */
    const CAT_IDS  = array( 'categorie', 'categorie_produs', 'category' );
    const PROD_IDS = array( 'produs', 'produs_solicitat', 'product' );

    /** @var bool Formularul cu liste Pavva e în pagina curentă. */
    private static $used = false;

    /** @var array|null */
    private static $data = null;

    public static function init() {
        add_filter( 'elementor_pro/forms/render/item', array( __CLASS__, 'fill_select' ), 10, 3 );
        add_action( 'wp_footer', array( __CLASS__, 'print_script' ), 98 );
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
            $prods[] = array( 'name' => self::txt( get_the_title( $pid ) ), 'cats' => array_values( array_unique( $names ) ) );
        }

        self::$data = array( 'cats' => $cats, 'prods' => $prods );
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
        self::$used = true;
        return $item;
    }

    public static function print_script() {
        if ( ! self::$used || is_admin() ) return;
        $d = self::data();
        $map = array();
        foreach ( $d['prods'] as $p ) $map[] = array( $p['name'], $p['cats'] );
        ?>
<script id="pavva-oferta-liste">
/* Pavva Widgets – „Produs solicitat” arată doar produsele din categoria aleasă */
(function () {
	'use strict';
	var PRODS = <?php echo wp_json_encode( $map ); ?>;
	var CAT = <?php echo wp_json_encode( array_map( function ( $i ) { return 'form_fields[' . $i . ']'; }, self::CAT_IDS ) ); ?>;
	var PROD = <?php echo wp_json_encode( array_map( function ( $i ) { return 'form_fields[' . $i . ']'; }, self::PROD_IDS ) ); ?>;

	function norm(t) {
		return String(t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/^(—\s*)+/, '')
			.toLowerCase().replace(/\s+/g, ' ').trim();
	}
	function find(form, names) {
		for (var i = 0; i < names.length; i++) {
			var el = form.querySelector('select[name="' + names[i] + '"]');
			if (el) return el;
		}
		return null;
	}

	function filter(form) {
		var cat = find(form, CAT), prod = find(form, PROD);
		if (!cat || !prod) return;
		var c = norm(cat.value);
		var keep = prod.value;
		var first = prod.options[0] && !prod.options[0].value ? prod.options[0].cloneNode(true) : null;
		prod.innerHTML = '';
		if (first) prod.appendChild(first);
		PRODS.forEach(function (p) {
			if (c && !p[1].some(function (n) { return norm(n) === c; })) return;
			var o = document.createElement('option');
			o.value = p[0];
			o.textContent = p[0];
			prod.appendChild(o);
		});
		/* produsul ales rămâne selectat dacă e în categoria nouă */
		prod.value = keep;
		if (prod.value !== keep) prod.value = '';
	}

	document.addEventListener('change', function (e) {
		var el = e.target;
		if (el && el.tagName === 'SELECT' && el.form && CAT.indexOf(el.name) !== -1) filter(el.form);
	}, true);

	function initAll() {
		Array.prototype.forEach.call(document.querySelectorAll('form'), function (f) {
			if (find(f, CAT) && find(f, PROD)) filter(f);
		});
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAll);
	else initAll();
	/* formulare încărcate mai târziu (ex.: în popup Elementor) */
	if (window.jQuery) window.jQuery(document).on('elementor/popup/show', function () { setTimeout(initAll, 50); });
})();
</script>
        <?php
    }
}
