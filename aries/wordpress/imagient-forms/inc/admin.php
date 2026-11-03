<?php
/**
 * Forms in the admin: every message kept (filter by form, read, delete, export as CSV) and the settings (the default
 * address, Cloudflare Turnstile, how long messages are kept).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'ig_form_entry', array(
		'labels'          => array(
			'name'               => 'Form messages',
			'singular_name'      => 'Form message',
			'menu_name'          => 'Forms',
			'all_items'          => 'Messages',
			'edit_item'          => 'Message',
			'view_item'          => 'Message',
			'search_items'       => 'Search messages',
			'not_found'          => 'No messages yet.',
			'not_found_in_trash' => 'No messages in the trash.',
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'show_in_rest'    => false,
		'menu_position'   => 26,
		'menu_icon'       => 'dashicons-email-alt',
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),   // made by the forms only
		'map_meta_cap'    => true,
	) );
} );

/* ---------- the list ---------- */

add_filter( 'manage_ig_form_entry_posts_columns', function () {
	return array(
		'cb'        => '<input type="checkbox">',
		'title'     => 'Message',
		'igf_form'  => 'Form',
		'igf_email' => 'Email',
		'igf_page'  => 'Sent from',
		'igf_mail'  => 'Email sent',
		'date'      => 'Date',
	);
} );
add_action( 'manage_ig_form_entry_posts_custom_column', function ( $col, $id ) {
	switch ( $col ) {
		case 'igf_form':
			echo esc_html( get_post_meta( $id, '_igf_form', true ) );
			break;
		case 'igf_email':
			$e = get_post_meta( $id, '_igf_email', true );
			echo $e ? '<a href="mailto:' . esc_attr( $e ) . '">' . esc_html( $e ) . '</a>' : '—';
			break;
		case 'igf_page':
			$u = get_post_meta( $id, '_igf_url', true );
			echo $u ? '<a href="' . esc_url( $u ) . '" target="_blank" rel="noopener">' . esc_html( wp_parse_url( $u, PHP_URL_PATH ) ?: '/' ) . '</a>' : '—';
			break;
		case 'igf_mail':
			echo get_post_meta( $id, '_igf_mailed', true ) ? '✓' : '<span style="color:#b32d2e">not sent</span>';
			break;
	}
}, 10, 2 );
add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( 'ig_form_entry' === $post->post_type ) {
		unset( $actions['inline hide-if-no-js'] );
		if ( isset( $actions['edit'] ) ) {
			$actions['edit'] = str_replace( '>Edit<', '>Read<', $actions['edit'] );
		}
	}
	return $actions;
}, 10, 2 );

