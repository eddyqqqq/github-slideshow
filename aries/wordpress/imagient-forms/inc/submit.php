<?php
/**
 * A submission: the form is read from the page it came from, every value is checked against its field, spam is
 * turned away (a hidden field, the time taken, a rate per visitor, Turnstile if set), then the message is kept and
 * sent. With the script the form posts to the REST route and the page answers in place; without it the browser posts
 * to admin-post.php and comes back to the page (or goes to the redirect).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
	register_rest_route( 'imagient-forms/v1', '/submit', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',   // a public form: checked below (signed token, honeypot, rate)
		'callback'            => function ( WP_REST_Request $req ) {
			$r = igf_handle( $req->get_body_params() );
			return new WP_REST_Response( $r, $r['ok'] ? 200 : ( count( (array) $r['errors'] ) ? 422 : 400 ) );
		},
	) );
} );

// without the script
foreach ( array( 'admin_post_imagient_form', 'admin_post_nopriv_imagient_form' ) as $hook ) {
	add_action( $hook, function () {
		$r    = igf_handle( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification -- public form, signed token checked inside
		$key  = sanitize_key( $_POST['_igf'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
		$back = wp_get_referer() ?: home_url( '/' );
		if ( $r['ok'] && ! empty( $r['redirect'] ) ) {
			wp_redirect( $r['redirect'] ); // phpcs:ignore WordPress.Security.SafeRedirect -- set by the site's editors
			exit;
		}
		$back = remove_query_arg( array( 'igf_sent', 'igf_error' ), $back );
		wp_safe_redirect( add_query_arg( $r['ok'] ? 'igf_sent' : 'igf_error', $key, $back ) . '#igf-' . $key );
		exit;
	} );
}

/** The visitor's address (for the rate limit and the entry: the last part is dropped, as the address is personal data). */
function igf_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
}
function igf_ip_short( $ip ) {
	if ( false !== strpos( $ip, ':' ) ) {
		return implode( ':', array_slice( explode( ':', $ip ), 0, 3 ) ) . '::';
	}
	return preg_replace( '/\.\d+$/', '.0', $ip );
}

/** {placeholders} in a text: {form}, {site}, {page}, {date} and each field's name. */
function igf_fill( $text, $vars ) {
	return preg_replace_callback( '/\{([a-z0-9_]+)\}/', function ( $m ) use ( $vars ) {
		return array_key_exists( $m[1], $vars ) ? $vars[ $m[1] ] : $m[0];
	}, (string) $text );
}

/** The addresses in a list (commas, semicolons or new lines between them). */
function igf_emails( $list ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/[,;\s]+/', (string) $list ) ), 'is_email' ) );
}

/**
 * @return array ok, message, redirect, errors (field name => message)
 */
