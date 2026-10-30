<?php
/**
 * The company pages (Imagient · imagient.ro): Page hero (the company's frame with its photo, the logo and the
 * title), Cover (a photo band with a colour gradient and its own blocks on top), Services (a list of services in
 * four layouts) and Menu (the hamburger button and its full-screen menu). Texts, photos, colours and links are
 * attributes edited in place; the motion is in aries.js.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/frames.php';

function imagient_pages_defaults() {
	$img = function_exists( 'get_theme_file_uri' ) ? get_theme_file_uri( 'assets/images/' ) : '';
	return array(
		'sea'      => $img . 'shipping-agency-sea.webp',
		'port'     => $img . 'shipping-agency-port.webp',
		'services' => array(
			array( 'title' => 'Agricultural Commodities Agency', 'text' => 'We handle ships which load or discharge 25% of all agricultural products exported and imported via Romanian ports, including grain, oil seeds, meals, edible oils and sugar.' ),
			array( 'title' => 'Tanker Agency', 'text' => 'Our tanker agency operations cover 20% of tanker traffic in Romanian ports, including crude oil, petroleum products and liquid chemicals, on behalf of Romania’s largest petroleum company and other major traders.' ),
			array( 'title' => 'Fertilizers Agency', 'text' => 'More than 50% of fertilizer traffic through Romanian ports is handled by our agency, including imports, exports and transit cargoes, on behalf of Romania’s largest fertilizer producer and major local and international traders.' ),
			array( 'title' => 'Agency for Steel Products and General Cargo', 'text' => 'Our expertise covers tramp and break-bulk liner vessels, with parceling services between the Black Sea and North/Central America, Middle East, Indian Subcontinent and Far East, plus cargo supervision, port captain and customs services.' ),
			array( 'title' => 'Agency for Passenger and Naval Vessels', 'text' => 'For passenger, naval and auxiliary vessels, we draw on intra-group expertise and resources in ship agency, bunkering, logistics, procurement, storage, chandling, supply and transport to meet the requirements of shipowners and operators.' ),
			array( 'title' => 'River Ships Agency', 'text' => 'We provide agency services for river vessels, barges and pushers in all Romanian ports, including channel transit formalities, pilotage and towage coordination, customs transit procedures and brokerage for cargo transported via inland waterways.' ),
		),
		'menu'     => array(
			array( 'label' => 'Home', 'url' => '/' ),
			array( 'label' => 'Aries Shipping Agency', 'url' => '/aries-shipping-agency/' ),
			array( 'label' => 'Aries Interlink', 'url' => '#' ),
			array( 'label' => 'Aries Logistics', 'url' => '#' ),
			array( 'label' => 'News', 'url' => '#' ),
			array( 'label' => 'Contact', 'url' => '#contact' ),
		),
	);
}

/** A photo from the media library (every size WordPress made) or a theme default (AVIF sizes and a WebP). */
function imagient_photo( $id, $url, $alt, $attrs ) {
	if ( $id ) {
		$html = wp_get_attachment_image( $id, 'full', false, array_merge( array( 'alt' => $alt ), $attrs ) );
		if ( $html ) {
			return $html;
		}
	}
	return $url ? imagient_picture( $url, array_merge( array( 'alt' => $alt ), $attrs ) ) : '';
}

