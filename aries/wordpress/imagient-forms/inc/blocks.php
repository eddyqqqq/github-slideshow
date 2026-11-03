<?php
/**
 * The blocks: Form (its settings in the sidebar, its fields inside it), Form field (one block, a type for each kind of
 * field; the inserter lists each type) and Submit button. All render on the server, so the markup is always current.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	wp_register_script( 'imagient-forms-editor', IGF_URL . 'assets/editor.js', array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n' ), IGF_VERSION, true );
	wp_register_script( 'imagient-forms', IGF_URL . 'assets/forms.js', array(), IGF_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_register_style( 'imagient-forms', IGF_URL . 'assets/forms.css', array(), IGF_VERSION );
	wp_localize_script( 'imagient-forms-editor', 'IGF', array( 'types' => igf_types(), 'admin' => get_option( 'admin_email' ) ) );

	$common = array(
		'api_version'   => 3,
		'category'      => 'widgets',
		'editor_script' => 'imagient-forms-editor',
		'style'         => 'imagient-forms',
	);

	register_block_type( 'imagient/form', array_merge( $common, array(
		'title'           => 'Form',
		'description'     => 'A form: put fields and a submit button inside it; who gets the messages, the email\'s subject and what happens after sending are in its sidebar.',
		'supports'        => array( 'anchor' => true, 'className' => true, 'html' => false, 'align' => array( 'wide', 'full' ) ),
		'attributes'      => array(
			'formKey'        => array( 'type' => 'string', 'default' => '' ),
			'formName'       => array( 'type' => 'string', 'default' => 'Contact form' ),
			'to'             => array( 'type' => 'string', 'default' => '' ),
			'cc'             => array( 'type' => 'string', 'default' => '' ),
			'bcc'            => array( 'type' => 'string', 'default' => '' ),
			'subject'        => array( 'type' => 'string', 'default' => 'New message from {form} – {site}' ),
			'fromName'       => array( 'type' => 'string', 'default' => '' ),
			'replyTo'        => array( 'type' => 'string', 'default' => 'auto' ),
			'after'          => array( 'type' => 'string', 'default' => 'message' ),
			'message'        => array( 'type' => 'string', 'default' => 'Thank you! Your message has been sent successfully.' ),
			'redirect'       => array( 'type' => 'string', 'default' => '' ),
			'msgError'       => array( 'type' => 'string', 'default' => 'Something went wrong. Please try again in a moment.' ),
			'msgRequired'    => array( 'type' => 'string', 'default' => 'This field is required.' ),
			'msgInvalid'     => array( 'type' => 'string', 'default' => 'Please check this field.' ),
			'msgEmail'       => array( 'type' => 'string', 'default' => 'Please enter a valid email address.' ),
			'confirm'        => array( 'type' => 'boolean', 'default' => false ),
			'confirmSubject' => array( 'type' => 'string', 'default' => 'We received your message – {site}' ),
			'confirmBody'    => array( 'type' => 'string', 'default' => "Thank you for contacting us. We received your message and will get back to you shortly.\n\n{all_fields}" ),
			'store'          => array( 'type' => 'boolean', 'default' => true ),
		),
		'render_callback' => 'igf_render_form',
	) ) );

	register_block_type( 'imagient/field', array_merge( $common, array(
		'title'           => 'Form field',
		'description'     => 'One field of a form: text, email, phone, message, dropdown, checkboxes, radio buttons, consent, number, date, website or hidden.',
		'ancestor'        => array( 'imagient/form' ),
		'supports'        => array( 'className' => true, 'html' => false, 'reusable' => false ),
		'attributes'      => array(
			'type'         => array( 'type' => 'string', 'default' => 'text' ),
			'label'        => array( 'type' => 'string', 'default' => '' ),
			'name'         => array( 'type' => 'string', 'default' => '' ),
			'placeholder'  => array( 'type' => 'string', 'default' => '' ),
			'required'     => array( 'type' => 'boolean', 'default' => false ),
			'width'        => array( 'type' => 'string', 'default' => '100' ),
			'options'      => array( 'type' => 'string', 'default' => '' ),
			'defaultValue' => array( 'type' => 'string', 'default' => '' ),
			'help'         => array( 'type' => 'string', 'default' => '' ),
			'rows'         => array( 'type' => 'number', 'default' => 5 ),
			'hideLabel'    => array( 'type' => 'boolean', 'default' => false ),
			'autocomplete' => array( 'type' => 'string', 'default' => '' ),
		),
		'render_callback' => 'igf_render_field',
	) ) );

	register_block_type( 'imagient/form-submit', array_merge( $common, array(
		'title'           => 'Submit button',
		'description'     => 'The button that sends the form.',
		'ancestor'        => array( 'imagient/form' ),
		'supports'        => array( 'className' => true, 'html' => false, 'reusable' => false, 'multiple' => false ),
		'attributes'      => array(
			'text'    => array( 'type' => 'string', 'default' => 'Send message' ),
			'sending' => array( 'type' => 'string', 'default' => 'Sending…' ),
			'width'   => array( 'type' => 'string', 'default' => 'auto' ),
		),
		'render_callback' => 'igf_render_submit',
	) ) );
} );

// the field names of the form being rendered (reset when a form starts, as its fields render before it)
$GLOBALS['igf_used'] = array();
add_filter( 'render_block_data', function ( $block ) {
	if ( 'imagient/form' === ( $block['blockName'] ?? '' ) ) {
		$GLOBALS['igf_used'] = array();
	}
	return $block;
} );

/** A signed time: the server can tell how long the visitor took and that the form came from this site. */
function igf_token( $key ) {
	$t = time();
	return $t . '.' . substr( hash_hmac( 'sha256', $t . '|' . $key, wp_salt( 'nonce' ) ), 0, 20 );
}

