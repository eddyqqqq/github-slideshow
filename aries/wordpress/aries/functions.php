<?php
/**
 * Aries — block theme by Imagient (https://imagient.ro).
 *
 * The design system lives in theme.json; the sections and their motion come from the Imagient Blocks plugin.
 * This file only loads the stylesheet, preloads the type, leaves out WordPress' emoji script and adds the Imagient
 * credits in the admin.
 */

defined( 'ABSPATH' ) || exit;

// the theme's version plus the stylesheet's upload time: every upload gets a new URL, so browsers and the host's
// week-long cache never keep an old stylesheet
define( 'ARIES_VERSION', wp_get_theme( 'aries' )->get( 'Version' ) . '.' . filemtime( __DIR__ . '/style.css' ) );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'wp-block-styles' );
	// pages get an excerpt (the editor's "Excerpt" panel): Yoast SEO's llms.txt describes each page it lists with it
	add_post_type_support( 'page', 'excerpt' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
	add_theme_support( 'responsive-embeds' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'aries', get_stylesheet_uri(), array(), ARIES_VERSION );
} );

// the two Area weights on every page (Regular for texts, SemiBold for headings): started with the HTML, not after the CSS
add_action( 'wp_head', function () {
	foreach ( array( 'area-regular', 'area-semibold' ) as $f ) {
		if ( file_exists( get_theme_file_path( 'assets/fonts/' . $f . '.woff2' ) ) ) {
			printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( get_theme_file_uri( 'assets/fonts/' . $f . '.woff2' ) ) );
		}
	}
}, 2 );

// Contact Form 7: the form's template as written (its own layout, no paragraphs or line breaks added)
add_filter( 'wpcf7_autop_or_not', '__return_false' );

// Contact Form 7's scripts and styles only on the pages that hold a form (the contact page): every other page saved
// its files; a form placed on another page later brings them with it
function aries_has_cf7() {
	if ( ! is_singular() ) {
		return false;
	}
	$post = get_post();
	return $post && ( has_shortcode( $post->post_content, 'contact-form-7' ) || has_block( 'contact-form-7/contact-form-selector', $post ) );
}
add_filter( 'wpcf7_load_js', 'aries_has_cf7' );
add_filter( 'wpcf7_load_css', 'aries_has_cf7' );

// no emoji script or its styles: the site has no emoji, and every page saved a script and an inline style for them
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );
} );

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
