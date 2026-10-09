<?php
/**
 * Pavva Imagini – conversia în WebP / AVIF
 *
 * Variantele stau lângă original, cu extensia adăugată: poza.png → poza.png.webp / poza.png.avif.
 * Originalul nu se modifică niciodată. Dacă varianta iese mai mare decât originalul, nu se păstrează.
 * Starea fiecărei imagini e în meta „_pvi” (mărimi, ce s-a sărit), pentru statistici și ca să nu se refacă degeaba.
 *
 * @package Pavva_Imagini
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class PVI_Converter {

	const META  = '_pvi';
	const EXTS  = array( 'jpg', 'jpeg', 'png' );
	const MIMES = array( 'image/jpeg', 'image/png' );
	const CRON  = 'pvi_convert_one';

	private static $support = null;

	public static function init() {
		$s = PVI_Settings::get();
		if ( ! empty( $s['auto'] ) ) {
			/* imagini noi / regenerate / editate: conversia rulează în fundal (WP-Cron), nu încetinește încărcarea */
			add_filter( 'wp_generate_attachment_metadata', array( __CLASS__, 'queue' ), 99, 2 );
			add_filter( 'wp_update_attachment_metadata', array( __CLASS__, 'queue' ), 99, 2 );
		}
		add_action( self::CRON, array( __CLASS__, 'cron_convert' ) );
		add_action( 'delete_attachment', array( __CLASS__, 'delete_variants' ) );
	}

	/* ================= ce știe serverul ================= */

	/**
	 * Ce poate serverul să CREEZE (nu doar să citească): fiecare motor face o conversie reală de probă
	 * pe o imagine mică. Unele servere au Imagick care „cunoaște” AVIF, dar doar îl citesc.
	 * Rezultatul se ține minte 12 ore (sau până la Media → Pavva – Imagini → „Verifică din nou”).
	 */
	public static function support( bool $fresh = false ): array {
		if ( null !== self::$support && ! $fresh ) return self::$support;
		$cached = $fresh ? false : get_transient( 'pvi_support' );
		if ( is_array( $cached ) ) return self::$support = $cached;
		$s = array(
			'imagick' => array( 'webp' => self::probe_imagick( 'webp' ), 'avif' => self::probe_imagick( 'avif' ) ),
			'gd'      => array( 'webp' => self::probe_gd( 'webp' ), 'avif' => self::probe_gd( 'avif' ) ),
		);
		set_transient( 'pvi_support', $s, 12 * HOUR_IN_SECONDS );
		return self::$support = $s;
	}

	private static function probe_imagick( string $fmt ): bool {
		if ( ! class_exists( 'Imagick' ) ) return false;
		try {
			if ( ! \Imagick::queryFormats( strtoupper( $fmt ) ) ) return false;
			$im = new \Imagick();
			$im->newImage( 16, 16, new \ImagickPixel( '#AB5525' ), 'png' );
			$im->setImageFormat( strtoupper( $fmt ) );
			$blob = $im->getImageBlob();
			$im->destroy();
			return strlen( $blob ) > 12;
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	private static function probe_gd( string $fmt ): bool {
		$fn = 'image' . $fmt;
		if ( ! function_exists( $fn ) || ! function_exists( 'imagecreatetruecolor' ) ) return false;
		try {
			$im = imagecreatetruecolor( 16, 16 );
			ob_start();
			$ok  = @$fn( $im, null ); // phpcs:ignore
			$out = ob_get_clean();
			imagedestroy( $im );
			return $ok && strlen( (string) $out ) > 12;
		} catch ( \Throwable $e ) {
			if ( ob_get_level() ) ob_end_clean();
			return false;
		}
	}

	/* motorul folosit pentru un format: Imagick (mai bun), altfel GD */
	public static function engine( string $fmt ) {
		$s = self::support();
		if ( ! empty( $s['imagick'][ $fmt ] ) ) return 'imagick';
		if ( ! empty( $s['gd'][ $fmt ] ) ) return 'gd';
		return null;
	}

	/* ================= un fișier ================= */

	/**
	 * Creează varianta $fmt pentru $src. Întoarce mărimea variantei (octeți), 0 dacă s-a sărit
	 * (ieșea mai mare decât originalul) sau false la eroare.
	 */
	public static function convert_file( string $src, string $fmt, int $quality ) {
		$engine = self::engine( $fmt );
		if ( ! $engine || ! is_readable( $src ) ) return false;
		$out = $src . '.' . $fmt;
		$tmp = $out . '.tmp';
		if ( function_exists( 'wp_raise_memory_limit' ) ) wp_raise_memory_limit( 'image' );
		@set_time_limit( 120 ); // phpcs:ignore

		$ok = 'imagick' === $engine ? self::imagick( $src, $tmp, $fmt, $quality ) : self::gd( $src, $tmp, $fmt, $quality );
		if ( ! $ok || ! file_exists( $tmp ) || ! filesize( $tmp ) ) {
			@unlink( $tmp ); // phpcs:ignore
			return false;
		}
		clearstatcache();
		$size = filesize( $tmp );
		if ( $size >= filesize( $src ) ) {
			/* nu câștigăm nimic: rămâne originalul */
			@unlink( $tmp ); // phpcs:ignore
			@unlink( $out ); // phpcs:ignore
			return 0;
		}
		if ( ! @rename( $tmp, $out ) ) { // phpcs:ignore
			@unlink( $tmp ); // phpcs:ignore
			return false;
		}
		/* aceleași permisiuni ca originalul */
		$perms = @fileperms( $src ); // phpcs:ignore
		if ( $perms ) @chmod( $out, $perms & 0666 ); // phpcs:ignore
		return $size;
	}

	private static function imagick( string $src, string $tmp, string $fmt, int $q ): bool {
		try {
			$im = new \Imagick( $src );
			if ( $im->getNumberImages() > 1 ) $im->setIteratorIndex( 0 );
			/* păstrăm profilul de culoare, scoatem restul (EXIF etc.) */
			$icc = null;
			try { $p = $im->getImageProfiles( 'icc', true ); $icc = $p['icc'] ?? null; } catch ( \Throwable $e ) {} // phpcs:ignore
			$im->stripImage();
			if ( $icc ) $im->profileImage( 'icc', $icc );
			$im->setImageFormat( strtoupper( $fmt ) );
			$im->setImageCompressionQuality( $q );
			if ( 'webp' === $fmt ) {
				$im->setOption( 'webp:method', '6' );
				$im->setOption( 'webp:alpha-quality', '100' );
			} else {
				$im->setOption( 'heic:speed', '6' );
			}
			$ok = $im->writeImage( $fmt . ':' . $tmp );
			$im->clear();
			$im->destroy();
			return (bool) $ok;
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	private static function gd( string $src, string $tmp, string $fmt, int $q ): bool {
		$ext = strtolower( pathinfo( $src, PATHINFO_EXTENSION ) );
		$im  = 'png' === $ext ? @imagecreatefrompng( $src ) : @imagecreatefromjpeg( $src ); // phpcs:ignore
		if ( ! $im ) return false;
		if ( ! imageistruecolor( $im ) ) imagepalettetotruecolor( $im );
		imagealphablending( $im, false );
		imagesavealpha( $im, true );
		$ok = 'webp' === $fmt ? @imagewebp( $im, $tmp, $q ) : @imageavif( $im, $tmp, $q, 6 ); // phpcs:ignore
		imagedestroy( $im );
		return (bool) $ok;
	}

	/* ================= o imagine din Media Library (cu toate mărimile ei) ================= */

	/* toate fișierele unei imagini: originalul, varianta „scaled” și mărimile generate */
	public static function files( int $id ): array {
		$file = get_attached_file( $id );
		if ( ! $file ) return array();
		$dir   = dirname( $file );
		$meta  = wp_get_attachment_metadata( $id );
		$files = array( $file );
		if ( is_array( $meta ) ) {
			if ( ! empty( $meta['original_image'] ) ) $files[] = $dir . '/' . $meta['original_image'];
			foreach ( (array) ( $meta['sizes'] ?? array() ) as $size ) {
				if ( ! empty( $size['file'] ) ) $files[] = $dir . '/' . $size['file'];
			}
		}
		return array_values( array_unique( array_filter( $files, function ( $f ) {
			return in_array( strtolower( pathinfo( $f, PATHINFO_EXTENSION ) ), self::EXTS, true ) && file_exists( $f );
		} ) ) );
	}

	/**
	 * Convertește toate fișierele unei imagini. $force = refă și ce există deja (ex. după schimbarea calității).
	 * Întoarce statistica: orig / webp / avif (octeți, doar pentru fișierele cu variantă), fișiere, erori.
	 */
	public static function convert_attachment( int $id, bool $force = false, float $budget = 0 ): array {
		$stats = array( 'orig' => 0, 'webp' => 0, 'best' => 0, 'files' => 0, 'errors' => 0, 'more' => false );
		$t0    = microtime( true );
		if ( ! in_array( get_post_mime_type( $id ), self::MIMES, true ) ) return $stats;

		$s       = PVI_Settings::get();
		$formats = PVI_Settings::formats();
		$state   = get_post_meta( $id, self::META, true );
		$state   = is_array( $state ) ? $state : array();
		$upl     = wp_get_upload_dir();
		$base    = trailingslashit( $upl['basedir'] );
		$new     = array();

		foreach ( self::files( $id ) as $f ) {
			$rel   = 0 === strpos( $f, $base ) ? substr( $f, strlen( $base ) ) : basename( $f );
			$mtime = (int) filemtime( $f );
			/* starea veche contează doar dacă originalul nu s-a schimbat și nu refacem forțat */
			$row   = ! $force && isset( $state[ $rel ] ) && is_array( $state[ $rel ] ) && (int) ( $state[ $rel ]['m'] ?? 0 ) === $mtime ? $state[ $rel ] : array( 'm' => $mtime );
			$row['o'] = (int) filesize( $f );
			foreach ( $formats as $fmt ) {
				/* limita de timp a cererii (hosting): ne oprim și continuăm la cererea următoare */
				if ( $budget && microtime( true ) - $t0 > $budget ) {
					$stats['more'] = true;
					break;
				}
				$have = file_exists( $f . '.' . $fmt );
				/* deja făcut (sau sărit pentru că ieșea mai mare) și originalul nu s-a schimbat */
				if ( ! $force && array_key_exists( $fmt, $row ) && ( $row[ $fmt ] > 0 ? $have : true ) ) continue;
				$r = self::convert_file( $f, $fmt, (int) $s[ 'avif' === $fmt ? 'q_avif' : 'q_webp' ] );
				if ( false === $r ) {
					$stats['errors']++;
					unset( $row[ $fmt ] );
				} else {
					$row[ $fmt ] = (int) $r;
				}
			}
			/* AVIF se livrează înaintea WebP: dacă iese mai mare decât WebP, nu-l păstrăm */
			if ( ! empty( $row['avif'] ) && ! empty( $row['webp'] ) && $row['avif'] >= $row['webp'] ) {
				@unlink( $f . '.avif' ); // phpcs:ignore
				$row['avif'] = 0;
			}
			$new[ $rel ] = $row;
			$stats['files']++;
			$stats['orig'] += $row['o'];
			$stats['webp'] += self::served( $row, false );
			$stats['best'] += self::served( $row, true );
		}
		update_post_meta( $id, self::META, $new );
		return $stats;
	}

	public static function delete_variants( $id ) {
		foreach ( self::files( (int) $id ) as $f ) {
			foreach ( array( 'webp', 'avif' ) as $fmt ) {
				if ( file_exists( $f . '.' . $fmt ) ) @unlink( $f . '.' . $fmt ); // phpcs:ignore
			}
		}
		delete_post_meta( (int) $id, self::META );
	}

	/* ================= imagini noi ================= */

	public static function queue( $meta, $id ) {
		$id = (int) $id;
		if ( $id && in_array( get_post_mime_type( $id ), self::MIMES, true ) && ! wp_next_scheduled( self::CRON, array( $id ) ) ) {
			wp_schedule_single_event( time() + 10, self::CRON, array( $id ) );
		}
		return $meta;
	}

	public static function cron_convert( $id ) {
		self::convert_attachment( (int) $id );
	}

	/* ================= statistici ================= */

	/* ce primește browserul: cu AVIF (cel mai mic dintre AVIF / WebP / original) sau doar WebP */
	private static function served( array $row, bool $with_avif ): int {
		$o    = (int) ( $row['o'] ?? 0 );
		$best = ! empty( $row['webp'] ) ? min( $o, (int) $row['webp'] ) : $o;
		if ( $with_avif && ! empty( $row['avif'] ) ) $best = (int) $row['avif'];
		return $best;
	}

	public static function totals(): array {
		global $wpdb;
		$t    = array( 'images' => 0, 'done' => 0, 'orig' => 0, 'webp' => 0, 'best' => 0 );
		$t['images'] = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type IN ('image/jpeg','image/png')" ); // phpcs:ignore
		$rows = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s", self::META ) ); // phpcs:ignore
		foreach ( $rows as $v ) {
			$state = maybe_unserialize( $v );
			if ( ! is_array( $state ) || ! $state ) continue;
			$t['done']++;
			foreach ( $state as $row ) {
				$t['orig'] += (int) ( $row['o'] ?? 0 );
				$t['webp'] += self::served( $row, false );
				$t['best'] += self::served( $row, true );
			}
		}
		return $t;
	}
}
