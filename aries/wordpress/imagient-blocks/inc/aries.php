<?php
/**
 * The Aries scenes: Logo, Hero, Company scene (identity), Figures, Service cards, Ribbon. Texts, images, links and
 * colours are attributes edited in place; the markup and the motion (aries.js) are fixed, so the scenes always
 * behave as designed.
 */

defined( 'ABSPATH' ) || exit;

function imagient_aries_defaults() {
	$img = function_exists( 'get_theme_file_uri' ) ? get_theme_file_uri( 'assets/images/' ) : '';
	return array(
		'companies' => array(
			array( 'name' => 'Aries Shipping Agency', 'text' => 'Maritime and river agency providing comprehensive agency services in all Romanian Black Sea and Danube ports.', 'linkText' => 'View more', 'linkUrl' => '#contact', 'logo' => 'agency', 'shape' => 'agency', 'color' => 'agency' ),
			array( 'name' => 'Aries Interlink', 'text' => 'Supply chain support across Romania, with expertise in rail transport and rail operations mainly for grains, steel products and fertilizers.', 'linkText' => 'View more', 'linkUrl' => '#contact', 'logo' => 'interlink', 'shape' => 'interlink', 'color' => 'interlink' ),
			array( 'name' => 'Aries Logistics', 'text' => 'Bulk and break-bulk logistics, including stevedoring, transport, customs brokerage, warehousing, freight forwarding and door-to-door delivery.', 'linkText' => 'View more', 'linkUrl' => '#contact', 'logo' => 'logistics', 'shape' => 'logistics', 'color' => 'logistics' ),
		),
		'tiles'     => array(
			array( 'label' => 'Aries Shipping Agency', 'shape' => 'agencyT', 'color' => 'agency', 'wide' => true, 'figures' => array(
				array( 'value' => '600', 'suffix' => '+', 'caption' => 'Maritime port calls annually' ),
				array( 'value' => '800', 'suffix' => '+', 'caption' => 'Inland waterway port calls annually' ),
			) ),
			array( 'label' => 'Aries Interlink', 'shape' => 'interlinkT', 'color' => 'interlink', 'wide' => false, 'figures' => array(
				array( 'value' => '800,000', 'suffix' => 't', 'caption' => 'Bulk grain transported by rail annually' ),
			) ),
			array( 'label' => 'Aries Logistics', 'shape' => 'logisticsT', 'color' => 'logistics', 'wide' => false, 'figures' => array(
				array( 'value' => '300,000', 'suffix' => 't', 'caption' => 'Cargo volumes managed annually' ),
			) ),
		),
		'cards'     => array(
			array( 'title' => 'Multimodal transport, stevedoring and warehousing.', 'imageId' => 0, 'imageUrl' => $img . 'card-stacker.webp', 'alt' => 'Reach stacker lifting a container in the port', 'url' => '' ),
			array( 'title' => 'Port agency and all related services.', 'imageId' => 0, 'imageUrl' => $img . 'card-vessel.webp', 'alt' => 'Bulk carrier moored at the grain terminal', 'url' => '' ),
			array( 'title' => 'Freight forwarding and full-service logistics.', 'imageId' => 0, 'imageUrl' => $img . 'card-stacker.webp', 'alt' => '', 'url' => '' ),
			array( 'title' => 'Cargo chartering and brokerage.', 'imageId' => 0, 'imageUrl' => $img . 'card-vessel.webp', 'alt' => '', 'url' => '' ),
		),
		'heroImage' => $img . 'hero.webp',
	);
}

/** Ribbon shapes from the design files, and where each tile shape sits in its tile (measured on the design). */
function imagient_ribbon_shapes() {
	return array(
		'group'      => 'Group (arc and peak)',
		'agency'     => 'Shipping Agency (wave)',
		'interlink'  => 'Interlink (zigzag)',
		'logistics'  => 'Logistics (peak)',
		'agencyT'    => 'Shipping Agency, tile',
		'interlinkT' => 'Interlink, tile',
		'logisticsT' => 'Logistics, tile',
	);
}

