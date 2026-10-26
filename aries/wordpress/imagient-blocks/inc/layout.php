<?php
/**
 * Layout blocks — Section, Container-aware Flex, Grid, Grid item, Box. Webflow-style responsive controls: every
 * layout setting has a desktop, tablet and mobile value (tablet ≤ 1024 px, mobile ≤ 640 px). The values become
 * CSS custom properties on the element; aries.css turns them into flexbox / grid.
 *
 * All blocks are dynamic (rendered here), the editor saves only their inner blocks, so the markup can evolve
 * without "invalid block" warnings.
 */

defined( 'ABSPATH' ) || exit;

function imagient_responsive_attr( $default = array() ) {
	return array( 'type' => 'object', 'default' => array( 'd' => $default, 't' => new stdClass(), 'm' => new stdClass() ) );
}

function imagient_register_layout_blocks() {
	$common = array(
		'api_version'   => 3,
		'category'      => 'imagient',
		'editor_script' => 'imagient-blocks-editor',
		'supports'      => array( 'anchor' => true, 'className' => true, 'html' => false ),
	);

	register_block_type( 'imagient/section', array_merge( $common, array(
		'title'           => 'Section',
		'description'     => 'A full-width band of the page: background, text tone, inner width and vertical spacing per breakpoint.',
		'supports'        => array( 'anchor' => true, 'className' => true, 'html' => false, 'align' => array( 'full' ) ),
		'attributes'      => array(
			'align'      => array( 'type' => 'string', 'default' => 'full' ),
			'background' => array( 'type' => 'string', 'default' => '' ),
			'tone'       => array( 'type' => 'string', 'default' => 'light' ),
			'container'  => array( 'type' => 'string', 'default' => 'gutter' ),
			'minHeight'  => array( 'type' => 'string', 'default' => '' ),
			'spacing'    => imagient_responsive_attr( array( 'pt' => '7', 'pb' => '7' ) ),
			'radius'     => array( 'type' => 'boolean', 'default' => false ),
		),
		'render_callback' => 'imagient_render_section',
	) ) );

	register_block_type( 'imagient/flex', array_merge( $common, array(
		'title'           => 'Flex',
		'description'     => 'Lays its blocks out in a row or a column, with alignment, gap and wrapping per breakpoint.',
		'attributes'      => array(
			'layout' => imagient_responsive_attr( array( 'dir' => 'row', 'justify' => 'flex-start', 'align' => 'stretch', 'gap' => '4', 'wrap' => 'nowrap', 'child' => 'equal' ) ),
		),
		'render_callback' => 'imagient_render_flex',
	) ) );

	register_block_type( 'imagient/grid', array_merge( $common, array(
		'title'           => 'Grid',
		'description'     => 'A CSS grid: columns, gaps and alignment per breakpoint. Put Grid items inside to span columns or rows.',
		'attributes'      => array(
			'layout' => imagient_responsive_attr( array( 'cols' => '3', 'gap' => '4', 'rowGap' => '', 'align' => 'stretch' ) ),
		),
		'render_callback' => 'imagient_render_grid',
	) ) );

	register_block_type( 'imagient/grid-item', array_merge( $common, array(
		'title'           => 'Grid item',
		'description'     => 'A cell of a Grid that can span several columns or rows, per breakpoint.',
		'parent'          => array( 'imagient/grid' ),
		'attributes'      => array(
			'layout' => imagient_responsive_attr( array( 'span' => '1', 'rows' => '1', 'align' => '' ) ),
		),
		'render_callback' => 'imagient_render_grid_item',
	) ) );

	register_block_type( 'imagient/box', array_merge( $common, array(
		'title'           => 'Box',
		'description'     => 'A container with its own background, padding, corner radius and height; the building block of cards and panels.',
		'attributes'      => array(
			'background' => array( 'type' => 'string', 'default' => '' ),
			'tone'       => array( 'type' => 'string', 'default' => 'light' ),
			'radius'     => array( 'type' => 'string', 'default' => '0' ),
			'minHeight'  => array( 'type' => 'string', 'default' => '' ),
			'link'       => array( 'type' => 'string', 'default' => '' ),
			'layout'     => imagient_responsive_attr( array( 'pad' => '5', 'dir' => 'column', 'justify' => 'flex-start', 'align' => 'stretch', 'gap' => '3' ) ),
		),
		'render_callback' => 'imagient_render_box',
	) ) );
}

function imagient_render_section( $a, $content ) {
	$sp    = $a['spacing'];
	$style = imagient_vars( 'ig-pt', imagient_resolve( $sp, 'pt', '7' ), 'imagient_space_var' )
		. imagient_vars( 'ig-pb', imagient_resolve( $sp, 'pb', '7' ), 'imagient_space_var' );
	if ( $bg = imagient_color( $a['background'] ) ) {
		$style .= 'background:' . $bg . ';';
	}
	$class = 'ig-section tone-' . sanitize_html_class( $a['tone'] );
	if ( 'screen' === $a['minHeight'] ) {
		$class .= ' is-screen';
	}
	if ( ! empty( $a['radius'] ) ) {
		$class .= ' is-rounded';
	}
	$tone = 'dark' === $a['tone'] ? array( 'data-tone' => 'dark' ) : array();
	return sprintf(
		'<section %s><div class="ig-container is-%s">%s</div></section>',
		imagient_wrapper( null, $class, $style, $tone ),
		sanitize_html_class( $a['container'] ),
		$content
	);
}

