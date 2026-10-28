<?php
/**
 * Shared helpers: brand palette, spacing scale, logos, responsive values.
 */

defined( 'ABSPATH' ) || exit;

/** Brand colours (also defined in the theme's theme.json; these are the fallbacks the blocks use). */
function imagient_palette() {
	return array(
		array( 'slug' => 'group', 'name' => 'Aries Group', 'color' => '#145fad' ),
		array( 'slug' => 'agency', 'name' => 'Shipping Agency', 'color' => '#3089a9' ),
		array( 'slug' => 'interlink', 'name' => 'Interlink', 'color' => '#2aa8da' ),
		array( 'slug' => 'logistics', 'name' => 'Logistics', 'color' => '#42979c' ),
		array( 'slug' => 'ink', 'name' => 'Ink', 'color' => '#111418' ),
		array( 'slug' => 'text', 'name' => 'Text', 'color' => '#2a2f36' ),
		array( 'slug' => 'muted', 'name' => 'Muted', 'color' => '#6a7280' ),
		array( 'slug' => 'line', 'name' => 'Line', 'color' => '#d9dee5' ),
		array( 'slug' => 'paper', 'name' => 'White', 'color' => '#ffffff' ),
	);
}

/** Spacing steps 0–9 (CSS vars --ig-s0 … --ig-s9, fluid, defined in aries.css). */
function imagient_space_scale() {
	return array(
		array( 'value' => '0', 'label' => '0' ),
		array( 'value' => '1', 'label' => 'XXS (4)' ),
		array( 'value' => '2', 'label' => 'XS (8)' ),
		array( 'value' => '3', 'label' => 'S (16)' ),
		array( 'value' => '4', 'label' => 'M (24)' ),
		array( 'value' => '5', 'label' => 'L (40)' ),
		array( 'value' => '6', 'label' => 'XL (64)' ),
		array( 'value' => '7', 'label' => '2XL (96)' ),
		array( 'value' => '8', 'label' => '3XL (128)' ),
		array( 'value' => '9', 'label' => '4XL (160)' ),
	);
}

/** The colour value for a palette slug, or the value itself when it is a custom colour. */
function imagient_color( $value ) {
	if ( ! $value ) {
		return '';
	}
	foreach ( imagient_palette() as $c ) {
		if ( $c['slug'] === $value ) {
			return 'var(--wp--preset--color--' . $c['slug'] . ', ' . $c['color'] . ')';
		}
	}
	return preg_match( '/^(#[0-9a-f]{3,8}|rgba?\([^)]*\)|hsla?\([^)]*\)|var\(--[\w-]+\))$/i', $value ) ? $value : '';
}

function imagient_logo_list() {
	return array(
		'group'     => 'Aries Group',
		'agency'    => 'Aries Shipping Agency',
		'interlink' => 'Aries Interlink',
		'logistics' => 'Aries Logistics',
	);
}

/**
 * A design logo as inline SVG: no fixed size (CSS sets the width), no clip wrapper (it only repeats the viewBox,
 * and its id would clash when a logo is used twice on a page).
 */
function imagient_logo_svg( $name ) {
	$name = sanitize_key( $name );
	$file = IMAGIENT_BLOCKS_DIR . 'assets/logos/' . $name . '.svg';
	if ( ! isset( imagient_logo_list()[ $name ] ) || ! file_exists( $file ) ) {
		return '';
	}
	static $cache = array();
	if ( ! isset( $cache[ $name ] ) ) {
		$svg = trim( file_get_contents( $file ) );
		$svg = preg_replace( '/<g clip-path="url\(#[^)]+\)">|<\/g>\s*<defs>.*?<\/defs>/s', '', $svg );
		$svg = preg_replace( '/\s(width|height)="[\d.]+"/', '', $svg, 2 );
		$svg = preg_replace( '/<svg /', '<svg class="logo logo-' . $name . '" aria-hidden="true" focusable="false" ', $svg, 1 );
		$cache[ $name ] = str_replace( "\n", '', $svg );
	}
	return $cache[ $name ];
}

/**
 * Responsive value: attributes keep one object per breakpoint ({d:…, t:…, m:…}); tablet falls back to desktop and
 * mobile to tablet. Every value is written out for all three breakpoints, so a nested block never inherits its
 * parent's custom properties.
 */