function imagient_register_aries_blocks() {
	$d      = imagient_aries_defaults();
	$common = array(
		'api_version'   => 3,
		'category'      => 'imagient',
		'editor_script' => 'imagient-blocks-editor',
	);

	register_block_type( 'imagient/logo', array_merge( $common, array(
		'title'           => 'Logo',
		'description'     => 'An Aries logo from the design files: Group or one of the companies, in colour or white.',
		'supports'        => array( 'className' => true, 'html' => false ),
		'attributes'      => array(
			'logo'  => array( 'type' => 'string', 'default' => 'group' ),
			'white' => array( 'type' => 'boolean', 'default' => false ),
			'width' => array( 'type' => 'number', 'default' => 75 ),
			'link'  => array( 'type' => 'boolean', 'default' => true ),
		),
		'render_callback' => 'imagient_render_logo',
	) ) );

	register_block_type( 'imagient/hero', array_merge( $common, array(
		'title'           => 'Aries hero',
		'description'     => 'The cover: the Aries frame (two bands closed by a bar at each end) with the photo inside. On scroll the frame grows from the centre, the bands open, the photo fills the screen with its own text, then the blue fills the screen.',
		'supports'        => array( 'className' => true, 'html' => false, 'align' => array( 'full' ), 'multiple' => false ),
		'attributes'      => array(
			'align'      => array( 'type' => 'string', 'default' => 'full' ),
			'title'      => array( 'type' => 'string', 'default' => 'Where possibilities arise' ),
			'subtitle'   => array( 'type' => 'string', 'default' => 'Aries Group is among Romania’s leading transport and logistics providers.' ),
			'sceneTitle' => array( 'type' => 'string', 'default' => 'The partner of choice in Romania across the entire transport chain.' ),
			'sceneText'  => array( 'type' => 'string', 'default' => 'Established in 2003, Aries Group operates across the Black Sea and Danube region.' ),
			'imageId'    => array( 'type' => 'number', 'default' => 0 ),
			'imageUrl'   => array( 'type' => 'string', 'default' => $d['heroImage'] ),
		),
		'render_callback' => 'imagient_render_hero',
	) ) );

	register_block_type( 'imagient/identity', array_merge( $common, array(
		'title'           => 'Aries company scene',
		'description'     => 'Pinned scene: one ribbon flows from the Group shape through each company’s shape while colour, logo and text change.',
		'supports'        => array( 'className' => true, 'html' => false, 'align' => array( 'full' ), 'anchor' => true, 'multiple' => false ),
		'attributes'      => array(
			'align'      => array( 'type' => 'string', 'default' => 'full' ),
			'anchor'     => array( 'type' => 'string', 'default' => 'group' ),
			'introTitle' => array( 'type' => 'string', 'default' => 'Integrated maritime and logistics solutions that expand possibilities.' ),
			'introText'  => array( 'type' => 'string', 'default' => 'We integrate maritime, rail and road transport capabilities to create flexible supply chain solutions tailored to the specific needs of each customer.' ),
			'footText'   => array( 'type' => 'string', 'default' => 'With specialized divisions, each focused on a specific area of expertise, we reduce operational friction and give our customers greater predictability and operational control, regardless of project complexity.' ),
			'companies'  => array( 'type' => 'array', 'default' => $d['companies'] ),
		),
		'render_callback' => 'imagient_render_identity',
	) ) );

	register_block_type( 'imagient/figures', array_merge( $common, array(
		'title'           => 'Aries figures',
		'description'     => 'Company tiles with their ribbons and figures that count up when they come into view.',
		'supports'        => array( 'className' => true, 'html' => false, 'align' => array( 'full' ), 'anchor' => true ),
		'attributes'      => array(
			'align' => array( 'type' => 'string', 'default' => 'full' ),
			'tiles' => array( 'type' => 'array', 'default' => $d['tiles'] ),
		),
		'render_callback' => 'imagient_render_figures',
	) ) );

	register_block_type( 'imagient/cards', array_merge( $common, array(
		'title'           => 'Aries service cards',
		'description'     => 'A row of cards with the diagonal photo cut; the section pins and the row slides sideways with the scroll.',
		'supports'        => array( 'className' => true, 'html' => false, 'align' => array( 'full' ), 'anchor' => true, 'multiple' => false ),
		'attributes'      => array(
			'align' => array( 'type' => 'string', 'default' => 'full' ),
			'cards' => array( 'type' => 'array', 'default' => $d['cards'] ),
		),
		'render_callback' => 'imagient_render_cards',
	) ) );

	register_block_type( 'imagient/ribbon', array_merge( $common, array(
		'title'           => 'Ribbon',
		'description'     => 'A decorative Aries ribbon behind the content of a Section or Box; it draws in with the scroll.',
		'supports'        => array( 'className' => true, 'html' => false ),
		'attributes'      => array(
			'shape'   => array( 'type' => 'string', 'default' => 'group' ),
			'size'    => array( 'type' => 'number', 'default' => 110 ),
			'x'       => array( 'type' => 'number', 'default' => -5 ),
			'y'       => array( 'type' => 'number', 'default' => 55 ),
			'opacity' => array( 'type' => 'number', 'default' => 100 ),
			'pan'     => array( 'type' => 'boolean', 'default' => false ),
		),
		'render_callback' => 'imagient_render_ribbon',
	) ) );
}

