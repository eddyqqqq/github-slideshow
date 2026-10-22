<?php
/**
 * Pavva Products – pagini WooCommerce care nu fac parte din site
 *
 * Site-ul e un catalog: produsele se văd pe categorii. Pagina „Magazin” (/shop/,
 * în orice limbă) și categoria implicită WooCommerce („Uncategorized”, goală,
 * care nu se poate șterge) duc la prima pagină, în limba vizitatorului.
 *
 * @package Pavva_Produse
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_P_Redirects {

    public function __construct() {
        add_action( 'template_redirect', array( $this, 'redirect' ), 5 );
    }

    public function redirect() {
        if ( is_admin() || wp_doing_ajax() ) return;
        $go = function_exists( 'is_shop' ) && is_shop();
        if ( ! $go && function_exists( 'is_product_category' ) && is_product_category() ) {
            $term = get_queried_object();
            $def  = (int) get_option( 'default_product_cat', 0 );
            /* doar categoria implicită și doar cât e goală (dacă primește produse, rămâne vizibilă) */
            $go = $term instanceof WP_Term && ( (int) $term->term_id === $def || 'uncategorized' === $term->slug ) && 0 === (int) $term->count;
        }
        if ( ! $go ) return;
        $home = function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' );
        wp_safe_redirect( $home, 301 );
        exit;
    }
}