function imagient_register_page_blocks() {
	$d      = imagient_pages_defaults();
	$common = array(
		'api_version'   => 3,
		'category'      => 'imagient',
		'editor_script' => 'imagient-blocks-editor',
	);

	register_block_type( 'imagient/page-hero', array_merge( $common, array(
		'title'           => 'Page hero',
		'description'     => 'The top of a company page: the logo, the company frame with a photo inside and the page title. Five opening animations to choose from.',
		'supports'        => array( 'anchor' => true, 'className' => true, 'html' => false, 'align' => array( 'full' ), 'multiple' => false ),
		'attributes'      => array(
			'align'     => array( 'type' => 'string', 'default' => 'full' ),
			'title'     => array( 'type' => 'string', 'default' => 'Aries Shipping Agency' ),
			'logo'      => array( 'type' => 'string', 'default' => 'agency' ),
			'frame'     => array( 'type' => 'string', 'default' => 'agency' ),
			'color'     => array( 'type' => 'string', 'default' => 'agency' ),
			'imageId'   => array( 'type' => 'number', 'default' => 0 ),
			'imageUrl'  => array( 'type' => 'string', 'default' => $d['sea'] ),
			'focusX'    => array( 'type' => 'number', 'default' => 55 ),
			'focusY'    => array( 'type' => 'number', 'default' => 52 ),
			'zoom'      => array( 'type' => 'number', 'default' => 100 ),
			'portraitX' => array( 'type' => 'number', 'default' => 1060 ),
		),
		'render_callback' => 'imagient_render_page_hero',
	) ) );

	register_block_type( 'imagient/cover', array_merge( $common, array(
		'title'           => 'Cover',
		'description'     => 'A full-width photo band with a colour gradient over it and its own blocks on top (title, text, link). The photo drifts gently with the scroll.',
		'supports'        => array( 'anchor' => true, 'className' => true, 'html' => false, 'align' => array( 'full' ) ),
		'attributes'      => array(
			'align'      => array( 'type' => 'string', 'default' => 'full' ),
			'imageId'    => array( 'type' => 'number', 'default' => 0 ),
			'imageUrl'   => array( 'type' => 'string', 'default' => $d['sea'] ),
			'alt'        => array( 'type' => 'string', 'default' => '' ),
			'height'     => array( 'type' => 'string', 'default' => 'image' ),
			'focusX'     => array( 'type' => 'number', 'default' => 50 ),
			'focusY'     => array( 'type' => 'number', 'default' => 50 ),
			'overlay'    => array( 'type' => 'boolean', 'default' => true ),
			'from'       => array( 'type' => 'string', 'default' => 'agency' ),
			'to'         => array( 'type' => 'string', 'default' => 'group' ),
			'angle'      => array( 'type' => 'number', 'default' => 100 ),
			'strength'   => array( 'type' => 'number', 'default' => 100 ),
			'tone'       => array( 'type' => 'string', 'default' => 'dark' ),
			'parallax'   => array( 'type' => 'boolean', 'default' => true ),
			'shade'      => array( 'type' => 'string', 'default' => 'text' ),   // text: deep on the text side; tint: light colour at both edges
		),
		'render_callback' => 'imagient_render_cover',
	) ) );

	register_block_type( 'imagient/services', array_merge( $common, array(
		'title'           => 'Services list',
		'description'     => 'A list of services, each a title and a short text, in one of three layouts: as designed (title, wave, text), editorial (numbered rows) or accordion.',
		'supports'        => array( 'anchor' => true, 'className' => true, 'html' => false ),
		'attributes'      => array(
			'items'   => array( 'type' => 'array', 'default' => $d['services'] ),
			'color'   => array( 'type' => 'string', 'default' => 'agency' ),
		),
		'render_callback' => 'imagient_render_services',
	) ) );

	register_block_type( 'imagient/menu', array_merge( $common, array(
		'title'           => 'Menu',
		'description'     => 'The hamburger button and the full-screen menu it opens: your links, in the brand blue.',
		'supports'        => array( 'className' => true, 'html' => false ),
		'attributes'      => array(
			'items'    => array( 'type' => 'array', 'default' => $d['menu'] ),
			'color'    => array( 'type' => 'string', 'default' => 'group' ),
			'linkedin' => array( 'type' => 'string', 'default' => 'https://www.linkedin.com/company/aries-group-romania/' ),
		),
		'render_callback' => 'imagient_render_menu',
	) ) );
}

function imagient_render_page_hero( $a ) {
	$frames = imagient_frames();
	$f      = $frames[ $a['frame'] ] ?? reset( $frames );
	$uid    = wp_unique_id( 'ph' );
	// the photo shows about 1.1 × the screen's width in the frame (on phones the frame runs 1.6 × past the screen)
	$photo  = imagient_photo( $a['imageId'], $a['imageUrl'], '', array( 'sizes' => '(orientation: portrait) 170vw, 110vw', 'fetchpriority' => 'high', 'decoding' => 'async', 'loading' => 'eager' ) );
	$logo   = $a['logo'] ? imagient_logo_svg( $a['logo'] ) : '';
	$style  = sprintf( '--accent:%s;--fw:%s;--fh:%s;--fx0:%s;--fx1:%s;--fpx:%s;', imagient_color( $a['color'] ), $f['w'], $f['h'], $f['x0'], $f['x1'], max( 300, min( $f['w'] - 300, floatval( $a['portraitX'] ) ) ) );
	$data   = array(
		'data-focus' => max( 0, min( 100, floatval( $a['focusX'] ) ) ) . ' ' . max( 0, min( 100, floatval( $a['focusY'] ) ) ),
		'data-zoom'  => max( 100, min( 250, floatval( $a['zoom'] ) ) ) / 100,
	);
	ob_start();
	?>
<section <?php echo imagient_wrapper( null, 'ig-phero', $style, $data ); ?>>
	<?php if ( $logo ) : ?>
  <a class="ig-phero__logo logo-link is-colour" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( imagient_logo_list()[ $a['logo'] ] ?? 'Home' ); ?>"><?php echo $logo; // phpcs:ignore -- sanitised SVG from the plugin ?></a>
	<?php endif; ?>
  <div class="ig-phero__stage">
    <div class="ig-phero__src" aria-hidden="true"><?php echo $photo; // phpcs:ignore -- core-escaped ?></div>
    <svg class="ig-phero__svg" viewBox="0 0 <?php echo esc_attr( $f['w'] . ' ' . $f['h'] ); ?>" aria-hidden="true" focusable="false">
      <defs>
        <clipPath id="<?php echo esc_attr( $uid ); ?>-w"><path class="ph-window" d="<?php echo esc_attr( $f['window'] ); ?>"/></clipPath>
        <clipPath id="<?php echo esc_attr( $uid ); ?>-d"><rect class="ph-draw" x="-20" y="-20" width="<?php echo esc_attr( $f['w'] + 40 ); ?>" height="<?php echo esc_attr( $f['h'] + 40 ); ?>"/></clipPath>
      </defs>
      <g class="ph-art" clip-path="url(#<?php echo esc_attr( $uid ); ?>-d)">
        <g clip-path="url(#<?php echo esc_attr( $uid ); ?>-w)">
          <g class="ph-photo"><image class="ph-img" x="<?php echo esc_attr( $f['x0'] ); ?>" y="0" width="<?php echo esc_attr( $f['x1'] - $f['x0'] ); ?>" height="<?php echo esc_attr( $f['h'] ); ?>" preserveAspectRatio="xMidYMid slice"/></g>
        </g>
        <path class="ph-frame" d="<?php echo esc_attr( $f['path'] ); ?>"/>
        <path class="ph-wbox" d="<?php echo esc_attr( $f['window'] ); ?>" fill="none"/>
      </g>
    </svg>
  </div>
  <h1 class="ig-phero__title"><?php echo imagient_text( $a['title'] ); ?></h1>
</section>
	<?php
	return ob_get_clean();
}

