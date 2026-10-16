<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UMEX_HARDENING_PAGE', 'umex-hardening' );

add_action( 'admin_menu', 'umex_hardening_admin_menu' );
add_action( 'admin_init', 'umex_hardening_register_setting' );
add_action( 'update_option_' . UMEX_HARDENING_OPTION, 'umex_hardening_on_options_updated', 10, 2 );
add_action( 'add_option_' . UMEX_HARDENING_OPTION, 'umex_hardening_on_options_added', 10, 2 );
add_action( 'admin_post_umex_hardening_check', 'umex_hardening_handle_check' );
add_action( 'admin_post_umex_hardening_clear_reports', 'umex_hardening_handle_clear_reports' );
add_filter( 'plugin_action_links_' . plugin_basename( UMEX_HARDENING_FILE ), 'umex_hardening_action_links' );

function umex_hardening_admin_menu() {
	add_options_page( 'UMEX Hardening', 'UMEX Hardening', 'manage_options', UMEX_HARDENING_PAGE, 'umex_hardening_render_page' );
}

function umex_hardening_register_setting() {
	register_setting(
		'umex_hardening',
		UMEX_HARDENING_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'umex_hardening_sanitize_options',
		)
	);
}

function umex_hardening_action_links( $links ) {
	array_unshift( $links, '<a href="' . esc_url( umex_hardening_page_url() ) . '">Setări</a>' );
	return $links;
}

function umex_hardening_page_url( $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => UMEX_HARDENING_PAGE ), $args ), admin_url( 'options-general.php' ) );
}

/* -------------------------------------------------------------------------
 * Keep .htaccess in sync with the saved settings
 * ---------------------------------------------------------------------- */

function umex_hardening_on_options_added( $option, $value ) {
	umex_hardening_on_options_updated( array(), $value );
}

function umex_hardening_on_options_updated( $old, $new ) {
	$new = is_array( $new ) ? $new : array();
	$old = is_array( $old ) ? $old : array();

	if ( ! empty( $new['htaccess_rules'] ) ) {
		$result = umex_hardening_write_htaccess( true, array_merge( umex_hardening_defaults(), $new ) );
	} elseif ( ! empty( $old['htaccess_rules'] ) ) {
		$result = umex_hardening_write_htaccess( false );
	} else {
		return;
	}

	if ( is_wp_error( $result ) ) {
		set_transient( 'umex_hardening_htaccess_error', $result->get_error_message(), 5 * MINUTE_IN_SECONDS );
	}
}

/* -------------------------------------------------------------------------
 * Actions
 * ---------------------------------------------------------------------- */

function umex_hardening_handle_clear_reports() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Acces interzis.', 403 );
	}
	check_admin_referer( 'umex_hardening_clear_reports' );
	delete_option( UMEX_HARDENING_CSP_REPORTS_OPTION );
	wp_safe_redirect( umex_hardening_page_url( array( 'umex_notice' => 'cleared' ) ) );
	exit;
}

function umex_hardening_handle_check() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Acces interzis.', 403 );
	}
	check_admin_referer( 'umex_hardening_check' );
	set_transient( 'umex_hardening_check_' . get_current_user_id(), umex_hardening_run_checks(), 30 * MINUTE_IN_SECONDS );
	wp_safe_redirect( umex_hardening_page_url( array( 'umex_notice' => 'checked' ) ) . '#umex-check' );
	exit;
}

/**
 * Requests the site from the server itself, as an anonymous visitor would.
 *
 * @return array[] Each row: label, status (ok|fail|info), detail.
 */