function igf_handle( $in ) {
	$fail = function ( $msg, $errors = array() ) {
		return array( 'ok' => false, 'message' => $msg, 'errors' => (object) $errors );
	};
	$key  = sanitize_key( $in['_igf'] ?? '' );
	$form = igf_find_form( (int) ( $in['_igp'] ?? 0 ), $key );
	if ( ! $form ) {
		return $fail( 'This form could not be found. Please reload the page and try again.' );
	}
	$a       = igf_attrs( $form );
	$generic = $a['msgError'];

	// spam: the hidden field filled in looks like success to a bot, and nothing is kept or sent
	if ( '' !== trim( (string) ( $in['ig_website'] ?? '' ) ) ) {
		return array( 'ok' => true, 'message' => $a['message'], 'errors' => (object) array() );
	}
	// the signed time: from this site, for this form, and not sent within two seconds of the page showing it
	$tok = explode( '.', (string) ( $in['_igt'] ?? '' ) );
	if ( 2 !== count( $tok ) || ! hash_equals( substr( hash_hmac( 'sha256', $tok[0] . '|' . $key, wp_salt( 'nonce' ) ), 0, 20 ), $tok[1] ) ) {
		return $fail( 'This page is out of date. Please reload it and send the form again.' );
	}
	if ( time() - (int) $tok[0] < 2 ) {
		return $fail( 'Please take a moment to check your message, then send it again.' );
	}
	// at most 5 messages every 10 minutes from one visitor
	$ip   = igf_ip();
	$rate = 'igf_rate_' . md5( $ip . wp_salt( 'nonce' ) );
	$sent = (int) get_transient( $rate );
	if ( $sent >= 5 ) {
		return $fail( 'Too many messages in a short time. Please try again in a few minutes.' );
	}
	// Cloudflare Turnstile, when its keys are set
	$set = igf_settings();
	if ( $set['ts_site'] && $set['ts_secret'] ) {
		$v = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array( 'timeout' => 10, 'body' => array(
			'secret'   => $set['ts_secret'],
			'response' => (string) ( $in['cf-turnstile-response'] ?? '' ),
			'remoteip' => $ip,
		) ) );
		$ok = ! is_wp_error( $v ) && ! empty( json_decode( wp_remote_retrieve_body( $v ), true )['success'] );
		if ( ! $ok ) {
			return $fail( 'Please confirm you are not a robot, then send the form again.' );
		}
	}

	// every field checked against its block
	$posted = isset( $in['ig'] ) && is_array( $in['ig'] ) ? $in['ig'] : array();
	$values = array();
	$errors = array();
	foreach ( igf_form_fields( $form ) as $f ) {
		$name = $f['key'];
		$type = $f['type'];
		$raw  = $posted[ $name ] ?? '';
		$opts = igf_options( $f );
		$val  = '';
		switch ( $type ) {
			case 'hidden':
				$val = sanitize_text_field( $f['defaultValue'] );   // the page's value, never the browser's
				break;
			case 'textarea':
				$val = mb_substr( sanitize_textarea_field( is_string( $raw ) ? $raw : '' ), 0, 5000 );
				break;
			case 'checkbox':
				$val = array_values( array_intersect( array_map( 'strval', (array) $raw ), array_map( 'strval', array_keys( $opts ) ) ) );
				break;
			case 'select':
			case 'radio':
				$val = is_string( $raw ) && array_key_exists( $raw, $opts ) ? $raw : '';
				if ( is_string( $raw ) && '' !== $raw && '' === $val ) {
					$errors[ $name ] = $a['msgInvalid'];
				}
				break;
			case 'consent':
				$val = 'yes' === $raw ? 'yes' : '';
				break;
			default:
				$val = mb_substr( sanitize_text_field( is_string( $raw ) ? $raw : '' ), 0, 500 );
		}
		$empty = is_array( $val ) ? ! $val : '' === trim( $val );
		if ( ! empty( $f['required'] ) && $empty && 'hidden' !== $type ) {
			$errors[ $name ] = $a['msgRequired'];
		} elseif ( ! $empty && ! isset( $errors[ $name ] ) ) {
			if ( 'email' === $type && ! is_email( $val ) ) {
				$errors[ $name ] = $a['msgEmail'];
			} elseif ( 'url' === $type && ! wp_http_validate_url( preg_match( '#^https?://#i', $val ) ? $val : 'https://' . $val ) ) {
				$errors[ $name ] = $a['msgInvalid'];
			} elseif ( 'number' === $type && ! is_numeric( $val ) ) {
				$errors[ $name ] = $a['msgInvalid'];
			} elseif ( 'tel' === $type && ! preg_match( '/^[0-9+()\-.\s\/]{5,30}$/', $val ) ) {
				$errors[ $name ] = $a['msgInvalid'];
			} elseif ( 'date' === $type && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $val ) ) {
				$errors[ $name ] = $a['msgInvalid'];
			}
		}
		// what the entry and the email show: options by their labels
		if ( is_array( $val ) ) {
			$shown = implode( ', ', array_map( function ( $v ) use ( $opts ) { return $opts[ $v ] ?? $v; }, $val ) );
		} elseif ( 'consent' === $type ) {
			$shown = $val ? 'Yes' : 'No';
		} else {
			$shown = in_array( $type, array( 'select', 'radio' ), true ) && '' !== $val ? ( $opts[ $val ] ?? $val ) : $val;
		}
		$values[] = array( 'name' => $name, 'label' => igf_label_text( $f ), 'type' => $type, 'value' => $shown, 'raw' => $val );
	}
	if ( $errors ) {
		return $fail( $a['msgInvalid'], $errors );
	}
	set_transient( $rate, $sent + 1, 10 * MINUTE_IN_SECONDS );

	// the placeholders
	$page = (int) ( $in['_igp'] ?? 0 );
	$vars = array(
		'form' => $a['formName'],
		'site' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		'page' => $page ? get_permalink( $page ) : home_url( '/' ),
		'date' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
	);
	$visitor = '';
	foreach ( $values as $v ) {
		$vars[ $v['name'] ] = $v['value'];
		if ( ! $visitor && 'email' === $v['type'] && is_email( $v['value'] ) ) {
			$visitor = $v['value'];
		}
	}
	if ( 'auto' !== $a['replyTo'] && isset( $vars[ $a['replyTo'] ] ) && is_email( $vars[ $a['replyTo'] ] ) ) {
		$visitor = $vars[ $a['replyTo'] ];
	}
	$table = igf_table( $values, $vars );

	// kept under Forms
	$entry = 0;
	if ( ! empty( $a['store'] ) ) {
		$summary = $visitor ?: ( $values[0]['value'] ?? '' );
		$entry   = wp_insert_post( array(
			'post_type'   => 'ig_form_entry',
			'post_status' => 'publish',
			'post_title'  => wp_strip_all_tags( $a['formName'] . ( $summary ? ' – ' . $summary : '' ) ),
		) );
		if ( $entry && ! is_wp_error( $entry ) ) {
			update_post_meta( $entry, '_igf_fields', $values );
			update_post_meta( $entry, '_igf_form', $a['formName'] );
			update_post_meta( $entry, '_igf_key', $key );
			update_post_meta( $entry, '_igf_page', $page );
			update_post_meta( $entry, '_igf_url', $vars['page'] );
			update_post_meta( $entry, '_igf_ip', igf_ip_short( $ip ) );
			update_post_meta( $entry, '_igf_email', $visitor );
		}
	}

	// sent: to the form's addresses (or the default in Forms → Settings, or the site's admin)
	$to = igf_emails( $a['to'] ) ?: igf_emails( $set['to'] ) ?: array( get_option( 'admin_email' ) );
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	foreach ( igf_emails( $a['cc'] ) as $e ) {
		$headers[] = 'Cc: ' . $e;
	}
	foreach ( igf_emails( $a['bcc'] ) as $e ) {
		$headers[] = 'Bcc: ' . $e;
	}
	if ( $visitor ) {
		$headers[] = 'Reply-To: ' . $visitor;
	}
	$from_name = trim( igf_fill( $a['fromName'], $vars ) ) ?: $vars['site'];
	$set_name  = function () use ( $from_name ) { return $from_name; };
	add_filter( 'wp_mail_from_name', $set_name );
	$subject = wp_strip_all_tags( igf_fill( $a['subject'], $vars ) );
	$body    = igf_mail_html( '<p style="margin:0 0 16px">' . esc_html( $a['formName'] ) . ' · ' . esc_html( $vars['date'] ) . '<br><a href="' . esc_url( $vars['page'] ) . '">' . esc_html( $vars['page'] ) . '</a></p>' . $table );
	$mailed  = wp_mail( $to, $subject, $body, $headers );
	// and a copy to the visitor, if the form asks for it
	if ( ! empty( $a['confirm'] ) && $visitor ) {
		$cbody = nl2br( esc_html( igf_fill( $a['confirmBody'], array_merge( $vars, array( 'all_fields' => '%%IGF_ALL%%' ) ) ) ) );
		$cbody = str_replace( '%%IGF_ALL%%', $table, $cbody );
		wp_mail( $visitor, wp_strip_all_tags( igf_fill( $a['confirmSubject'], $vars ) ), igf_mail_html( $cbody ), array( 'Content-Type: text/html; charset=UTF-8' ) );
	}
	remove_filter( 'wp_mail_from_name', $set_name );
	if ( $entry && ! is_wp_error( $entry ) ) {
		update_post_meta( $entry, '_igf_mailed', $mailed ? 1 : 0 );
	}
	// the visitor is told it went well once it is kept, even if the email could not go (it is under Forms)
	if ( ! $mailed && ! ( $entry && ! is_wp_error( $entry ) ) ) {
		return $fail( $generic );
	}
	$out = array( 'ok' => true, 'message' => $a['message'], 'errors' => (object) array() );
	if ( 'redirect' === $a['after'] && '' !== trim( $a['redirect'] ) ) {
		$url = trim( $a['redirect'] );
		$out['redirect'] = esc_url_raw( 0 === strpos( $url, '/' ) ? home_url( $url ) : $url );
	}
	return $out;
}