function imagient_render_cover( $a, $content ) {
	$height = in_array( $a['height'], array( 'image', 'screen', 'auto' ), true ) ? $a['height'] : 'image';
	$fx     = max( 0, min( 100, floatval( $a['focusX'] ) ) );
	$fy     = max( 0, min( 100, floatval( $a['focusY'] ) ) );
	$photo  = imagient_photo( $a['imageId'], $a['imageUrl'], $a['alt'], array( 'sizes' => '100vw', 'loading' => 'lazy', 'decoding' => 'async' ) );
	$style  = sprintf( '--cv-pos:%s%% %s%%;', $fx, $fy );
	if ( $a['overlay'] ) {
		$style .= sprintf( '--cv-from:%s;--cv-to:%s;--cv-angle:%sdeg;--cv-o:%s;', imagient_color( $a['from'] ), imagient_color( $a['to'] ), intval( $a['angle'] ), max( 0, min( 100, intval( $a['strength'] ) ) ) / 100 );
	}
	$class  = 'ig-cover is-' . $height . ( 'light' === $a['tone'] ? ' tone-light' : ' tone-dark' ) . ( $a['overlay'] ? ' has-overlay' : '' ) . ( $a['parallax'] ? ' is-parallax' : '' ) . ( trim( wp_strip_all_tags( $content, true ) ) || false !== strpos( $content, '<img' ) ? ' has-content' : '' );
	if ( 'tint' === $a['shade'] ) {
		$class .= ' is-tint';
	}
	return sprintf(
		'<section %s><div class="ig-cover__media">%s</div>%s<div class="ig-cover__inner ig-container is-gutter">%s</div></section>',
		imagient_wrapper( null, $class, $style ),
		$photo,
		$a['overlay'] ? '<div class="ig-cover__shade" aria-hidden="true"></div>' : '',
		$content
	);
}

/** The thin wave between a service's title and its text (from the design). */
function imagient_wave_svg( $class ) {
	// its entrance (script): a mask whose soft edge runs from left to right; the mask is far larger than the line, so
	// nothing of the wave is cut where it swells past its box
	$id = wp_unique_id( 'wv' );
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 212 26" aria-hidden="true" focusable="false"><defs>'
		. '<linearGradient id="' . $id . 'g" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="212" y2="0"><stop offset="1" stop-color="#fff"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>'
		. '<mask id="' . $id . '" maskUnits="userSpaceOnUse" x="-20" y="-80" width="252" height="186"><rect x="-20" y="-80" width="252" height="186" fill="url(#' . $id . 'g)"/></mask>'
		. '</defs><path d="M0.2 14.47C93.3 54.73 126.45-31.67 211.2 14.47" mask="url(#' . $id . ')"/></svg>';
}