function igf_settings() {
	return wp_parse_args( (array) get_option( 'igf_settings', array() ), array( 'to' => '', 'ts_site' => '', 'ts_secret' => '', 'keep_days' => 0 ) );
}

function igf_render_form( $a, $content ) {
	wp_enqueue_script( 'imagient-forms' );
	$key  = preg_match( '/^[a-z0-9]{4,32}$/', (string) $a['formKey'] ) ? $a['formKey'] : '';
	$post = get_queried_object_id() ?: get_the_ID();
	$id   = ! empty( $a['anchor'] ) ? $a['anchor'] : 'igf-' . ( $key ?: wp_unique_id() );
	$set  = igf_settings();
	// the Submit button block, or one by default when it was left out
	if ( false === strpos( $content, 'ig-form__submit' ) ) {
		$content .= igf_render_submit( array( 'text' => 'Send message', 'sending' => 'Sending…', 'width' => 'auto' ) );
	}
	// back from a submission without the script (the browser posted the form itself)
	$notice = '';
	if ( $key && isset( $_GET['igf_sent'] ) && $_GET['igf_sent'] === $key ) { // phpcs:ignore WordPress.Security.NonceVerification
		$notice = '<div class="ig-form__status is-ok" role="status">' . esc_html( $a['message'] ) . '</div>';
	} elseif ( $key && isset( $_GET['igf_error'] ) && $_GET['igf_error'] === $key ) { // phpcs:ignore WordPress.Security.NonceVerification
		$notice = '<div class="ig-form__status is-error" role="alert">' . esc_html( $a['msgError'] ) . '</div>';
	}
	$turnstile = '';
	if ( $set['ts_site'] && $set['ts_secret'] ) {
		wp_enqueue_script( 'igf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, array( 'strategy' => 'defer' ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		$turnstile = '<div class="ig-form__captcha cf-turnstile" data-sitekey="' . esc_attr( $set['ts_site'] ) . '"></div>';
	}
	$wrap = get_block_wrapper_attributes( array(
		'class'             => 'ig-form',
		'id'                => $id,
		'action'            => esc_url( admin_url( 'admin-post.php' ) ),
		'method'            => 'post',
		'novalidate'        => 'novalidate',
		'data-igf'          => esc_url( rest_url( 'imagient-forms/v1/submit' ) ),
		'data-msg-required' => $a['msgRequired'],
		'data-msg-email'    => $a['msgEmail'],
		'data-msg-invalid'  => $a['msgInvalid'],
		'data-msg-error'    => $a['msgError'],
	) );
	return '<form ' . $wrap . '>'
		. ( $key ? '' : '<p class="ig-form__status is-error">This form has no key yet: open the page in the editor and save it once.</p>' )
		. $notice
		. '<input type="hidden" name="action" value="imagient_form">'
		. '<input type="hidden" name="_igf" value="' . esc_attr( $key ) . '">'
		. '<input type="hidden" name="_igp" value="' . esc_attr( (int) $post ) . '">'
		. '<input type="hidden" name="_igt" value="' . esc_attr( igf_token( $key ) ) . '">'
		// a field people never see: bots that fill in everything give themselves away
		. '<div class="ig-form__hp" aria-hidden="true"><label>Leave this empty <input type="text" name="ig_website" value="" tabindex="-1" autocomplete="off"></label></div>'
		. '<div class="ig-form__fields">' . $content . '</div>'
		. $turnstile
		. '<div class="ig-form__status" role="status" aria-live="polite" hidden></div>'
		. '</form>';
}

function igf_render_field( $a ) {
	$types = igf_types();
	$type  = isset( $types[ $a['type'] ] ) ? $a['type'] : 'text';
	$name  = igf_field_name( $a, $GLOBALS['igf_used'] );
	$id    = wp_unique_id( 'igf-' . $name . '-' );
	$req   = ! empty( $a['required'] );
	$label = wp_kses( (string) $a['label'], array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ), 'strong' => array(), 'em' => array(), 'br' => array() ) );
	$star  = $req ? '<span class="ig-field__req" aria-hidden="true">*</span>' : '';
	$ph    = '' !== $a['placeholder'] ? ' placeholder="' . esc_attr( $a['placeholder'] ) . '"' : '';
	$reqa  = $req ? ' required aria-required="true"' : '';
	$help  = '' !== trim( (string) $a['help'] ) ? '<p class="ig-field__help" id="' . esc_attr( $id ) . '-help">' . esc_html( $a['help'] ) . '</p>' : '';
	$desc  = $help ? ' aria-describedby="' . esc_attr( $id ) . '-help"' : '';
	$auto  = array( 'email' => 'email', 'tel' => 'tel', 'url' => 'url' );
	$ac    = $a['autocomplete'] ?: ( $auto[ $type ] ?? '' );
	$ac    = $ac ? ' autocomplete="' . esc_attr( $ac ) . '"' : '';
	$width = in_array( (string) $a['width'], array( '100', '75', '66', '50', '33', '25' ), true ) ? $a['width'] : '100';
	$class = 'ig-field ig-field--' . $type . ' is-w-' . $width . ( $req ? ' is-required' : '' ) . ( $a['hideLabel'] ? ' has-hidden-label' : '' );
	$nm    = 'ig[' . $name . ']';

	if ( 'hidden' === $type ) {
		return '<input type="hidden" name="' . esc_attr( $nm ) . '" value="' . esc_attr( $a['defaultValue'] ) . '">';
	}
	$lab = '<label class="ig-field__label" for="' . esc_attr( $id ) . '">' . $label . $star . '</label>';
	switch ( $type ) {
		case 'textarea':
			$ctrl = '<textarea class="ig-field__input" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nm ) . '" rows="' . max( 2, min( 20, (int) $a['rows'] ) ) . '" maxlength="5000"' . $ph . $reqa . $desc . '>' . esc_textarea( $a['defaultValue'] ) . '</textarea>';
			break;
		case 'select':
			$ctrl = '<select class="ig-field__input" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nm ) . '"' . $reqa . $desc . '>';
			$ctrl .= '<option value="">' . esc_html( '' !== $a['placeholder'] ? $a['placeholder'] : '—' ) . '</option>';
			foreach ( igf_options( $a ) as $v => $l ) {
				$ctrl .= '<option value="' . esc_attr( $v ) . '"' . selected( $v, $a['defaultValue'], false ) . '>' . esc_html( $l ) . '</option>';
			}
			$ctrl .= '</select>';
			break;
		case 'radio':
		case 'checkbox':
			$multi = 'checkbox' === $type;
			$ctrl  = '<div class="ig-field__choices" role="' . ( $multi ? 'group' : 'radiogroup' ) . '" aria-labelledby="' . esc_attr( $id ) . '-l"' . $desc . '>';
			$defs  = array_map( 'trim', explode( ',', (string) $a['defaultValue'] ) );
			$i     = 0;
			foreach ( igf_options( $a ) as $v => $l ) {
				$cid   = $id . '-' . ( $i++ );
				$ctrl .= '<label class="ig-field__choice" for="' . esc_attr( $cid ) . '"><input type="' . $type . '" id="' . esc_attr( $cid ) . '" name="' . esc_attr( $nm . ( $multi ? '[]' : '' ) ) . '" value="' . esc_attr( $v ) . '"' . checked( in_array( $v, $defs, true ), true, false ) . ( $req && ! $multi ? ' required' : '' ) . '><span>' . esc_html( $l ) . '</span></label>';
			}
			$ctrl .= '</div>';
			$lab   = '<span class="ig-field__label" id="' . esc_attr( $id ) . '-l">' . $label . $star . '</span>';
			break;
		case 'consent':
			$lab  = '';
			$ctrl = '<label class="ig-field__choice ig-field__consent" for="' . esc_attr( $id ) . '"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nm ) . '" value="yes"' . $reqa . $desc . '><span>' . $label . $star . '</span></label>';
			break;
		default:
			$html = array( 'text' => 'text', 'email' => 'email', 'tel' => 'tel', 'number' => 'number', 'date' => 'date', 'url' => 'url' );
			$ctrl = '<input class="ig-field__input" type="' . $html[ $type ] . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nm ) . '" value="' . esc_attr( $a['defaultValue'] ) . '" maxlength="500"' . $ph . $reqa . $ac . $desc . '>';
	}
	$wrap = get_block_wrapper_attributes( array( 'class' => $class, 'data-name' => $name, 'data-type' => $type ) );
	return '<div ' . $wrap . '>' . $lab . $ctrl . $help . '<p class="ig-field__error" hidden></p></div>';
}

function igf_render_submit( $a, $content = '', $block = null ) {
	// as a block, with its wrapper's classes and styles; the form's own default button (no block) is plain
	$cls  = 'ig-form__submit' . ( 'full' === ( $a['width'] ?? '' ) ? ' is-full' : '' );
	$wrap = $block ? get_block_wrapper_attributes( array( 'class' => $cls ) ) : 'class="' . esc_attr( $cls ) . '"';
	return '<div ' . $wrap . '><button type="submit" class="ig-form__button" data-sending="' . esc_attr( $a['sending'] ) . '"><span>' . esc_html( $a['text'] ) . '</span></button></div>';
}
