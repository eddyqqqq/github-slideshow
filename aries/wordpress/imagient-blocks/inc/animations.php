<?php
/**
 * Scroll animations for every block. Any block (core or ours) gets an "Animation" setting in the editor; the choice
 * is written on its outermost element as data-anim when the page renders, so the saved content of core blocks is
 * never touched and switching an animation off leaves no trace.
 *
 *   fade-up   rises and fades in as it enters
 *   split     words rise one after the other (headings)
 *   count     numbers inside count up from 0 when it comes into view
 *   scale-in  grows slightly from 94 %
 */

defined( 'ABSPATH' ) || exit;

function imagient_animations() {
	return array( 'fade-up', 'split', 'count', 'scale-in' );
}

// the attribute exists on every block type, so it survives in the block comment and reaches render_block
add_filter( 'register_block_type_args', function ( $args ) {
	$args['attributes']           = isset( $args['attributes'] ) ? $args['attributes'] : array();
	$args['attributes']['igAnim'] = array( 'type' => 'string', 'default' => '' );
	return $args;
} );

add_filter( 'render_block', function ( $html, $block ) {
	$anim = $block['attrs']['igAnim'] ?? '';
	if ( ! $anim || ! in_array( $anim, imagient_animations(), true ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $html;
	}
	$p = new WP_HTML_Tag_Processor( $html );
	if ( $p->next_tag() ) {
		$p->set_attribute( 'data-anim', $anim );
		return $p->get_updated_html();
	}
	return $html;
}, 10, 2 );