function imagient_text( $html ) {
	return wp_kses( $html, array( 'br' => array(), 'strong' => array(), 'em' => array(), 'a' => array( 'href' => array() ), 'span' => array( 'class' => array() ) ) );
}

function imagient_render_logo( $a ) {
	$svg = imagient_logo_svg( $a['logo'] );
	if ( ! $svg ) {
		return '';
	}
	$w     = max( 24, min( 300, intval( $a['width'] ) ) );
	$style = '--logo-w:clamp(' . round( $w * 0.6 ) . 'px, ' . round( $w / 14.4, 3 ) . 'vw, ' . $w . 'px);';
	$class = 'ig-logo' . ( $a['white'] ? ' is-white' : '' );
	$inner = $a['link'] ? '<a class="logo-link" href="' . esc_url( home_url( '/' ) ) . '" aria-label="' . esc_attr( imagient_logo_list()[ $a['logo'] ] ) . '">' . $svg . '</a>' : $svg;
	return sprintf( '<div %s>%s</div>', imagient_wrapper( null, $class, $style ), $inner );
}

function imagient_render_hero( $a ) {
	$img = '';
	if ( $a['imageId'] ) {
		// every size WordPress made of the upload (WebP/AVIF when the server converts them); the photo is drawn
		// rotated and larger than the screen, so the browser is told it fills about 1.6 screen widths
		$img = wp_get_attachment_image( $a['imageId'], 'full', false, array(
			'id' => 'heroSrc', 'alt' => '', 'sizes' => '160vw', 'fetchpriority' => 'high', 'decoding' => 'async', 'loading' => 'eager',
		) );
	}
	if ( ! $img ) {
		$img = '<img id="heroSrc" src="' . esc_url( imagient_versioned( $a['imageUrl'] ) ) . '" alt="" width="1500" height="1020" fetchpriority="high" decoding="async">';
	}
	ob_start();
	?>
<section <?php echo imagient_wrapper( null, 'hero', '', array( 'id' => 'hero' ) ); ?>>
  <div class="hero-stage">
    <svg class="hero-svg" id="heroSvg" viewBox="0 0 2007 876" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
      <defs><clipPath id="hClip"><path id="hRegion" d="M0 0Z"/></clipPath></defs>
      <g id="hZoom">
        <g clip-path="url(#hClip)"><g id="hPhoto"><g id="hImgWrap"><image id="hImg" x="0" y="0" width="1500" height="1020" preserveAspectRatio="none"/></g></g></g>
        <path id="hTop" fill="#145FAD" d="M0 0Z"/>
        <path id="hBot" fill="#145FAD" d="M0 0Z"/>
        <path id="hBarL" fill="#145FAD" d="M0 0Z"/>
        <path id="hBarR" fill="#145FAD" d="M0 0Z"/>
      </g>
    </svg>
    <div class="hero-src" aria-hidden="true"><?php echo $img; // phpcs:ignore -- core-escaped ?></div>
    <div class="shade" id="shade"></div>
    <div class="hero-layout">
      <div class="hero-slot" id="heroSlot"></div>
      <div class="hero-title wrap" id="heroTitle">
        <h1><?php echo imagient_text( $a['title'] ); ?></h1>
        <p><?php echo imagient_text( $a['subtitle'] ); ?></p>
      </div>
    </div>
  </div>
  <div class="scene-two" id="sceneTwo">
    <div class="wrap">
      <h2><?php echo imagient_text( $a['sceneTitle'] ); ?></h2>
      <p><?php echo imagient_text( $a['sceneText'] ); ?></p>
    </div>
  </div>
</section>
	<?php
	return ob_get_clean();
}