// a filter by form, and the export button, above the list
add_action( 'restrict_manage_posts', function ( $type ) {
	if ( 'ig_form_entry' !== $type ) {
		return;
	}
	global $wpdb;
	$forms = $wpdb->get_col( "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_igf_form' ORDER BY meta_value" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$cur   = isset( $_GET['igf_form'] ) ? sanitize_text_field( wp_unslash( $_GET['igf_form'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	echo '<select name="igf_form"><option value="">All forms</option>';
	foreach ( $forms as $f ) {
		echo '<option value="' . esc_attr( $f ) . '"' . selected( $f, $cur, false ) . '>' . esc_html( $f ) . '</option>';
	}
	echo '</select>';
} );
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && 'ig_form_entry' === $q->get( 'post_type' ) && ! empty( $_GET['igf_form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$q->set( 'meta_key', '_igf_form' );
		$q->set( 'meta_value', sanitize_text_field( wp_unslash( $_GET['igf_form'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}
} );
add_action( 'manage_posts_extra_tablenav', function ( $which ) {
	$screen = get_current_screen();
	if ( 'top' !== $which || ! $screen || 'edit-ig_form_entry' !== $screen->id ) {
		return;
	}
	$url = wp_nonce_url( add_query_arg( array( 'action' => 'igf_export', 'igf_form' => isset( $_GET['igf_form'] ) ? sanitize_text_field( wp_unslash( $_GET['igf_form'] ) ) : '' ), admin_url( 'admin-post.php' ) ), 'igf_export' ); // phpcs:ignore WordPress.Security.NonceVerification
	echo '<div class="alignleft actions"><a class="button" href="' . esc_url( $url ) . '">Export CSV</a></div>';
} );

/* ---------- one message ---------- */

add_action( 'add_meta_boxes_ig_form_entry', function () {
	remove_meta_box( 'submitdiv', 'ig_form_entry', 'side' );
	add_meta_box( 'igf_fields', 'Message', function ( $post ) {
		$values = (array) get_post_meta( $post->ID, '_igf_fields', true );
		echo '<style>#igf_fields table{border-collapse:collapse;width:100%}#igf_fields th,#igf_fields td{text-align:left;vertical-align:top;padding:12px 16px 12px 0;border-top:1px solid #e3e6ea}#igf_fields th{width:220px}</style>';
		echo wp_kses_post( igf_table( $values ) );
	}, 'ig_form_entry', 'normal', 'high' );
	add_meta_box( 'igf_info', 'Details', function ( $post ) {
		$u = get_post_meta( $post->ID, '_igf_url', true );
		echo '<p><strong>Form:</strong> ' . esc_html( get_post_meta( $post->ID, '_igf_form', true ) ) . '</p>';
		echo '<p><strong>Received:</strong> ' . esc_html( get_the_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $post ) ) . '</p>';
		echo '<p><strong>Sent from:</strong> ' . ( $u ? '<a href="' . esc_url( $u ) . '" target="_blank" rel="noopener">' . esc_html( $u ) . '</a>' : '—' ) . '</p>';
		echo '<p><strong>Email:</strong> ' . ( get_post_meta( $post->ID, '_igf_mailed', true ) ? 'sent' : '<span style="color:#b32d2e">not sent (check the site\'s email settings)</span>' ) . '</p>';
		echo '<p><strong>Address:</strong> ' . esc_html( get_post_meta( $post->ID, '_igf_ip', true ) ?: '—' ) . '</p>';
		echo '<p><a class="submitdelete" style="color:#b32d2e" href="' . esc_url( get_delete_post_link( $post->ID ) ) . '">Move to trash</a></p>';
	}, 'ig_form_entry', 'side', 'high' );
} );

/* ---------- export ---------- */

add_action( 'admin_post_igf_export', function () {
	if ( ! current_user_can( 'edit_posts' ) || ! check_admin_referer( 'igf_export' ) ) {
		wp_die( 'Not allowed.' );
	}
	$form = isset( $_GET['igf_form'] ) ? sanitize_text_field( wp_unslash( $_GET['igf_form'] ) ) : '';
	$args = array( 'post_type' => 'ig_form_entry', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC', 'fields' => 'ids' );
	if ( $form ) {
		$args['meta_key']   = '_igf_form'; // phpcs:ignore WordPress.DB.SlowDBQuery
		$args['meta_value'] = $form; // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	$ids  = get_posts( $args );
	$cols = array();
	$rows = array();
	foreach ( $ids as $id ) {
		$row = array( 'Date' => get_the_date( 'Y-m-d H:i', $id ), 'Form' => get_post_meta( $id, '_igf_form', true ), 'Page' => get_post_meta( $id, '_igf_url', true ) );
		foreach ( (array) get_post_meta( $id, '_igf_fields', true ) as $v ) {
			$row[ $v['label'] ] = $v['value'];
			$cols[ $v['label'] ] = true;
		}
		$rows[] = $row;
	}
	$head = array_merge( array( 'Date', 'Form', 'Page' ), array_keys( $cols ) );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=form-messages-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" );   // Excel reads it as UTF-8
	$safe = function ( $v ) { $v = (string) $v; return preg_match( '/^[=+\-@\t\r]/', $v ) ? "'" . $v : $v; };   // no formulas
	fputcsv( $out, $head );
	foreach ( $rows as $r ) {
		fputcsv( $out, array_map( function ( $h ) use ( $r, $safe ) { return $safe( $r[ $h ] ?? '' ); }, $head ) );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
} );

/* ---------- settings ---------- */

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=ig_form_entry', 'Form settings', 'Settings', 'manage_options', 'igf-settings', 'igf_settings_page' );
} );
add_action( 'admin_init', function () {
	register_setting( 'igf_settings', 'igf_settings', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $v ) {
			$v = (array) $v;
			return array(
				'to'        => implode( ', ', igf_emails( $v['to'] ?? '' ) ),
				'ts_site'   => sanitize_text_field( $v['ts_site'] ?? '' ),
				'ts_secret' => sanitize_text_field( $v['ts_secret'] ?? '' ),
				'keep_days' => max( 0, (int) ( $v['keep_days'] ?? 0 ) ),
			);
		},
	) );
} );
function igf_settings_page() {
	$s = igf_settings();
	echo '<div class="wrap"><h1>Form settings</h1><form method="post" action="options.php">';
	settings_fields( 'igf_settings' );
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row"><label for="igf-to">Default recipients</label></th><td><input id="igf-to" class="regular-text" name="igf_settings[to]" value="' . esc_attr( $s['to'] ) . '"><p class="description">Used by forms with no address of their own (commas between several). Empty: ' . esc_html( get_option( 'admin_email' ) ) . '.</p></td></tr>';
	echo '<tr><th scope="row"><label for="igf-keep">Keep messages for</label></th><td><input id="igf-keep" type="number" min="0" class="small-text" name="igf_settings[keep_days]" value="' . esc_attr( $s['keep_days'] ) . '"> days <p class="description">0 keeps them until deleted. Older ones are deleted once a day.</p></td></tr>';
	echo '<tr><th scope="row">Cloudflare Turnstile</th><td><p><input class="regular-text" name="igf_settings[ts_site]" placeholder="Site key" value="' . esc_attr( $s['ts_site'] ) . '"></p><p><input class="regular-text" name="igf_settings[ts_secret]" placeholder="Secret key" value="' . esc_attr( $s['ts_secret'] ) . '"></p><p class="description">Optional: a check against bots on top of the built-in ones (keys from dash.cloudflare.com → Turnstile). Leave empty to use only the invisible checks.</p></td></tr>';
	echo '</table>';
	submit_button();
	echo '</form><h2>Sending email</h2><p>Messages go out through WordPress\' own mail. For them to land reliably (not in spam), connect the site to a mail service with an SMTP plugin such as WP Mail SMTP; the forms then use it with no further setting.</p></div>';
}

/* ---------- old messages deleted, if the settings ask for it ---------- */

add_action( 'igf_cleanup', function () {
	$days = (int) igf_settings()['keep_days'];
	if ( ! $days ) {
		return;
	}
	$old = get_posts( array( 'post_type' => 'ig_form_entry', 'post_status' => 'any', 'posts_per_page' => 200, 'fields' => 'ids', 'date_query' => array( array( 'before' => $days . ' days ago' ) ) ) );
	foreach ( $old as $id ) {
		wp_delete_post( $id, true );
	}
} );
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'igf_cleanup' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'igf_cleanup' );
	}
} );
register_deactivation_hook( IGF_DIR . 'imagient-forms.php', function () {
	wp_clear_scheduled_hook( 'igf_cleanup' );
} );