/** The fields as a table, for the email and the entry. */
function igf_table( $values, $vars = array() ) {
	$rows = '';
	foreach ( $values as $v ) {
		if ( 'hidden' === $v['type'] && '' === $v['value'] ) {
			continue;
		}
		$val   = '' === (string) $v['value'] ? '<span style="color:#888">—</span>' : nl2br( esc_html( $v['value'] ) );
		if ( 'email' === $v['type'] && is_email( $v['value'] ) ) {
			$val = '<a href="mailto:' . esc_attr( $v['value'] ) . '">' . esc_html( $v['value'] ) . '</a>';
		}
		$rows .= '<tr><th style="text-align:left;vertical-align:top;padding:10px 16px 10px 0;border-top:1px solid #e3e6ea;width:180px;font-weight:600">' . esc_html( $v['label'] ) . '</th>'
			. '<td style="vertical-align:top;padding:10px 0;border-top:1px solid #e3e6ea">' . $val . '</td></tr>';
	}
	return '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:100%;max-width:640px">' . $rows . '</table>';
}

function igf_mail_html( $inner ) {
	return '<!doctype html><html><body style="margin:0;padding:24px;background:#f4f6f8"><div style="max-width:680px;margin:0 auto;padding:28px 32px;background:#fff;font:15px/1.5 -apple-system,Segoe UI,Roboto,Arial,sans-serif;color:#1d2327">'
		. $inner . '</div></body></html>';
}