function imagient_render_identity( $a ) {
	$cos    = array_values( array_filter( (array) $a['companies'], 'is_array' ) );
	$shapes = array( 'group' );
	$cols   = array( imagient_color( 'group' ) );
	foreach ( $cos as $c ) {
		$shapes[] = isset( imagient_ribbon_shapes()[ $c['shape'] ?? '' ] ) ? $c['shape'] : 'group';
		$cols[]   = imagient_color( $c['color'] ?? 'group' ) ?: imagient_color( 'group' );
	}
	// real colour values for the runtime (it tweens between them)
	$hex = array_map( function ( $v ) {
		if ( preg_match( '/var\(--[\w-]+, (#[0-9a-f]+)\)/i', $v, $m ) ) {
			return $m[1];
		}
		return $v;
	}, $cols );
	$anchor = $a['anchor'] ? $a['anchor'] : 'group';
	ob_start();
	?>
<section <?php echo imagient_wrapper( null, 'identity on-dark', '', array( 'id' => $anchor, 'data-tone' => 'dark', 'aria-label' => 'Aries Group and its companies', 'data-shapes' => wp_json_encode( $shapes ), 'data-colors' => wp_json_encode( $hex ) ) ); ?>>
  <div class="id-bg" id="idBg"></div>
  <svg class="rib-svg" data-rib="identity" aria-hidden="true"></svg>
  <div class="id-top"><div class="wrap">
    <div class="id-states">
      <div class="g-intro">
        <h2><?php echo imagient_text( $a['introTitle'] ); ?></h2>
        <p><?php echo imagient_text( $a['introText'] ); ?></p>
      </div>
	<?php foreach ( $cos as $c ) : ?>
      <div class="co-logo"><?php echo imagient_logo_svg( str_replace( 'group', 'group', $c['logo'] ?? 'group' ) ); ?></div>
	<?php endforeach; ?>
    </div>
    <ul class="co-tabs" aria-label="Companies">
	<?php foreach ( $cos as $k => $c ) : ?>
      <li><button type="button" data-co="<?php echo $k + 1; ?>"><?php echo esc_html( wp_strip_all_tags( $c['name'] ?? '' ) ); ?></button></li>
	<?php endforeach; ?>
    </ul>
  </div></div>
  <div class="id-foot"><div class="wrap id-states">
    <div class="g-foot"><p><?php echo imagient_text( $a['footText'] ); ?></p></div>
	<?php foreach ( $cos as $c ) : ?>
    <div class="co-item"><h2><?php echo imagient_text( $c['name'] ?? '' ); ?></h2><p><?php echo imagient_text( $c['text'] ?? '' ); ?></p>
		<?php if ( ! empty( $c['linkText'] ) ) : ?><a class="more" href="<?php echo esc_url( $c['linkUrl'] ?? '#' ); ?>"><?php echo esc_html( $c['linkText'] ); ?></a><?php endif; ?></div>
	<?php endforeach; ?>
  </div></div>
	<?php
	// anchors for links to a state: #<scene>, #<scene>-1 … (also the company names, e.g. #aries-interlink)
	foreach ( array_merge( array( '' ), $cos ) as $k => $c ) {
		$ids = $k ? array( $anchor . '-' . $k, sanitize_title( $c['name'] ?? '' ) ) : array();
		foreach ( array_filter( $ids ) as $id ) {
			echo '<span class="ig-anchor" id="' . esc_attr( $id ) . '" data-state="' . intval( $k ) . '"></span>';
		}
	}
	?>
</section>
	<?php
	return ob_get_clean();
}