function imagient_resolve( $val, $key, $default ) {
	$val = json_decode( wp_json_encode( $val ), true );   // defaults hold objects, saved blocks hold arrays
	$val = is_array( $val ) ? $val : array();
	$d = isset( $val['d'][ $key ] ) && '' !== $val['d'][ $key ] ? $val['d'][ $key ] : $default;
	$t = isset( $val['t'][ $key ] ) && '' !== $val['t'][ $key ] ? $val['t'][ $key ] : $d;
	$m = isset( $val['m'][ $key ] ) && '' !== $val['m'][ $key ] ? $val['m'][ $key ] : $t;
	return array( 'd' => $d, 't' => $t, 'm' => $m );
}

/** CSS custom properties for one responsive setting: --{name}-d / -t / -m. */
function imagient_vars( $name, $resolved, $map = null ) {
	$out = '';
	foreach ( array( 'd', 't', 'm' ) as $bp ) {
		$v = $resolved[ $bp ];
		if ( is_bool( $v ) ) {
			$v = $v ? '1' : '0';
		}
		if ( $map ) {
			$v = call_user_func( $map, $v );
		}
		$out .= '--' . $name . '-' . $bp . ':' . $v . ';';
	}
	return $out;
}

/** A spacing value: a scale step (0–9) or a free CSS length typed in the editor (24px, 2rem, clamp(…)). */
function imagient_space_var( $step ) {
	$step = (string) $step;
	if ( ! preg_match( '/^\d$/', $step ) ) {
		$v = function_exists( 'imagient_style_value' ) ? imagient_style_value( $step ) : '';
		return '' !== $v ? $v : 'var(--ig-s4)';
	}
	return imagient_space_step( $step );
}
function imagient_space_step( $step ) {
	$step = (string) intval( $step );
	return 'var(--ig-s' . max( 0, min( 9, (int) $step ) ) . ')';
}

/** Wrapper attributes (class, anchor, align) from the block supports, plus our own. */
function imagient_wrapper( $block, $class, $style = '', $extra = array() ) {
	$attrs = array( 'class' => $class );
	if ( $style ) {
		$attrs['style'] = $style;
	}
	return get_block_wrapper_attributes( array_merge( $attrs, $extra ) );
}

/**
 * A theme image URL with its file time as a version: the host caches images for a week, so a replaced file gets a
 * new URL. Other URLs (the media library, other sites) are returned as they are.
 */
function imagient_versioned( $url ) {
	if ( ! function_exists( 'get_theme_file_uri' ) || ! is_string( $url ) || '' === $url ) {
		return $url;
	}
	$base = get_theme_file_uri( 'assets/' );
	if ( 0 !== strpos( $url, $base ) ) {
		return $url;
	}
	$rel  = strtok( substr( $url, strlen( $base ) ), '?' );
	$file = get_theme_file_path( 'assets/' . $rel );
	return file_exists( $file ) ? add_query_arg( 'v', filemtime( $file ), $base . $rel ) : $url;
}

/**
 * An <img> for a URL, wrapped in a <picture> with an AVIF source when the theme ships an .avif beside a .webp (the
 * default photos): browsers that read AVIF get the smaller file, the others the WebP. $attrs are the <img>'s
 * attributes (escaped here).
 */
function imagient_picture( $url, $attrs ) {
	$img = '<img src="' . esc_url( imagient_versioned( $url ) ) . '"';
	foreach ( $attrs as $k => $v ) {
		$img .= ' ' . $k . '="' . esc_attr( $v ) . '"';
	}
	$img .= '>';
	$avif = preg_replace( '/\.webp(\?.*)?$/i', '.avif', (string) $url );
	if ( $avif === $url || ! function_exists( 'get_theme_file_uri' ) || 0 !== strpos( $url, get_theme_file_uri( 'assets/' ) ) ) {
		return $img;
	}
	$rel = substr( strtok( $avif, '?' ), strlen( get_theme_file_uri( 'assets/' ) ) );
	if ( ! file_exists( get_theme_file_path( 'assets/' . $rel ) ) ) {
		return $img;
	}
	return '<picture><source type="image/avif" srcset="' . esc_url( imagient_versioned( $avif ) ) . '">' . $img . '</picture>';
}
