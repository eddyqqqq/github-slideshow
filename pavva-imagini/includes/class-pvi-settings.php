<?php
/**
 * Pavva Imagini – setări
 *
 * @package Pavva_Imagini
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class PVI_Settings {

	const OPTION = 'pvi_settings';

	public static function defaults(): array {
		return array(
			'webp'    => 1,   /* creează variante WebP */
			'avif'    => 1,   /* creează variante AVIF (dacă serverul știe) */
			'q_webp'  => 82,  /* calitate WebP (0–100) */
			'q_avif'  => 60,  /* calitate AVIF (0–100; 55–65 arată ca WebP 80–85, dar e mai mic) */
			'auto'    => 1,   /* imaginile noi se convertesc singure */
			'deliver' => 1,   /* livrare automată prin uploads/.htaccess */
		);
	}

	public static function get(): array {
		$o = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $o ) ? $o : array() );
	}

	public static function sanitize( $in ): array {
		$in  = is_array( $in ) ? $in : array();
		$def = self::defaults();
		return array(
			'webp'    => empty( $in['webp'] ) ? 0 : 1,
			'avif'    => empty( $in['avif'] ) ? 0 : 1,
			'q_webp'  => isset( $in['q_webp'] ) ? max( 30, min( 100, (int) $in['q_webp'] ) ) : $def['q_webp'],
			'q_avif'  => isset( $in['q_avif'] ) ? max( 20, min( 100, (int) $in['q_avif'] ) ) : $def['q_avif'],
			'auto'    => empty( $in['auto'] ) ? 0 : 1,
			'deliver' => empty( $in['deliver'] ) ? 0 : 1,
		);
	}

	/* formatele care chiar se pot crea pe acest server */
	public static function formats(): array {
		$s   = self::get();
		$out = array();
		if ( ! empty( $s['avif'] ) && PVI_Converter::engine( 'avif' ) ) $out[] = 'avif';
		if ( ! empty( $s['webp'] ) && PVI_Converter::engine( 'webp' ) ) $out[] = 'webp';
		return $out;
	}
}
