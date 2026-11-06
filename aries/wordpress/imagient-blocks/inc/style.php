<?php
/**
 * Style — an Elementor-like Style tab on every block (core and Aries).
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

/**
 * The parts inside a block that the Style tab can style on their own, as Elementor's widgets list theirs: block name →
 * part → [label, selector inside the block]. The editor shows them in the tab's "Part" list.
 */
function imagient_style_elements() {
	return apply_filters( 'imagient_style_elements', array(
		'imagient/page-hero'   => array( 'title' => array( 'Title', '.ig-phero__title, .ig-phero-edit__title' ), 'scene' => array( 'Scene (its texts)', '.ig-phero__scene, .ig-phero-edit__scene' ) ),
		'imagient/hero'        => array( 'title' => array( 'Title', '.hero-title h1, .ig-hero-edit__title h1' ), 'subtitle' => array( 'Subtitle', '.hero-title p, .ig-hero-edit__title p' ), 'sceneTitle' => array( 'Second screen: title', '.scene-two h2, .ig-hero-edit__scene h2' ), 'sceneText' => array( 'Second screen: text', '.scene-two p, .ig-hero-edit__scene p' ) ),
		'imagient/identity'    => array( 'introTitle' => array( 'Intro title', '.g-intro h2, .ig-scene-edit__state:first-child h2' ), 'introText' => array( 'Intro text', '.g-intro p, .ig-scene-edit__state:first-child p:not(.ig-foot)' ), 'footText' => array( 'Text under the ribbon', '.g-foot p, .ig-scene-edit__state:first-child .ig-foot' ), 'name' => array( 'Company name', '.co-item h2, .ig-scene-edit__state + .ig-scene-edit__state h2' ), 'text' => array( 'Company text', '.co-item p, .ig-scene-edit__state + .ig-scene-edit__state p' ), 'link' => array( 'Company link', '.co-item .more, .ig-more' ), 'tabs' => array( 'Company list', '.co-tabs button' ), 'logo' => array( 'Company logo', '.co-logo .logo, .ig-scene-edit__logo' ) ),
		'imagient/figures'     => array( 'tile' => array( 'Tiles', '.tile' ), 'label' => array( 'Label', '.tile .lbl' ), 'number' => array( 'Figure', '.tile strong' ), 'suffix' => array( 'Figure suffix (+, %, t)', '.tile strong sup' ), 'caption' => array( 'Caption', '.tile small' ) ),
		'imagient/cards'       => array( 'card' => array( 'Cards', '.card' ), 'title' => array( 'Card title', '.card h3' ), 'image' => array( 'Card photo', '.card .pi img' ) ),
		'imagient/services'    => array( 'row' => array( 'Rows', '.ig-svc__row' ), 'title' => array( 'Title', '.ig-svc__title' ), 'text' => array( 'Text', '.ig-svc__text' ), 'mark' => array( 'Mark (line)', '.ig-svc__wave' ) ),
		'imagient/cover'       => array( 'photo' => array( 'Photo', '.ig-cover__media img, .ig-cover-edit img' ), 'shade' => array( 'Colour over the photo', '.ig-cover__shade' ), 'content' => array( 'Content', '.ig-cover__inner, .ig-cover-edit__inner' ) ),
		'imagient/menu'        => array( 'button' => array( 'Menu button', '.ig-menu__btn, .ig-menu-edit__btn' ), 'panel' => array( 'Menu background', '.ig-menu__panel' ), 'links' => array( 'Menu links', '.ig-menu__panel li a, .ig-menu-edit__label' ), 'slogan' => array( 'Slogan', '.ig-menu__slogan' ) ),
		'imagient/logo'        => array( 'logo' => array( 'Logo', '.logo, img' ) ),
		'imagient/section'     => array( 'inner' => array( 'Inner container', '.ig-container' ), 'cf7fields' => array( 'Form: fields', '.wpcf7 :is(input[type="text"], input[type="email"], input[type="tel"], input[type="url"], input[type="number"], input[type="date"], textarea, select)' ), 'cf7placeholders' => array( 'Form: placeholders', '.wpcf7 :is(input, textarea)::placeholder' ), 'cf7consent' => array( 'Form: consent', '.ig-cf7__consent' ), 'cf7button' => array( 'Form: Send button', '.wpcf7 .wpcf7-submit' ), 'cf7message' => array( 'Form: message after sending', '.wpcf7 form .wpcf7-response-output' ), 'cf7errors' => array( 'Form: field errors', '.wpcf7 .wpcf7-not-valid-tip' ) ),
		'imagient/box'         => array( 'inner' => array( 'Inner container', '.ig-box__inner' ) ),
		'core/button'          => array( 'link' => array( 'Button', '.wp-block-button__link' ) ),
		'core/buttons'         => array( 'link' => array( 'Buttons', '.wp-block-button__link' ) ),
		'core/image'           => array( 'image' => array( 'Image', 'img' ), 'caption' => array( 'Caption', 'figcaption' ) ),
		'core/list'            => array( 'items' => array( 'Items', 'li' ), 'links' => array( 'Links', 'a' ) ),
		'core/group'           => array( 'headings' => array( 'Headings inside', 'h1, h2, h3, h4' ), 'texts' => array( 'Paragraphs inside', 'p' ), 'links' => array( 'Links inside', 'a' ) ),
	) );
}

