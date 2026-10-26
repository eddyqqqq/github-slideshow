<?php
/**
 * Aries — block theme by Imagient (https://imagient.ro).
 *
 * The design system lives in theme.json; the sections and their motion come from the Imagient Blocks plugin.
 * This file only loads the stylesheet, preloads the type and adds the Imagient credits in the admin.
 */

defined( 'ABSPATH' ) || exit;

define( 'ARIES_VERSION', '1.0.0' );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
	add_theme_support( 'responsive-embeds' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'aries', get_stylesheet_uri(), array(), ARIES_VERSION );
} );

// the two Area weights are needed on every page: start them with the HTML instead of after the CSS
add_action( 'wp_head', function () {
	foreach ( array( 'area-regular', 'area-bold' ) as $f ) {
		$file = get_theme_file_path( 'assets/fonts/' . $f . '.woff2' );
		if ( file_exists( $file ) ) {
			printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( get_theme_file_uri( 'assets/fonts/' . $f . '.woff2' ) ) );
		}
	}
}, 2 );

// the plugin carries the blocks; say so in the admin if it is missing
add_action( 'admin_notices', function () {
	if ( ! function_exists( 'imagient_register_layout_blocks' ) && current_user_can( 'activate_plugins' ) ) {
		echo '<div class="notice notice-warning"><p><strong>Aries:</strong> activate the <em>Imagient Blocks</em> plugin to use the Aries sections and layout blocks.</p></div>';
	}
} );

require_once get_theme_file_path( 'inc/imagient.php' );

// one pattern category for the Aries sections, first in the inserter's Patterns tab
add_action( 'init', function () {
	register_block_pattern_category( 'aries', array( 'label' => 'Aries (Imagient)', 'description' => 'The homepage sections and starting points for new ones.' ) );
} );

/**
 * On activation, when the site still shows its latest posts on the front page, create the Home page from the
 * homepage pattern and make it the front page. A site that already has a front page is left as it is.
 */
add_action( 'after_switch_theme', function () {
	if ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) ) {
		return;
	}
	$pattern = get_theme_file_path( 'patterns/home.php' );
	ob_start();
	include $pattern;
	$content = trim( ob_get_clean() );
	$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Home', 'post_name' => 'home', 'post_content' => $content, 'page_template' => 'page-blank' ) );
	if ( $id && ! is_wp_error( $id ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $id );
	}
} );