function umex_hardening_run_checks() {
	$options = umex_hardening_options();
	$args    = array(
		'timeout'     => 15,
		'redirection' => 0,
		'sslverify'   => apply_filters( 'https_local_ssl_verify', false ),
		'headers'     => array( 'Cache-Control' => 'no-cache' ),
	);
	$rows    = array();

	$home = wp_remote_get( home_url( '/' ), $args );
	if ( is_wp_error( $home ) ) {
		return array( array( 'Conexiune către propriul site', 'fail', $home->get_error_message() ) );
	}

	$expected = array(
		'strict-transport-security' => 'Strict-Transport-Security (HSTS)',
		'x-content-type-options'    => 'X-Content-Type-Options',
		'x-frame-options'           => 'X-Frame-Options',
		'content-security-policy'   => 'Content-Security-Policy',
		'referrer-policy'           => 'Referrer-Policy',
	);
	foreach ( $expected as $header => $label ) {
		$value = wp_remote_retrieve_header( $home, $header );
		$value = is_array( $value ) ? implode( ', ', $value ) : (string) $value;
		if ( 'strict-transport-security' === $header && 'https' !== wp_parse_url( home_url(), PHP_URL_SCHEME ) ) {
			$rows[] = array( $label, 'info', 'Site-ul nu rulează pe HTTPS, deci HSTS nu se trimite.' );
			continue;
		}
		$rows[] = array( $label, '' !== $value ? 'ok' : 'fail', '' !== $value ? $value : 'lipsește' );
	}

	$xmlrpc = wp_remote_post(
		site_url( 'xmlrpc.php' ),
		array_merge(
			$args,
			array(
				'headers' => array( 'Content-Type' => 'text/xml' ),
				'body'    => '<?xml version="1.0"?><methodCall><methodName>system.listMethods</methodName></methodCall>',
			)
		)
	);
	$code   = wp_remote_retrieve_response_code( $xmlrpc );
	$rows[] = array(
		'xmlrpc.php',
		in_array( $code, array( 401, 403, 404 ), true ) ? 'ok' : ( $options['block_xmlrpc'] ? 'fail' : 'info' ),
		is_wp_error( $xmlrpc ) ? $xmlrpc->get_error_message() : 'HTTP ' . $code,
	);

	$users  = wp_remote_get( rest_url( 'wp/v2/users' ), $args );
	$code   = wp_remote_retrieve_response_code( $users );
	$rows[] = array(
		'/wp-json/wp/v2/users',
		in_array( $code, array( 401, 403, 404 ), true ) ? 'ok' : ( $options['restrict_users_api'] ? 'fail' : 'info' ),
		is_wp_error( $users ) ? $users->get_error_message() : 'HTTP ' . $code,
	);

	$author   = wp_remote_get( add_query_arg( 'author', 1, home_url( '/' ) ), $args );
	$location = (string) wp_remote_retrieve_header( $author, 'location' );
	$leaks    = false !== strpos( $location, '/author/' );
	$rows[]   = array(
		'?author=1 (enumerare)',
		$leaks ? 'fail' : 'ok',
		is_wp_error( $author ) ? $author->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $author ) . ( $location ? ' → ' . $location : '' ),
	);

	$cron   = wp_remote_get( site_url( 'wp-cron.php' ), $args );
	$code   = wp_remote_retrieve_response_code( $cron );
	$rows[] = array(
		'wp-cron.php fără cheie',
		403 === $code ? 'ok' : ( $options['restrict_wp_cron'] ? 'fail' : 'info' ),
		is_wp_error( $cron ) ? $cron->get_error_message() : 'HTTP ' . $code . ( $options['restrict_wp_cron'] ? '' : ' (restricția e dezactivată)' ),
	);

	if ( $options['restrict_wp_cron'] ) {
		$signed = wp_remote_get( umex_hardening_cron_url(), $args );
		$code   = wp_remote_retrieve_response_code( $signed );
		$rows[] = array(
			'wp-cron.php cu cheie',
			200 === $code ? 'ok' : 'fail',
			is_wp_error( $signed ) ? $signed->get_error_message() : 'HTTP ' . $code,
		);
	}

	return $rows;
}

/* -------------------------------------------------------------------------
 * Page
 * ---------------------------------------------------------------------- */

function umex_hardening_checkbox( $key, $label, $help ) {
	$options = umex_hardening_options();
	printf(
		'<tr><th scope="row">%1$s</th><td><label><input type="checkbox" name="%2$s[%3$s]" value="1" %4$s> Activat</label><p class="description">%5$s</p></td></tr>',
		esc_html( $label ),
		esc_attr( UMEX_HARDENING_OPTION ),
		esc_attr( $key ),
		checked( ! empty( $options[ $key ] ), true, false ),
		wp_kses( $help, array( 'code' => array(), 'strong' => array(), 'br' => array() ) )
	);
}

function umex_hardening_render_notices() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	$notice = isset( $_GET['umex_notice'] ) ? sanitize_key( $_GET['umex_notice'] ) : '';
	if ( 'cleared' === $notice ) {
		echo '<div class="notice notice-success is-dismissible"><p>Rapoartele CSP au fost șterse.</p></div>';
	}

	$error = get_transient( 'umex_hardening_htaccess_error' );
	if ( $error ) {
		delete_transient( 'umex_hardening_htaccess_error' );
		echo '<div class="notice notice-error"><p><strong>.htaccess:</strong> ' . esc_html( $error ) . '</p></div>';
	}
}

