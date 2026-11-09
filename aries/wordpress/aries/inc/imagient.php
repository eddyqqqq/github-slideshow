<?php
/**
 * Imagient credits in the admin: the admin footer and a dashboard note (the login screen shows none). Nothing here
 * changes the public site.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'admin_footer_text', function () {
	return 'Website designed and built by <a href="https://imagient.ro" target="_blank" rel="noopener">Imagient</a>.';
} );

// the login screen: no logo, no credit, nothing added; the brand's blue on the buttons and links
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

add_action( 'wp_dashboard_setup', function () {
	wp_add_dashboard_widget( 'imagient_help', 'Imagient · editing the site', function () {
		echo '<p>Pages are built from blocks. In the editor, open the <strong>+</strong> inserter: the <strong>Imagient</strong> category has the layout blocks (Section, Flex, Grid, Box) and the Aries scenes; the <strong>Patterns</strong> tab has every homepage section ready to drop in.</p>';
		echo '<p>Any block can get a scroll animation: select it and open <em>Animation (Imagient)</em> in the sidebar.</p>';
		echo '<p>Questions: <a href="https://imagient.ro" target="_blank" rel="noopener">Imagient</a>.</p>';
	} );
} );
