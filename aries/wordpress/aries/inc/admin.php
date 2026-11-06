<?php
/**
 * The admin side: the login screen without any logo (WordPress's is removed), and a short note on the dashboard about
 * editing the site. Nothing here changes the public site.
 */

defined( 'ABSPATH' ) || exit;

// the login screen: no logo above the form; the brand's blue on the buttons and links
// (the selectors outweigh the login stylesheet, which is printed after this)
add_action( 'login_enqueue_scripts', function () {
	?>
<style>
  body.login #login h1.wp-login-logo, body.login #login h1 a { display: none; }
  body.login #login { padding-top: 12vh; }
  body.login .wp-core-ui .button-primary, body.login.wp-core-ui .button-primary { background: #145fad; border-color: #145fad; }
  body.login .wp-core-ui .button-primary:hover, body.login.wp-core-ui .button-primary:hover { background: #0f4e90; border-color: #0f4e90; }
  body.login #backtoblog a, body.login #nav a { color: #145fad; }
</style>
	<?php
} );

// the dashboard: where the blocks, patterns and animations are
add_action( 'wp_dashboard_setup', function () {
	wp_add_dashboard_widget( 'aries_help', 'Editing the site', function () {
		echo '<p>Pages are built from blocks. In the editor, open the <strong>+</strong> inserter: the <strong>Aries</strong> category has the layout blocks (Section, Flex, Grid, Box) and the Aries scenes; the <strong>Patterns</strong> tab has every page section ready to drop in.</p>';
		echo '<p>Any block can get a scroll animation: select it and open <em>Animation</em> in the sidebar. Its <em>Style</em> panel sets fonts, sizes, colours and spacing.</p>';
	} );
} );