function imagient_render_services( $a ) {
	$items   = array_values( array_filter( (array) $a['items'], 'is_array' ) );
	// the page shows the designed layout; ?list=2 (editorial) or ?list=3 (accordion) in the address previews the others
	$variant = array( '2' => 'editorial', '3' => 'accordion' )[ isset( $_GET['list'] ) ? sanitize_key( wp_unslash( $_GET['list'] ) ) : '' ] ?? 'design'; // phpcs:ignore WordPress.Security.NonceVerification -- a read-only display choice
	$uid     = wp_unique_id( 'svc' );
	$style   = '--accent:' . imagient_color( $a['color'] ) . ';';
	$out     = '';
	foreach ( $items as $i => $it ) {
		$title = imagient_text( $it['title'] ?? '' );
		$text  = imagient_text( $it['text'] ?? '' );
		$num   = sprintf( '%02d', $i + 1 );
		switch ( $variant ) {
			case 'editorial':
				$out .= '<li class="ig-svc__row"><span class="ig-svc__num">' . $num . '</span><h3 class="ig-svc__title">' . $title . '</h3><p class="ig-svc__text">' . $text . '</p><span class="ig-svc__rule" aria-hidden="true"></span></li>';
				break;
			case 'accordion':
				$open  = 0 === $i;
				$pid   = $uid . '-' . $i;
				$out  .= '<div class="ig-svc__row' . ( $open ? ' is-open' : '' ) . '"><h3 class="ig-svc__title"><button type="button" aria-expanded="' . ( $open ? 'true' : 'false' ) . '" aria-controls="' . esc_attr( $pid ) . '"><span class="ig-svc__num">' . $num . '</span><span class="ig-svc__label">' . $title . '</span><span class="ig-svc__plus" aria-hidden="true"></span></button></h3>'
					. '<div class="ig-svc__panel" id="' . esc_attr( $pid ) . '" role="region"' . ( $open ? '' : ' hidden' ) . '><div class="ig-svc__body">' . imagient_wave_svg( 'ig-svc__wave' ) . '<p class="ig-svc__text">' . $text . '</p></div></div></div>';
				break;
			default:
				$out .= '<div class="ig-svc__row"><h3 class="ig-svc__title">' . $title . '</h3>' . imagient_wave_svg( 'ig-svc__wave' ) . '<p class="ig-svc__text">' . $text . '</p></div>';
		}
	}
	$tag = 'editorial' === $variant ? 'ol' : 'div';
	return sprintf( '<%1$s %2$s>%3$s</%1$s>', $tag, imagient_wrapper( null, 'ig-svc is-' . $variant, $style, array( 'data-svc' => $variant ) ), $out );
}

function imagient_render_menu( $a ) {
	$items = array_values( array_filter( (array) $a['items'], 'is_array' ) );
	$uid   = wp_unique_id( 'menu' );
	$links = '';
	foreach ( $items as $it ) {
		$url    = $it['url'] ?? '#';
		$url    = '/' === $url ? home_url( '/' ) : $url;
		$links .= '<li><a href="' . esc_url( $url ) . '"><span>' . esc_html( wp_strip_all_tags( $it['label'] ?? '' ) ) . '</span></a></li>';
	}
	$li = $a['linkedin'] ? '<a class="ig-menu__li" href="' . esc_url( $a['linkedin'] ) . '" target="_blank" rel="noreferrer noopener" aria-label="Aries Group on LinkedIn"><svg viewBox="0 0 34 34" aria-hidden="true" focusable="false"><path d="M3.78 34h26.44A3.8 3.8 0 0 0 34 30.22V3.78A3.8 3.8 0 0 0 30.22 0H3.78A3.8 3.8 0 0 0 0 3.78v26.44A3.8 3.8 0 0 0 3.78 34Z" fill="#fff"/><path d="M29.27 29.27h-5.04v-8.59c0-2.36-.9-3.68-2.75-3.68-2.04 0-3.08 1.38-3.08 3.68v8.59h-4.85V12.91h4.85v2.2s1.46-2.71 4.93-2.71 5.96 2.12 5.96 6.5v10.36ZM7.71 10.75a3.01 3.01 0 1 1 0-6.04 3.01 3.01 0 0 1 0 6.04ZM5.2 29.27h5.08V12.91H5.2v16.36Z" fill="var(--menu-bg)"/></svg></a>' : '';
	return sprintf(
		'<div %1$s><button type="button" class="ig-menu__btn" aria-expanded="false" aria-controls="%2$s" aria-label="Open the menu"><span></span><span></span><span></span></button>'
		. '<div class="ig-menu__panel" id="%2$s" aria-hidden="true" inert><nav aria-label="Main"><ul>%3$s</ul></nav><div class="ig-menu__foot"><p class="ig-menu__slogan">Where possibilities arise</p>%4$s</div></div></div>',
		imagient_wrapper( null, 'ig-menu', '--menu-bg:' . imagient_color( $a['color'] ) . ';' ),
		esc_attr( $uid ),
		$links,
		$li
	);
}
