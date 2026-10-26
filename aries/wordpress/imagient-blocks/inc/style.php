<?php
/**
 * Imagient Style — an Elementor-like Style tab on every block (core and Imagient).
 *
 * Each block can carry `igStyle`: { d: {...}, t: {...}, m: {...}, h: {...}, css: "..." }
 *   d = desktop (all screens), t = tablet (≤ 1024 px), m = mobile (≤ 640 px), h = hover, css = custom CSS
 * with free values ("24px", "2rem", "clamp(…)", "#145fad" …). When the page renders, the block gets a class made
 * from a hash of its style (igs-xxxxxxxx) and the CSS is added to the page head once per distinct style.
 */

defined( 'ABSPATH' ) || exit;

/** Style keys → CSS properties. Box values (margin, padding, inset, border width, radius) are objects. */
function imagient_style_props() {
	return array(
		// layout
		'display' => 'display', 'flexDirection' => 'flex-direction', 'justifyContent' => 'justify-content', 'alignItems' => 'align-items',
		'alignContent' => 'align-content', 'alignSelf' => 'align-self', 'flexWrap' => 'flex-wrap', 'gap' => 'gap', 'rowGap' => 'row-gap',
		'columnGap' => 'column-gap', 'gridTemplateColumns' => 'grid-template-columns', 'gridTemplateRows' => 'grid-template-rows',
		'gridColumn' => 'grid-column', 'gridRow' => 'grid-row', 'order' => 'order', 'flex' => 'flex',
		// size
		'width' => 'width', 'height' => 'height', 'minWidth' => 'min-width', 'maxWidth' => 'max-width', 'minHeight' => 'min-height',
		'maxHeight' => 'max-height', 'overflow' => 'overflow', 'aspectRatio' => 'aspect-ratio', 'objectFit' => 'object-fit', 'objectPosition' => 'object-position',
		// position
		'position' => 'position', 'zIndex' => 'z-index',
		// typography
		'fontFamily' => 'font-family', 'fontSize' => 'font-size', 'fontWeight' => 'font-weight', 'lineHeight' => 'line-height',
		'letterSpacing' => 'letter-spacing', 'textTransform' => 'text-transform', 'textDecoration' => 'text-decoration', 'fontStyle' => 'font-style',
		'textAlign' => 'text-align', 'color' => 'color', 'whiteSpace' => 'white-space',
		// background
		'backgroundColor' => 'background-color', 'backgroundSize' => 'background-size', 'backgroundPosition' => 'background-position',
		'backgroundRepeat' => 'background-repeat', 'backgroundAttachment' => 'background-attachment',
		// border
		'borderStyle' => 'border-style', 'borderColor' => 'border-color',
		// effects
		'opacity' => 'opacity', 'boxShadow' => 'box-shadow', 'transform' => 'transform', 'transition' => 'transition', 'filter' => 'filter',
		'backdropFilter' => 'backdrop-filter', 'cursor' => 'cursor', 'mixBlendMode' => 'mix-blend-mode',
	);
}

/** A free CSS value is accepted when it cannot leave its declaration. */
function imagient_style_value( $v ) {
	if ( is_array( $v ) || is_object( $v ) ) {
		return '';
	}
	$v = trim( (string) $v );
	if ( '' === $v || strlen( $v ) > 300 || preg_match( '/[{}<>;\\\\]|\/\*|expression\s*\(|javascript:|@import/i', $v ) ) {
		return '';
	}
	// palette slugs become their preset variable
	foreach ( imagient_palette() as $c ) {
		if ( $v === $c['slug'] ) {
			return 'var(--wp--preset--color--' . $c['slug'] . ', ' . $c['color'] . ')';
		}
	}
	return $v;
}

