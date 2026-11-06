<?php
/**
 * The admin side: the login screen with the Aries Group logo in place of WordPress's, nothing else added, and a short
 * note on the dashboard about editing the site. Nothing here changes the public site.
 */

defined( 'ABSPATH' ) || exit;

// the login screen: the Aries Group logo (WordPress's own is replaced), the brand's blue on the buttons and links;
// (the selectors outweigh the login stylesheet, which is printed after this)
add_action( 'login_enqueue_scripts', function () {
	// (written into the page: the screen names no plugin path)
	$file = defined( 'IMAGIENT_BLOCKS_DIR' ) ? IMAGIENT_BLOCKS_DIR . 'assets/logos/group.svg' : '';
	$logo = $file && is_readable( $file ) ? 'data:image/svg+xml;base64,' . base64_encode( (string) file_get_contents( $file ) ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
	?>
<style>
  body.login #login h1 a { <?php echo $logo ? 'background: url("' . esc_attr( $logo ) . '") center / contain no-repeat;' : 'display: none;'; ?> width: 64px; height: 113px; margin-bottom: 28px; }
  body.login .wp-core-ui .button-primary, body.login.wp-core-ui .button-primary { background: #145fad; border-color: #145fad; }
  body.login .wp-core-ui .button-primary:hover, body.login.wp-core-ui .button-primary:hover { background: #0f4e90; border-color: #0f4e90; }
  body.login #backtoblog a, body.login #nav a { color: #145fad; }
</style>
	<?php
} );
add_filter( 'login_headerurl', function () { return home_url( '/' ); } );
add_filter( 'login_headertext', function () { return get_bloginfo( 'name' ); } );

// the dashboard: where the blocks, patterns and animations are
add_action( 'wp_dashboard_setup', function () {
	wp_add_dashboard_widget( 'aries_help', 'Editing the site', function () {
		echo '<p>Pages are built from blocks. In the editor, open the <strong>+</strong> inserter: the <strong>Aries</strong> category has the layout blocks (Section, Flex, Grid, Box) and the Aries scenes; the <strong>Patterns</strong> tab has every page section ready to drop in.</p>';
		echo '<p>Any block can get a scroll animation: select it and open <em>Animation</em> in the sidebar. Its <em>Style</em> panel sets fonts, sizes, colours and spacing.</p>';
	} );
} );