function umex_hardening_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	global $is_apache;

	$options = umex_hardening_options();
	$reports = get_option( UMEX_HARDENING_CSP_REPORTS_OPTION, array() );
	$reports = is_array( $reports ) ? $reports : array();
	$checks  = get_transient( 'umex_hardening_check_' . get_current_user_id() );
	$name    = UMEX_HARDENING_OPTION;
	?>
	<div class="wrap">
		<h1>UMEX Hardening</h1>
		<p>Rezolvă punctele din raportul de securitate fără să modifice fișierele WordPress. Fiecare protecție se poate opri separat.</p>
		<?php umex_hardening_render_notices(); ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'umex_hardening' ); ?>

			<h2>Acces neautentificat</h2>
			<table class="form-table" role="presentation">
				<?php
				umex_hardening_checkbox( 'block_xmlrpc', 'Blochează xmlrpc.php', 'Răspunde cu 403 la orice cerere către <code>xmlrpc.php</code> și oprește pingback-urile. Lasă dezactivat doar dacă folosiți aplicația mobilă WordPress sau Jetpack.' );
				umex_hardening_checkbox( 'restrict_users_api', 'Ascunde lista de utilizatori', 'Blochează <code>/wp-json/wp/v2/users</code> pentru vizitatorii nelogați, redirecționează <code>?author=N</code> spre prima pagină și scoate autorul din oEmbed. Editorul și Divi funcționează normal pentru utilizatorii logați.' );
				umex_hardening_checkbox( 'restrict_wp_cron', 'Restricționează wp-cron.php', 'Acceptă <code>wp-cron.php</code> doar cu cheia de mai jos sau din linia de comandă. Rularea automată a WordPress primește cheia singură. <strong>Dacă hostingul are deja un cron care apelează wp-cron.php prin URL, actualizează-l cu adresa de mai jos înainte să activezi.</strong>' );
				?>
			</table>

			<h2>Header-e de securitate</h2>
			<table class="form-table" role="presentation">
				<?php
				umex_hardening_checkbox( 'security_headers', 'Trimite header-ele de securitate', 'HSTS (doar pe HTTPS), <code>X-Content-Type-Options: nosniff</code>, <code>X-Frame-Options: SAMEORIGIN</code>, <code>Referrer-Policy</code>, <code>Permissions-Policy</code> și un CSP minimal (<code>frame-ancestors \'self\'; object-src \'none\'; base-uri \'self\'</code>) care nu afectează Divi sau scripturile externe.' );
				umex_hardening_checkbox( 'hsts_subdomains', 'HSTS pentru subdomenii', 'Adaugă <code>includeSubDomains</code>. Activează doar dacă <strong>toate</strong> subdomeniile (mail, cpanel, staging etc.) au certificat SSL valid. Browserele țin minte setarea un an.' );
				umex_hardening_checkbox( 'htaccess_rules', 'Scrie regulile și în .htaccess', 'Aplică blocarea xmlrpc.php și header-ele direct din server, inclusiv pentru fișiere statice (CSS, JS, imagini) și pagini servite din cache. Funcționează pe Apache și LiteSpeed. Blocul se scoate automat la dezactivarea pluginului.' );
				?>
			</table>

			<h2>Content-Security-Policy completă (mod de test)</h2>
			<table class="form-table" role="presentation">
				<?php umex_hardening_checkbox( 'csp_report_only', 'Testează politica CSP', 'Trimite politica de mai jos ca <code>Content-Security-Policy-Report-Only</code>: browserul nu blochează nimic, doar raportează ce ar bloca. Lasă activat 1–2 săptămâni, adaugă domeniile din tabelul de rapoarte, apoi politica poate fi aplicată de hosting.' ); ?>
				<tr>
					<th scope="row"><label for="umex-csp">Politica CSP</label></th>
					<td>
						<textarea id="umex-csp" class="large-text code" rows="6" name="<?php echo esc_attr( $name ); ?>[csp_policy]"><?php echo esc_textarea( $options['csp_policy'] ); ?></textarea>
						<p class="description">Directivele se separă cu <code>;</code>. Golește câmpul și salvează pentru a reveni la politica implicită.</p>
					</td>
				</tr>
			</table>

			<?php submit_button( 'Salvează' ); ?>
		</form>

		<hr>
		<h2 id="umex-check">Verificare</h2>
		<p>Accesează site-ul din server ca un vizitator anonim și verifică fiecare punct din raport.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="umex_hardening_check">
			<?php wp_nonce_field( 'umex_hardening_check' ); ?>
			<?php submit_button( 'Rulează verificarea', 'secondary', 'submit', false ); ?>
		</form>
		<?php if ( is_array( $checks ) && $checks ) : ?>
			<table class="widefat striped" style="margin-top:12px;max-width:960px">
				<thead><tr><th>Verificare</th><th>Rezultat</th><th>Detalii</th></tr></thead>
				<tbody>
				<?php
				$labels = array(
					'ok'   => '<span style="color:#008a20">✔ OK</span>',
					'fail' => '<span style="color:#d63638">✘ Problemă</span>',
					'info' => '<span style="color:#646970">• Info</span>',
				);
				foreach ( $checks as $row ) {
					printf(
						'<tr><td>%1$s</td><td>%2$s</td><td><code>%3$s</code></td></tr>',
						esc_html( $row[0] ),
						isset( $labels[ $row[1] ] ) ? wp_kses_post( $labels[ $row[1] ] ) : '',
						esc_html( $row[2] )
					);
				}
				?>
				</tbody>
			</table>
			<p class="description">Dacă site-ul folosește cache de pagini, golește cache-ul înainte de verificare.</p>
		<?php endif; ?>

		<hr>
		<h2>Cron de la hosting (opțional)</h2>
		<p>Dacă restricționezi <code>wp-cron.php</code> și vrei ca sarcinile programate să ruleze și fără vizitatori, adaugă în cPanel → Cron Jobs (la 5 minute) una dintre comenzi:</p>
		<p><code>php <?php echo esc_html( ABSPATH . 'wp-cron.php' ); ?></code></p>
		<p><code>wget -q -O - "<?php echo esc_html( umex_hardening_cron_url() ); ?>" &gt;/dev/null 2&gt;&amp;1</code></p>
		<p class="description">Adresa conține o cheie secretă; nu o publica.</p>

		<hr>
		<h2>.htaccess</h2>
		<?php if ( ! $is_apache ) : ?>
			<p><strong>Serverul nu e Apache/LiteSpeed.</strong> Pentru Nginx, trimite hostingului regulile echivalente:</p>