function imagient_render_figures( $a ) {
	$tiles = array_values( array_filter( (array) $a['tiles'], 'is_array' ) );
	ob_start();
	?>
<section <?php echo imagient_wrapper( null, 'figs', '', array( 'aria-label' => 'Figures' ) ); ?>>
  <div class="wrap" data-tiles>
	<?php foreach ( $tiles as $t ) :
		$shape = isset( imagient_ribbon_shapes()[ $t['shape'] ?? '' ] ) ? $t['shape'] : 'agencyT';
		$bg    = imagient_color( $t['color'] ?? 'agency' ) ?: imagient_color( 'agency' );
		?>
    <article class="tile<?php echo ! empty( $t['wide'] ) ? ' wide' : ''; ?>" data-tone="dark" style="background:<?php echo esc_attr( $bg ); ?>"><svg class="rib-svg" data-rib="tile" data-shape="<?php echo esc_attr( $shape ); ?>" aria-hidden="true"></svg>
      <span class="lbl"><?php echo imagient_text( $t['label'] ?? '' ); ?></span>
      <div class="nums">
		<?php foreach ( (array) ( $t['figures'] ?? array() ) as $f ) : ?>
        <div><strong><?php echo esc_html( $f['value'] ?? '' ); ?><sup><?php echo esc_html( $f['suffix'] ?? '' ); ?></sup></strong><small><?php echo imagient_text( $f['caption'] ?? '' ); ?></small></div>
		<?php endforeach; ?>
      </div>
    </article>
	<?php endforeach; ?>
  </div>
</section>
	<?php
	return ob_get_clean();
}

function imagient_render_cards( $a ) {
	$cards = array_values( array_filter( (array) $a['cards'], 'is_array' ) );
	ob_start();
	?>
<section <?php echo imagient_wrapper( null, 'cards', '', array( 'id' => 'services', 'aria-label' => 'Services' ) ); ?>>
  <div class="cards-track" id="cardsTrack">
	<?php foreach ( $cards as $c ) :
		$img = ! empty( $c['imageId'] ) ? wp_get_attachment_image( $c['imageId'], 'large', false, array( 'alt' => $c['alt'] ?? '', 'sizes' => '(max-width: 760px) 84vw, 40vw', 'loading' => 'lazy', 'decoding' => 'async' ) ) : '';
		if ( ! $img && ! empty( $c['imageUrl'] ) ) {
			$img = '<img src="' . esc_url( imagient_versioned( $c['imageUrl'] ) ) . '" alt="' . esc_attr( $c['alt'] ?? '' ) . '" loading="lazy" decoding="async">';
		}
		$tag = ! empty( $c['url'] ) ? 'a href="' . esc_url( $c['url'] ) . '"' : 'article';
		$end = ! empty( $c['url'] ) ? 'a' : 'article';
		?>
    <<?php echo $tag; ?> class="card" data-tone="dark"><div class="pi"><?php echo $img; // phpcs:ignore ?></div><div class="bl" aria-hidden="true"></div><h3><?php echo imagient_text( $c['title'] ?? '' ); ?></h3></<?php echo $end; ?>>
	<?php endforeach; ?>
  </div>
</section>
	<?php
	return ob_get_clean();
}

function imagient_render_ribbon( $a ) {
	$shape = isset( imagient_ribbon_shapes()[ $a['shape'] ] ) ? $a['shape'] : 'group';
	$place = wp_json_encode( array(
		'w' => max( 10, min( 500, floatval( $a['size'] ) ) ) / 100,
		'x' => max( -400, min( 100, floatval( $a['x'] ) ) ) / 100,
		'y' => max( -50, min( 150, floatval( $a['y'] ) ) ) / 100,
	) );
	$style = 'opacity:' . max( 0, min( 100, intval( $a['opacity'] ) ) ) / 100 . ';';
	return sprintf(
		'<svg %s data-rib="decor" data-shape="%s" data-place="%s" data-pan="%s" aria-hidden="true"></svg>',
		imagient_wrapper( null, 'rib-svg ig-ribbon', $style ),
		esc_attr( $shape ),
		esc_attr( $place ),
		$a['pan'] ? '1' : '0'
	);
}
