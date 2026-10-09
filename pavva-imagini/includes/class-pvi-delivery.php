<?php
/**
 * Pavva Imagini – livrarea automată
 *
 * Reguli în wp-content/uploads/.htaccess (LiteSpeed și Apache): când browserul cere poza.png
 * și spune că știe AVIF / WebP (antetul Accept), serverul trimite poza.png.avif / poza.png.webp,
 * la aceeași adresă. „Vary: Accept” le spune cache-urilor (browser, LiteSpeed, CDN) să țină
 * câte o variantă pentru fiecare tip de browser. Fără variantă → se trimite originalul.
 *
 * @package Pavva_Imagini
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class PVI_Delivery {

	const MARKER = 'Pavva Imagini';

	public static function htaccess_path(): string {
		$u = wp_get_upload_dir();
		return trailingslashit( $u['basedir'] ) . '.htaccess';
	}

	public static function rules(): array {
		$u    = wp_get_upload_dir();
		$path = wp_parse_url( $u['baseurl'], PHP_URL_PATH );
		$path = '/' . trim( (string) $path, '/' ) . '/';
		$r    = array(
			'<IfModule mod_mime.c>',
			'  AddType image/avif .avif',
			'  AddType image/webp .webp',
			'</IfModule>',
			'<IfModule mod_rewrite.c>',
			'  RewriteEngine On',
			'  RewriteBase ' . $path,
		);
		foreach ( array( 'avif', 'webp' ) as $fmt ) {
			$r[] = '  RewriteCond %{HTTP_ACCEPT} image/' . $fmt;
			$r[] = '  RewriteCond %{REQUEST_FILENAME}.' . $fmt . ' -f';
			$r[] = '  RewriteRule ^(.+)\.(jpe?g|png)$ $1.$2.' . $fmt . ' [NC,L]';
		}
		$r[] = '</IfModule>';
		$r[] = '<IfModule mod_headers.c>';
		$r[] = '  <FilesMatch "\.(jpe?g|png)(\.(avif|webp))?$">';
		$r[] = '    Header append Vary Accept';
		$r[] = '  </FilesMatch>';
		$r[] = '</IfModule>';
		return $r;
	}

	public static function write_rules(): bool {
		$s = PVI_Settings::get();
		if ( empty( $s['deliver'] ) ) return self::remove_rules();
		return self::put( self::rules() );
	}

	/* scoate tot blocul (inclusiv comentariile BEGIN / END) */
	public static function remove_rules(): bool {
		$file = self::htaccess_path();
		if ( ! file_exists( $file ) ) return true;
		$txt = (string) file_get_contents( $file ); // phpcs:ignore
		$new = preg_replace( '/\n?# BEGIN ' . preg_quote( self::MARKER, '/' ) . '.*?# END ' . preg_quote( self::MARKER, '/' ) . '\n?/s', "\n", $txt );
		if ( null === $new || $new === $txt ) return true;
		if ( ! wp_is_writable( $file ) ) return false;
		$new = trim( $new );
		if ( '' === $new ) return @unlink( $file ); // phpcs:ignore
		return false !== file_put_contents( $file, $new . "\n" ); // phpcs:ignore
	}

	private static function put( array $lines ): bool {
		if ( ! function_exists( 'insert_with_markers' ) ) require_once ABSPATH . 'wp-admin/includes/misc.php';
		$file = self::htaccess_path();
		if ( ! file_exists( $file ) && ! $lines ) return true;
		if ( file_exists( $file ) ? ! wp_is_writable( $file ) : ! wp_is_writable( dirname( $file ) ) ) return false;
		return (bool) insert_with_markers( $file, self::MARKER, $lines );
	}

	public static function rules_present(): bool {
		$file = self::htaccess_path();
		return file_exists( $file ) && (bool) preg_match( '/# BEGIN ' . preg_quote( self::MARKER, '/' ) . '.*?RewriteRule.*?# END ' . preg_quote( self::MARKER, '/' ) . '/s', (string) file_get_contents( $file ) ); // phpcs:ignore
	}

	/**
	 * Test real: cere o imagine convertită cu antetul unui browser modern și se uită ce tip primește.
	 * Întoarce [ 'ok' => bool, 'msg' => text ].
	 */
	public static function test(): array {
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s ORDER BY post_id DESC LIMIT 30", PVI_Converter::META ) ); // phpcs:ignore
		$upl = wp_get_upload_dir();
		foreach ( $ids as $id ) {
			$state = get_post_meta( (int) $id, PVI_Converter::META, true );
			if ( ! is_array( $state ) ) continue;
			foreach ( $state as $rel => $row ) {
				foreach ( array( 'avif', 'webp' ) as $fmt ) {
					if ( empty( $row[ $fmt ] ) || ! file_exists( trailingslashit( $upl['basedir'] ) . $rel . '.' . $fmt ) ) continue;
					$url = trailingslashit( $upl['baseurl'] ) . str_replace( '%2F', '/', rawurlencode( $rel ) );
					$res = wp_remote_get( add_query_arg( 'pvi-test', time(), $url ), array(
						'timeout'   => 15,
						'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
						'headers'   => array( 'Accept' => 'image/' . $fmt . ',image/*,*/*;q=0.8' ),
					) );
					if ( is_wp_error( $res ) ) {
						return array( 'ok' => false, 'msg' => 'Serverul nu s-a putut testa singur (' . $res->get_error_message() . '). Verifică din browser: deschide o imagine .png din site și uită-te la „Type” în Developer Tools → Network.' );
					}
					$type = (string) wp_remote_retrieve_header( $res, 'content-type' );
					$len  = strlen( (string) wp_remote_retrieve_body( $res ) );
					if ( false !== stripos( $type, 'image/' . $fmt ) ) {
						return array( 'ok' => true, 'msg' => sprintf( 'Funcționează: %s a venit ca %s (%s în loc de %s).', basename( $rel ), strtoupper( $fmt ), size_format( $len, 1 ), size_format( (int) $row['o'], 1 ) ) );
					}
					return array( 'ok' => false, 'msg' => sprintf( '%s a venit ca „%s” în loc de %s. Regulile din uploads/.htaccess nu sunt aplicate (server Nginx sau reguli blocate) – vezi mai jos.', basename( $rel ), $type ?: 'necunoscut', strtoupper( $fmt ) ) );
				}
			}
		}
		return array( 'ok' => false, 'msg' => 'Nu există încă imagini convertite. Pornește întâi conversia.' );
	}
}
