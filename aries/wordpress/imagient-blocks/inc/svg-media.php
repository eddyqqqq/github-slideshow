<?php
/**
 * SVG files in the Media Library (the logos): administrators, who may post unfiltered HTML anyway, can upload them,
 * and the library shows them as pictures rather than as a file icon. Other users still cannot upload SVG.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'upload_mimes', function ( $mimes ) {
	if ( current_user_can( 'unfiltered_html' ) ) {
		$mimes['svg'] = 'image/svg+xml';
	}
	return $mimes;
} );

// WordPress also checks what the file really is; servers read an SVG as image/svg+xml, text/xml or text/plain
add_filter( 'wp_check_filetype_and_ext', function ( $data, $file, $filename ) {
	if ( current_user_can( 'unfiltered_html' ) && preg_match( '/\.svg$/i', (string) $filename ) && is_readable( $file ) ) {
		$head = (string) file_get_contents( $file, false, null, 0, 2048 );
		if ( false !== stripos( $head, '<svg' ) ) {
			$data['ext']  = 'svg';
			$data['type'] = 'image/svg+xml';
		}
	}
	return $data;
}, 10, 3 );

/** An SVG's size from its width/height or viewBox, for the library's preview. */
function imagient_svg_size( $file ) {
	$svg = is_readable( $file ) ? (string) file_get_contents( $file, false, null, 0, 4096 ) : '';
	if ( preg_match( '/<svg[^>]*\swidth="([\d.]+)"[^>]*\sheight="([\d.]+)"/i', $svg, $m ) || preg_match( '/<svg[^>]*\sviewBox="[\d.\-]+[\s,]+[\d.\-]+[\s,]+([\d.]+)[\s,]+([\d.]+)"/i', $svg, $m ) ) {
		return array( (int) round( $m[1] ), (int) round( $m[2] ) );
	}
	return array( 300, 300 );
}

// the library draws an image's preview from its sizes; an SVG has none, so it gets its own file as every size
add_filter( 'wp_prepare_attachment_for_js', function ( $response, $attachment ) {
	if ( isset( $response['mime'] ) && 'image/svg+xml' === $response['mime'] && empty( $response['sizes'] ) ) {
		list( $w, $h ) = imagient_svg_size( get_attached_file( $attachment->ID ) );
		// the library fills its square tiles: a tall logo marked "landscape" is fitted by its height, so it shows whole
		$fit  = $h > $w ? 'landscape' : 'portrait';
		$size = array( 'url' => $response['url'], 'width' => $w, 'height' => $h, 'orientation' => $fit );
		$response['width']       = $w;
		$response['height']      = $h;
		$response['orientation'] = $fit;
		$response['sizes']  = array( 'full' => $size, 'medium' => $size, 'thumbnail' => $size );
	}
	return $response;
}, 10, 2 );

// the company logos are white (they sit on colour on the site): the library shows every SVG on a mid grey, so they can be seen
add_action( 'admin_head', function () {
	echo '<style>.attachment-preview.subtype-svg\\+xml .thumbnail,.attachment-details .thumbnail img[src$=".svg"],.attachment-media-view img[src$=".svg"],.media-sidebar img[src$=".svg"]{background:#7d8590}'
		. '.attachment-preview.subtype-svg\\+xml .thumbnail img{padding:8px;box-sizing:border-box}</style>' . "\n";
} );