/** A part's selector, inside the block's own one (each of its comma-separated parts). */
function imagient_style_scope( $sel, $inner ) {
	return implode( ',', array_map( function ( $p ) use ( $sel ) { return $sel . ' ' . trim( $p ); }, explode( ',', $inner ) ) );
}

/** One set of breakpoints and hover, for a selector. */
function imagient_style_rules( $style, $sel, $hover = true ) {
	$css = '';
	if ( $d = imagient_style_decls( $style['d'] ?? array() ) ) {
		$css .= $sel . '{' . $d . '}';
	}
	if ( $hover && ( $h = imagient_style_decls( $style['h'] ?? array() ) ) ) {
		$css .= implode( ',', array_map( function ( $p ) { return $p . ':hover'; }, explode( ',', $sel ) ) ) . '{' . $h . '}';
	}
	if ( $t = imagient_style_decls( $style['t'] ?? array() ) ) {
		$css .= '@media (max-width:1024px){' . $sel . '{' . $t . '}}';
	}
	if ( $m = imagient_style_decls( $style['m'] ?? array() ) ) {
		$css .= '@media (max-width:640px){' . $sel . '{' . $m . '}}';
	}
	return $css;
}

/** Blocks whose children sit in an inner container: their Layout settings act there. */
function imagient_style_layout_inner() {
	return array( 'imagient/section' => '.ig-container', 'imagient/cover' => '.ig-cover__inner' );
}
function imagient_style_layout_keys() {
	return array( 'display', 'flexDirection', 'justifyContent', 'alignItems', 'alignContent', 'flexWrap', 'gap', 'rowGap', 'columnGap', 'gridTemplateColumns', 'gridTemplateRows' );
}

/** The whole stylesheet for one block, scoped to its class. */
function imagient_style_css( $style, $cls, $name = '' ) {
	// :root and two classes: wins over the blocks' and the theme's own rules
	$sel = ':root .' . $cls . '.' . $cls;
	$css = '';
	// its parts (title, text, button …), each with its own breakpoints and hover
	$parts = imagient_style_elements()[ $name ] ?? array();
	if ( ! empty( $style['el'] ) && is_array( $style['el'] ) ) {
		foreach ( $style['el'] as $k => $set ) {
			if ( isset( $parts[ $k ] ) && is_array( $set ) ) {
				$css .= imagient_style_rules( $set, imagient_style_scope( $sel, $parts[ $k ][1] ), false === strpos( $parts[ $k ][1], '::' ) );
			}
		}
	}
	$inner = imagient_style_layout_inner()[ $name ] ?? '';
	if ( $inner ) {
		$keys = array_flip( imagient_style_layout_keys() );
		$lay  = array();
		foreach ( array( 'd', 't', 'm' ) as $bp ) {
			if ( ! empty( $style[ $bp ] ) && is_array( $style[ $bp ] ) ) {
				$lay[ $bp ]   = array_intersect_key( $style[ $bp ], $keys );
				$style[ $bp ] = array_diff_key( $style[ $bp ], $keys );
			}
		}
		$css .= imagient_style_rules( $lay, $sel . ' > ' . $inner, false );
	}
	$css .= imagient_style_rules( $style, $sel );
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
	$cls = 'igs-' . substr( md5( ( $block['blockName'] ?? '' ) . wp_json_encode( $style ) ), 0, 8 );
	$css = imagient_style_css( $style, $cls, $block['blockName'] ?? '' );
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