<pre style="background:#fff;border:1px solid #c3c4c7;padding:12px;max-width:960px;overflow:auto">location = /xmlrpc.php { deny all; }
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Strict-Transport-Security "max-age=31536000" always;</pre>
		<?php else : ?>
			<p>Stare: <strong><?php echo umex_hardening_htaccess_has_block() ? 'blocul „UMEX Hardening” este scris în .htaccess' : 'nu există reguli scrise în .htaccess'; ?></strong></p>
			<details>
				<summary>Regulile care se scriu</summary>
				<pre style="background:#fff;border:1px solid #c3c4c7;padding:12px;max-width:960px;overflow:auto"><?php echo esc_html( implode( "\n", umex_hardening_htaccess_lines( $options ) ) ); ?></pre>
			</details>
		<?php endif; ?>

		<hr>
		<h2>Rapoarte CSP</h2>
		<?php if ( ! $reports ) : ?>
			<p><?php echo $options['csp_report_only'] ? 'Niciun raport încă. Navighează prin site (inclusiv paginile cu hartă și video) și revino.' : 'Activează „Testează politica CSP” ca să primești rapoarte.'; ?></p>
		<?php else : ?>
			<p>Resurse pe care politica le-ar bloca. Dacă sunt legitime, adaugă domeniul la directiva indicată.</p>
			<table class="widefat striped" style="max-width:960px">
				<thead><tr><th>Directivă</th><th>Resursă blocată</th><th>Pagina</th><th>Ultima dată</th><th>Ore cu raportări</th></tr></thead>
				<tbody>
				<?php
				uasort(
					$reports,
					function ( $a, $b ) {
						return $b['last'] - $a['last'];
					}
				);
				foreach ( $reports as $report ) {
					printf(
						'<tr><td><code>%1$s</code></td><td><code>%2$s</code></td><td>%3$s</td><td>%4$s</td><td>%5$d</td></tr>',
						esc_html( $report['directive'] ),
						esc_html( $report['blocked'] ),
						esc_html( $report['page'] ),
						esc_html( wp_date( 'Y-m-d H:i', (int) $report['last'] ) ),
						(int) $report['hours']
					);
				}
				?>
				</tbody>
			</table>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px">
				<input type="hidden" name="action" value="umex_hardening_clear_reports">
				<?php wp_nonce_field( 'umex_hardening_clear_reports' ); ?>
				<?php submit_button( 'Șterge rapoartele', 'delete', 'submit', false ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}