function imagient_render_flex( $a, $content ) {
	$l     = $a['layout'];
	$style = imagient_vars( 'ig-dir', imagient_resolve( $l, 'dir', 'row' ) )
		. imagient_vars( 'ig-justify', imagient_resolve( $l, 'justify', 'flex-start' ) )
		. imagient_vars( 'ig-align', imagient_resolve( $l, 'align', 'stretch' ) )
		. imagient_vars( 'ig-gap', imagient_resolve( $l, 'gap', '4' ), 'imagient_space_var' )
		. imagient_vars( 'ig-wrap', imagient_resolve( $l, 'wrap', 'nowrap' ), function ( $v ) { return in_array( (string) $v, array( '1', 'wrap', 'wrap-reverse' ), true ) || true === $v ? 'wrap' : 'nowrap'; } )
		. imagient_flex_child_vars( $l );
	return sprintf( '<div %s>%s</div>', imagient_wrapper( null, 'ig-flex', imagient_safe_css( $style ) ), $content );
}

function imagient_render_grid( $a, $content ) {
	$l      = $a['layout'];
	$gap    = imagient_resolve( $l, 'gap', '4' );
	$rowgap = imagient_resolve( $l, 'rowGap', '' );
	foreach ( array( 'd', 't', 'm' ) as $bp ) {
		if ( '' === $rowgap[ $bp ] ) {
			$rowgap[ $bp ] = $gap[ $bp ];
		}
	}
	$style = imagient_vars( 'ig-cols', imagient_resolve( $l, 'cols', '3' ), 'intval' )
		. imagient_vars( 'ig-gap', $gap, 'imagient_space_var' )
		. imagient_vars( 'ig-rgap', $rowgap, 'imagient_space_var' )
		. imagient_vars( 'ig-align', imagient_resolve( $l, 'align', 'stretch' ), 'imagient_css_align' );
	return sprintf( '<div %s>%s</div>', imagient_wrapper( null, 'ig-grid', imagient_safe_css( $style ) ), $content );
}

function imagient_render_grid_item( $a, $content ) {
	$l     = $a['layout'];
	$style = imagient_vars( 'ig-span', imagient_resolve( $l, 'span', '1' ), 'intval' )
		. imagient_vars( 'ig-rows', imagient_resolve( $l, 'rows', '1' ), 'intval' )
		. imagient_vars( 'ig-self', imagient_resolve( $l, 'align', 'auto' ), 'imagient_css_align' );
	return sprintf( '<div %s>%s</div>', imagient_wrapper( null, 'ig-grid-item', imagient_safe_css( $style ) ), $content );
}

function imagient_render_box( $a, $content ) {
	$l     = $a['layout'];
	$style = imagient_vars( 'ig-pad', imagient_resolve( $l, 'pad', '5' ), 'imagient_space_var' )
		. imagient_vars( 'ig-dir', imagient_resolve( $l, 'dir', 'column' ), 'imagient_css_dir' )
		. imagient_vars( 'ig-justify', imagient_resolve( $l, 'justify', 'flex-start' ), 'imagient_css_justify' )
		. imagient_vars( 'ig-align', imagient_resolve( $l, 'align', 'stretch' ), 'imagient_css_align' )
		. imagient_vars( 'ig-gap', imagient_resolve( $l, 'gap', '3' ), 'imagient_space_var' );
	if ( $bg = imagient_color( $a['background'] ) ) {
		$style .= 'background:' . $bg . ';';
	}
	if ( '' !== $a['minHeight'] ) {
		$style .= 'min-height:' . max( 0, intval( $a['minHeight'] ) ) . 'px;';
	}
	$style .= '--ig-radius:' . max( 0, intval( $a['radius'] ) ) . 'px;';
	$class  = 'ig-box tone-' . sanitize_html_class( $a['tone'] );
	$tone   = 'dark' === $a['tone'] ? array( 'data-tone' => 'dark' ) : array();
	$html   = sprintf( '<div %s>%s</div>', imagient_wrapper( null, $class, imagient_safe_css( $style ), $tone ), $content );
	if ( $a['link'] ) {
		// the whole box is the link: a stretched anchor keeps the inner markup valid (no links inside links)
		$html = preg_replace( '/<\/div>$/', '<a class="ig-box-link" href="' . esc_url( $a['link'] ) . '" aria-hidden="true" tabindex="-1"></a></div>', $html );
	}
	return $html;
}

/** Child sizing per breakpoint; in a column the children keep their natural height whatever the setting. */
function imagient_flex_child_vars( $l ) {
	$dir   = imagient_resolve( $l, 'dir', 'row' );
	$child = imagient_resolve( $l, 'child', 'equal' );
	$map   = array( 'equal' => '1 1 0', 'auto' => '0 1 auto', 'fill' => '1 1 auto', 'none' => '0 0 auto' );
	$out   = '';
	foreach ( array( 'd', 't', 'm' ) as $bp ) {
		$v    = 0 === strpos( (string) $dir[ $bp ], 'column' ) ? '0 0 auto' : ( $map[ $child[ $bp ] ] ?? '1 1 0' );
		$out .= '--ig-child-' . $bp . ':' . $v . ';';
	}
	return $out;
}

/** Layout keywords are taken from fixed lists, so an attribute can never carry other CSS. */
function imagient_pick( $v, $allowed, $default ) {
	return in_array( $v, $allowed, true ) ? $v : $default;
}
function imagient_css_dir( $v ) {
	return imagient_pick( $v, array( 'row', 'column', 'row-reverse', 'column-reverse' ), 'row' );
}
function imagient_css_justify( $v ) {
	return imagient_pick( $v, array( 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly' ), 'flex-start' );
}
function imagient_css_align( $v ) {
	return imagient_pick( $v, array( 'stretch', 'flex-start', 'center', 'flex-end', 'baseline', 'start', 'end', 'auto' ), 'auto' );
}

/** Only declarations we generate ourselves: property names from our list, values without markup characters. */
function imagient_safe_css( $css ) {
	return preg_replace( '/[<>"]/', '', $css );
}
