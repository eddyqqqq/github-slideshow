<?php
/**
 * Plugin Name:       Imagient Blocks
 * Plugin URI:        https://imagient.ro
 * Description:       Native Gutenberg blocks by Imagient: responsive layout (Section, Flex, Grid, Box), scroll animations for every block, and the Aries scenes (hero, company scene, figures, service cards, ribbons).
 * Version:           1.17.1
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Imagient
 * Author URI:        https://imagient.ro
 * License:           GPL-2.0-or-later
 * Text Domain:       imagient-blocks
 */

defined( 'ABSPATH' ) || exit;

define( 'IMAGIENT_BLOCKS_VERSION', '1.17.1' );
define( 'IMAGIENT_BLOCKS_DIR', plugin_dir_path( __FILE__ ) );
define( 'IMAGIENT_BLOCKS_URL', plugin_dir_url( __FILE__ ) );

require_once IMAGIENT_BLOCKS_DIR . 'inc/helpers.php';
require_once IMAGIENT_BLOCKS_DIR . 'inc/layout.php';
require_once IMAGIENT_BLOCKS_DIR . 'inc/aries.php';
require_once IMAGIENT_BLOCKS_DIR . 'inc/pages.php';
require_once IMAGIENT_BLOCKS_DIR . 'inc/animations.php';
require_once IMAGIENT_BLOCKS_DIR . 'inc/style.php';

/** One block category for everything Imagient, first in the inserter. */
add_filter( 'block_categories_all', function ( $categories ) {
	array_unshift( $categories, array( 'slug' => 'imagient', 'title' => 'Imagient' ) );
	return $categories;
} );

add_action( 'init', function () {
	$v   = IMAGIENT_BLOCKS_VERSION . '.' . filemtime( __FILE__ );   // a new URL on every upload (the host caches assets for a week)
	$url = IMAGIENT_BLOCKS_URL . 'assets/';

	// front end: the motion libraries and the Aries runtime, deferred so they never block the first paint
	wp_register_script( 'imagient-gsap', $url . 'vendor/gsap.min.js', array(), '3.12.5', array( 'strategy' => 'defer', 'in_footer' => false ) );
	wp_register_script( 'imagient-scrolltrigger', $url . 'vendor/ScrollTrigger.min.js', array( 'imagient-gsap' ), '3.12.5', array( 'strategy' => 'defer', 'in_footer' => false ) );
	wp_register_script( 'imagient-lenis', $url . 'vendor/lenis.min.js', array(), '1.1.13', array( 'strategy' => 'defer', 'in_footer' => false ) );
	wp_register_script( 'imagient-shapes', $url . 'shapes.js', array(), $v, array( 'strategy' => 'defer', 'in_footer' => false ) );
	wp_register_script( 'imagient-aries', $url . 'aries.js', array( 'imagient-gsap', 'imagient-scrolltrigger', 'imagient-lenis', 'imagient-shapes' ), $v, array( 'strategy' => 'defer', 'in_footer' => false ) );
	wp_register_style( 'imagient-aries', $url . 'aries.css', array(), $v );

	// editor: the icon controls shared by the blocks and the Style tab
	wp_register_script( 'imagient-layout-ui', $url . 'layout-ui.js', array( 'wp-element', 'wp-components' ), $v, true );

	// editor: one script for every block (plain JS on the WordPress globals, no build step)
	wp_register_script(
		'imagient-blocks-editor',
		$url . 'editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-hooks', 'wp-compose', 'wp-data', 'imagient-shapes', 'imagient-layout-ui' ),
		$v,
		true
	);
	wp_add_inline_script( 'imagient-blocks-editor', 'window.IMAGIENT = ' . wp_json_encode( array(
		'logos'    => imagient_logo_list(),
		'assets'   => $url,
		'palette'  => imagient_palette(),
		'spaces'   => imagient_space_scale(),
		'frames'   => imagient_frames(),
	) ) . ';', 'before' );
	wp_register_style( 'imagient-blocks-editor', $url . 'editor.css', array( 'imagient-aries' ), $v );

	// the Style tab on every block (Elementor-like): its own script, after the blocks
	wp_register_script( 'imagient-style-panel', $url . 'style-panel.js', array( 'imagient-blocks-editor' ), $v, true );

	imagient_register_layout_blocks();
	imagient_register_aries_blocks();
	imagient_register_page_blocks();

	// link styles for core blocks: the underline that draws in on hover, as on the site
	register_block_style( 'core/button', array( 'name' => 'aries-link', 'label' => 'Aries link' ) );
	register_block_style( 'core/list', array( 'name' => 'aries-links', 'label' => 'Aries links' ) );
} );

// the block styles and the runtime load on every front-end page; the animations need them site-wide
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'imagient-aries' );
	wp_enqueue_script( 'imagient-aries' );
} );

add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_script( 'imagient-blocks-editor' );
	wp_enqueue_script( 'imagient-style-panel' );
	wp_enqueue_style( 'imagient-blocks-editor' );
} );

// the editor canvas (an iframe) gets the block styles too, so blocks look like the site while editing
add_action( 'enqueue_block_assets', function () {
	if ( is_admin() ) {
		wp_enqueue_style( 'imagient-aries' );
		wp_enqueue_style( 'imagient-blocks-editor' );
	}
} );

/** Classes on <html> before anything paints: .js (script available) and .pre (hero not drawn yet). */
add_action( 'wp_head', function () {
	echo "<script>document.documentElement.classList.add('pre','js');setTimeout(function(){document.documentElement.classList.remove('pre')},4000);</script>\n";
}, 1 );
