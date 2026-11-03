<?php
/**
 * The form's definition, read from the page itself: the server never trusts what the browser says a form is. A form
 * is found by its key in the page's content (or in a synced pattern, a template or a template part), its fields and
 * settings are the blocks' attributes.
 */

defined( 'ABSPATH' ) || exit;

/** The field types and what each one is. */
function igf_types() {
	return array(
		'text'     => 'Text',
		'email'    => 'Email',
		'tel'      => 'Phone',
		'textarea' => 'Message (several lines)',
		'select'   => 'Dropdown',
		'radio'    => 'Radio buttons',
		'checkbox' => 'Checkboxes',
		'consent'  => 'Consent (a single checkbox)',
		'number'   => 'Number',
		'date'     => 'Date',
		'url'      => 'Website (URL)',
		'hidden'   => 'Hidden',
	);
}

/** The attributes a block has by default (from its registration), merged under the ones it was saved with. */
function igf_attrs( $block ) {
	$type = WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
	$out  = array();
	if ( $type ) {
		foreach ( (array) $type->attributes as $k => $def ) {
			if ( array_key_exists( 'default', $def ) ) {
				$out[ $k ] = $def['default'];
			}
		}
	}
	return array_merge( $out, (array) ( $block['attrs'] ?? array() ) );
}

/** A field's name in the form data: the one set on it, or one made from its label; unique within its form. */
function igf_field_name( $a, &$used ) {
	$base = sanitize_key( str_replace( '-', '_', (string) ( $a['name'] ?? '' ) ) );
	if ( '' === $base ) {
		$base = str_replace( '-', '_', sanitize_title( wp_strip_all_tags( (string) ( $a['label'] ?? '' ) ) ) );
		$base = substr( preg_replace( '/[^a-z0-9_]/', '', $base ), 0, 40 );
	}
	if ( '' === $base ) {
		$base = 'field';
	}
	$name = $base;
	for ( $i = 2; isset( $used[ $name ] ); $i++ ) {
		$name = $base . '_' . $i;
	}
	$used[ $name ] = true;
	return $name;
}

/** The options of a dropdown, radio or checkbox field: one per line ("value|Label" keeps a value apart). */
function igf_options( $a ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) ( $a['options'] ?? '' ) ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts                   = array_map( 'trim', explode( '|', $line, 2 ) );
		$out[ $parts[0] ] = $parts[1] ?? $parts[0];
	}
	return $out;
}

/** The form block with this key in a list of blocks (synced patterns followed). */
function igf_find_in_blocks( $blocks, $key, $depth = 0 ) {
	if ( $depth > 8 ) {
		return null;
	}
	foreach ( $blocks as $b ) {
		if ( 'imagient/form' === ( $b['blockName'] ?? '' ) && ( $b['attrs']['formKey'] ?? '' ) === $key ) {
			return $b;
		}
		if ( 'core/block' === ( $b['blockName'] ?? '' ) && ! empty( $b['attrs']['ref'] ) ) {
			$ref = get_post( (int) $b['attrs']['ref'] );
			if ( $ref && 'wp_block' === $ref->post_type ) {
				$found = igf_find_in_blocks( parse_blocks( $ref->post_content ), $key, $depth + 1 );
				if ( $found ) {
					return $found;
				}
			}
		}
		if ( ! empty( $b['innerBlocks'] ) ) {
			$found = igf_find_in_blocks( $b['innerBlocks'], $key, $depth + 1 );
			if ( $found ) {
				return $found;
			}
		}
	}
	return null;
}

/** The form with this key: on the page it was sent from, else in the site's templates and template parts. */
function igf_find_form( $post_id, $key ) {
	if ( ! preg_match( '/^[a-z0-9]{4,32}$/', (string) $key ) ) {
		return null;
	}
	$post = $post_id ? get_post( $post_id ) : null;
	if ( $post && 'publish' === $post->post_status && empty( $post->post_password ) ) {
		$found = igf_find_in_blocks( parse_blocks( $post->post_content ), $key );
		if ( $found ) {
			return $found;
		}
	}
	if ( function_exists( 'get_block_templates' ) ) {
		foreach ( array( 'wp_template_part', 'wp_template' ) as $kind ) {
			foreach ( get_block_templates( array(), $kind ) as $tpl ) {
				$found = igf_find_in_blocks( parse_blocks( (string) $tpl->content ), $key );
				if ( $found ) {
					return $found;
				}
			}
		}
	}
	return null;
}

/** The form's fields, in order: each with its attributes and its name in the form data. */
function igf_form_fields( $form ) {
	$fields = array();
	$used   = array();
	$walk   = function ( $blocks ) use ( &$walk, &$fields, &$used ) {
		foreach ( $blocks as $b ) {
			if ( 'imagient/field' === ( $b['blockName'] ?? '' ) ) {
				$a         = igf_attrs( $b );
				$a['key']  = igf_field_name( $a, $used );
				$fields[]  = $a;
			} elseif ( ! empty( $b['innerBlocks'] ) ) {
				$walk( $b['innerBlocks'] );
			}
		}
	};
	$walk( $form['innerBlocks'] ?? array() );
	return $fields;
}

/** A label as plain text (labels can hold a link or bold text). */
function igf_label_text( $a ) {
	$t = trim( wp_strip_all_tags( (string) ( $a['label'] ?? '' ) ) );
	return '' !== $t ? $t : ( $a['key'] ?? 'Field' );
}