/** Declarations for one breakpoint/state. */
function imagient_style_decls( $set ) {
	if ( ! is_array( $set ) ) {
		return '';
	}
	$out   = '';
	$props = imagient_style_props();
	foreach ( $set as $k => $v ) {
		if ( isset( $props[ $k ] ) ) {
			$v = imagient_style_value( $v );
			if ( '' !== $v ) {
				$out .= $props[ $k ] . ':' . $v . ';';
			}
		}
	}
	$sides = array( 'top', 'right', 'bottom', 'left' );
	foreach ( array( 'margin' => 'margin-%s', 'padding' => 'padding-%s', 'inset' => '%s', 'borderWidth' => 'border-%s-width' ) as $k => $pat ) {
		if ( isset( $set[ $k ] ) && is_array( $set[ $k ] ) ) {
			foreach ( $sides as $s ) {
				$v = imagient_style_value( $set[ $k ][ $s ] ?? '' );
				if ( '' !== $v ) {
					$out .= sprintf( $pat, $s ) . ':' . $v . ';';
				}
			}
		}
	}
	if ( isset( $set['borderRadius'] ) && is_array( $set['borderRadius'] ) ) {
		$corners = array( 'top' => 'top-left', 'right' => 'top-right', 'bottom' => 'bottom-right', 'left' => 'bottom-left' );
		foreach ( $corners as $s => $c ) {
			$v = imagient_style_value( $set['borderRadius'][ $s ] ?? '' );
			if ( '' !== $v ) {
				$out .= 'border-' . $c . '-radius:' . $v . ';';
			}
		}
	}
	// background image and gradient share background-image (image on top)
	$layers = array();
	if ( ! empty( $set['backgroundImage'] ) && is_string( $set['backgroundImage'] ) ) {
		$url = esc_url_raw( $set['backgroundImage'] );
		if ( $url ) {
			$layers[] = 'url("' . str_replace( '"', '%22', $url ) . '")';
		}
	}
	$g = imagient_style_value( $set['backgroundGradient'] ?? '' );
	if ( $g && preg_match( '/gradient\(/i', $g ) ) {
		$layers[] = $g;
	}
	if ( $layers ) {
		$out .= 'background-image:' . implode( ',', $layers ) . ';';
	}
	return $out;
}

/** The whole stylesheet for one block, scoped to its class. */
function imagient_style_css( $style, $cls ) {
	$sel = '.' . $cls . '.' . $cls;   // two classes: wins over the blocks' own one-class rules
	$css = '';
	if ( $d = imagient_style_decls( $style['d'] ?? array() ) ) {
		$css .= $sel . '{' . $d . '}';
	}
	if ( $h = imagient_style_decls( $style['h'] ?? array() ) ) {
		$css .= $sel . ':hover{' . $h . '}';
	}
	if ( $t = imagient_style_decls( $style['t'] ?? array() ) ) {
		$css .= '@media (max-width:1024px){' . $sel . '{' . $t . '}}';
	}
	if ( $m = imagient_style_decls( $style['m'] ?? array() ) ) {
		$css .= '@media (max-width:640px){' . $sel . '{' . $m . '}}';
	}
	// custom CSS: "selector" stands for this block, as in Elementor
	if ( ! empty( $style['css'] ) && is_string( $style['css'] ) ) {
		$custom = str_replace( array( '<', '\\' ), '', $style['css'] );
		$custom = preg_replace( '/@import[^;]*;?/i', '', $custom );
		$css   .= str_replace( 'selector', $sel, $custom );
	}
	return $css;
}

add_filter( 'register_block_type_args', function ( $args ) {
	$args['attributes']            = isset( $args['attributes'] ) ? $args['attributes'] : array();
	$args['attributes']['igStyle'] = array( 'type' => 'object' );
	return $args;
} );

/** CSS collected while the page renders; printed in the head (block themes render the template before it). */
function imagient_style_store( $css = null ) {
	static $all = array();
	if ( null !== $css ) {
		$all[ md5( $css ) ] = $css;
	}
	return $all;
}

add_filter( 'render_block', function ( $html, $block ) {
	$style = $block['attrs']['igStyle'] ?? null;
	if ( empty( $style ) || ! is_array( $style ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $html;
	}
	$cls = 'igs-' . substr( md5( wp_json_encode( $style ) ), 0, 8 );
	$css = imagient_style_css( $style, $cls );
	if ( ! $css ) {
		return $html;
	}
	$p = new WP_HTML_Tag_Processor( $html );
	if ( ! $p->next_tag() ) {
		return $html;
	}
	$p->add_class( $cls );
	$html = $p->get_updated_html();
	if ( did_action( 'wp_head' ) ) {
		// rendered after the head (classic templates, late renders): the rules go right before the block
		static $printed = array();
		if ( isset( $printed[ $cls ] ) ) {
			return $html;
		}
		$printed[ $cls ] = true;
		return '<style id="' . esc_attr( $cls ) . '">' . $css . '</style>' . $html;
	}
	imagient_style_store( $css );
	return $html;
}, 11, 2 );

add_action( 'wp_head', function () {
	$all = imagient_style_store();
	if ( $all ) {
		echo '<style id="imagient-style">' . implode( '', $all ) . "</style>\n"; // phpcs:ignore -- sanitised in imagient_style_css
	}
}, 99 );
