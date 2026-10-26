<?php
/**
 * Imagient credits in the admin: the login screen, the admin footer and a dashboard note. Nothing here
 * changes the public site.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'admin_footer_text', function () {
	return 'Website designed and built by <a href="https://imagient.ro" target="_blank" rel="noopener">Imagient</a>.';
} );

add_action( 'login_enqueue_scripts', function () {
	$logo = function_exists( 'imagient_logo_svg' ) ? IMAGIENT_BLOCKS_URL . 'assets/logos/group.svg' : '';
	?>
<style>
  body.login { background: #f4f7fb; font-family: "Area Normal", "Helvetica Neue", Arial, sans-serif; }
  body.login::before { content: ""; position: fixed; inset: auto 0 0 0; height: 38vh; background: #145fad; clip-path: polygon(0 40%, 38% 8%, 64% 46%, 100% 0, 100% 100%, 0 100%); z-index: -1; }
  .login h1 a { <?php echo $logo ? 'background: url(' . esc_url( $logo ) . ') center / contain no-repeat;' : ''; ?> width: 75px; height: 132px; }
  .login form { border: 0; border-radius: 6px; box-shadow: 0 10px 40px rgba(20, 95, 173, .12); }
  .wp-core-ui .button-primary { background: #145fad; border-color: #145fad; }
  .wp-core-ui .button-primary:hover { background: #0f4e90; border-color: #0f4e90; }
  .login #backtoblog a, .login #nav a { color: #145fad; }
  .imagient-credit { text-align: center; margin-top: 24px; font-size: 12px; color: #6a7280; }
  .imagient-credit a { color: #145fad; text-decoration: none; }
</style>
	<?php
} );
add_filter( 'login_headerurl', function () { return home_url( '/' ); } );
add_filter( 'login_headertext', function () { return get_bloginfo( 'name' ); } );
add_action( 'login_footer', function () {
	echo '<p class="imagient-credit">Built by <a href="https://imagient.ro" target="_blank" rel="noopener">Imagient</a></p>';
} );

add_action( 'wp_dashboard_setup', function () {
	wp_add_dashboard_widget( 'imagient_help', 'Imagient · editing the site', function () {
		echo '<p>Pages are built from blocks. In the editor, open the <strong>+</strong> inserter: the <strong>Imagient</strong> category has the layout blocks (Section, Flex, Grid, Box) and the Aries scenes; the <strong>Patterns</strong> tab has every homepage section ready to drop in.</p>';
		echo '<p>Any block can get a scroll animation: select it and open <em>Animation (Imagient)</em> in the sidebar.</p>';
		echo '<p>Questions: <a href="https://imagient.ro" target="_blank" rel="noopener">Imagient</a>.</p>';
	} );
} );
