<?php
/**
 * Pavva Imagini – pagina din admin (Media → Pavva – Imagini) și conversia în masă (AJAX, câte o imagine pe rând)
 *
 * @package Pavva_Imagini
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class PVI_Admin {

	const SLUG = 'pavva-imagini';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'update_option_' . PVI_Settings::OPTION, array( 'PVI_Delivery', 'write_rules' ) );
		add_action( 'add_option_' . PVI_Settings::OPTION, array( 'PVI_Delivery', 'write_rules' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( PVI_FILE ), array( __CLASS__, 'links' ) );
		foreach ( array( 'ids', 'convert', 'test', 'delete_all' ) as $a ) {
			add_action( 'wp_ajax_pvi_' . $a, array( __CLASS__, 'ajax_' . $a ) );
		}
	}

	public static function menu() {
		add_media_page( 'Pavva – Imagini', 'Pavva – Imagini', 'manage_options', self::SLUG, array( __CLASS__, 'page' ) );
	}

	public static function links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'upload.php?page=' . self::SLUG ) ) . '">Setări</a>' );
		return $links;
	}

	public static function register() {
		register_setting( 'pvi', PVI_Settings::OPTION, array(
			'type'              => 'array',
			'sanitize_callback' => array( 'PVI_Settings', 'sanitize' ),
			'default'           => PVI_Settings::defaults(),
		) );
	}

	public static function assets( $hook ) {
		if ( 'media_page_' . self::SLUG !== $hook ) return;
		$v = PVI_VERSION . '.' . filemtime( PVI_PATH . 'assets/admin.js' );
		wp_enqueue_style( 'pvi-admin', PVI_URL . 'assets/admin.css', array(), $v );
		wp_enqueue_script( 'pvi-admin', PVI_URL . 'assets/admin.js', array(), $v, true );
		wp_localize_script( 'pvi-admin', 'PVI', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'pvi' ),
		) );
	}

	/* ================= AJAX ================= */

	private static function guard() {
		if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'pvi', 'nonce', false ) ) {
			wp_send_json_error( array( 'msg' => 'Nu ai drepturi sau sesiunea a expirat. Reîncarcă pagina.' ), 403 );
		}
	}

	/* lista imaginilor de convertit: „missing” = doar cele neconvertite încă, „all” = toate */
	public static function ajax_ids() {
		self::guard();
		global $wpdb;
		$all = ! empty( $_POST['all'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$sql = "SELECT p.ID FROM {$wpdb->posts} p WHERE p.post_type = 'attachment' AND p.post_mime_type IN ('image/jpeg','image/png')";
		if ( ! $all ) {
			$sql .= $wpdb->prepare( " AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} m WHERE m.post_id = p.ID AND m.meta_key = %s)", PVI_Converter::META );
		}
		$ids = array_map( 'intval', $wpdb->get_col( $sql . ' ORDER BY p.ID DESC' ) ); // phpcs:ignore
		wp_send_json_success( array( 'ids' => $ids ) );
	}

	public static function ajax_convert() {
		self::guard();
		$id    = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$force = ! empty( $_POST['force'] ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $id ) wp_send_json_error( array( 'msg' => 'Imagine lipsă.' ) );
		/* „Reconvertește tot”: forțăm doar la prima trecere prin imagine; dacă trebuie continuată, restul se face normal */
		$pass  = isset( $_POST['pass'] ) ? (int) $_POST['pass'] : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$limit = (int) ini_get( 'max_execution_time' );
		$stats = PVI_Converter::convert_attachment( $id, $force && 0 === $pass, $limit > 0 ? max( 8, min( 20, $limit - 10 ) ) : 20 );
		$stats['id']   = $id;
		$stats['name'] = basename( (string) get_attached_file( $id ) );
		wp_send_json_success( $stats );
	}

	public static function ajax_test() {
		self::guard();
		wp_send_json_success( PVI_Delivery::test() );
	}

	public static function ajax_delete_all() {
		self::guard();
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", PVI_Converter::META ) ); // phpcs:ignore
		foreach ( $ids as $id ) PVI_Converter::delete_variants( (int) $id );
		wp_send_json_success( array( 'count' => count( $ids ) ) );
	}

	/* ================= pagina ================= */

	private static function mb( int $b ): string {
		return number_format_i18n( $b / 1048576, 1 ) . ' MB';
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		$s   = PVI_Settings::get();
		$n   = PVI_Settings::OPTION;
		/* „Verifică din nou” (ex. după ce hostingul a activat AVIF) */
		$sup = PVI_Converter::support( isset( $_GET['pvi-recheck'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$t   = PVI_Converter::totals();
		$eng = function ( $fmt ) {
			$e = PVI_Converter::engine( $fmt );
			return $e ? '<span class="pvi-ok">disponibil</span> <span class="pvi-muted">(' . ( 'imagick' === $e ? 'Imagick' : 'GD' ) . ')</span>' : '<span class="pvi-no">indisponibil pe acest server</span>';
		};
		$deliver_ok = PVI_Delivery::rules_present();
		?>
		<div class="wrap pvi">
			<h1>Pavva – Imagini</h1>
			<p class="pvi-lead">Imaginile JPG și PNG din Media Library primesc variante <strong>AVIF</strong> și <strong>WebP</strong>. Fiecare browser primește automat cea mai mică variantă pe care o înțelege, la aceeași adresă. Originalele nu se modifică.</p>

			<div class="pvi-grid">
				<section class="pvi-card">
					<h2>Server</h2>
					<table class="pvi-kv">
						<tr><th>AVIF</th><td><?php echo $eng( 'avif' ); // phpcs:ignore ?></td></tr>
						<tr><th>WebP</th><td><?php echo $eng( 'webp' ); // phpcs:ignore ?></td></tr>
						<tr><th>Livrare automată</th><td><?php echo $deliver_ok ? '<span class="pvi-ok">reguli active</span> <span class="pvi-muted">(uploads/.htaccess)</span>' : ( $s['deliver'] ? '<span class="pvi-no">regulile nu au putut fi scrise</span>' : '<span class="pvi-muted">oprită</span>' ); // phpcs:ignore ?></td></tr>
					</table>
					<?php if ( ! PVI_Converter::engine( 'avif' ) ) : ?>
						<p class="pvi-note">AVIF lipsește: serverul nu poate crea fișiere AVIF (nici Imagick, nici PHP GD). Se fac doar variante WebP, care merg peste tot. Poți cere hostingului „Imagick cu codor AVIF (libheif + aom)” sau „PHP GD compilat cu AVIF”; apoi „Verifică din nou serverul” și „Reconvertește tot”.</p>
					<?php endif; ?>
					<p><button type="button" class="button" id="pvi-test">Testează livrarea</button> <a class="button-link" href="<?php echo esc_url( admin_url( 'upload.php?page=' . self::SLUG . '&pvi-recheck=1' ) ); ?>">Verifică din nou serverul</a></p>
					<p id="pvi-test-out" class="pvi-test-out" hidden></p>
				</section>

				<section class="pvi-card">
					<h2>Rezultat</h2>
					<table class="pvi-kv" id="pvi-totals">
						<tr><th>Imagini convertite</th><td><strong><?php echo (int) $t['done']; ?></strong> din <?php echo (int) $t['images']; ?></td></tr>
						<tr><th>Originale</th><td><?php echo esc_html( self::mb( $t['orig'] ) ); ?></td></tr>
						<tr><th>Cu WebP</th><td><?php echo esc_html( self::mb( $t['webp'] ) ); ?><?php echo $t['orig'] ? ' <span class="pvi-ok">−' . (int) round( 100 - $t['webp'] * 100 / $t['orig'] ) . '%</span>' : ''; // phpcs:ignore ?></td></tr>
						<tr><th>Cu AVIF + WebP</th><td><?php echo esc_html( self::mb( $t['best'] ) ); ?><?php echo $t['orig'] ? ' <span class="pvi-ok">−' . (int) round( 100 - $t['best'] * 100 / $t['orig'] ) . '%</span>' : ''; // phpcs:ignore ?></td></tr>
					</table>
					<p class="pvi-muted">Mărimile includ toate variantele generate de WordPress (miniaturi, medium, large).</p>
				</section>
			</div>

			<section class="pvi-card">
				<h2>Conversie</h2>
				<p>
					<button type="button" class="button button-primary" id="pvi-run">Convertește imaginile noi</button>
					<button type="button" class="button" id="pvi-run-all">Reconvertește tot</button>
					<button type="button" class="button" id="pvi-stop" hidden>Oprește</button>
				</p>
				<p class="pvi-muted">„Convertește imaginile noi” face doar ce nu e convertit încă. „Reconvertește tot” refă toate variantele (de ex. după ce schimbi calitatea). Poți închide pagina oricând și relua mai târziu.</p>
				<div class="pvi-progress" id="pvi-progress" hidden>
					<div class="pvi-bar"><span id="pvi-bar"></span></div>
					<p id="pvi-status" class="pvi-status"></p>
					<ol id="pvi-log" class="pvi-log"></ol>
				</div>
			</section>

			<section class="pvi-card">
				<h2>Setări</h2>
				<form method="post" action="options.php">
					<?php settings_fields( 'pvi' ); ?>
					<table class="form-table" role="presentation">
						<tr><th scope="row">Formate</th><td>
							<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[avif]" value="1" <?php checked( ! empty( $s['avif'] ) ); ?>> AVIF</label>&nbsp;&nbsp;
							<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[webp]" value="1" <?php checked( ! empty( $s['webp'] ) ); ?>> WebP</label>
						</td></tr>
						<tr><th scope="row"><label for="pvi-qa">Calitate AVIF</label></th><td>
							<input id="pvi-qa" type="number" min="20" max="100" name="<?php echo esc_attr( $n ); ?>[q_avif]" value="<?php echo (int) $s['q_avif']; ?>" class="small-text">
							<p class="description">Implicit 60. AVIF 55–65 arată ca WebP 80–85, dar e mai mic.</p>
						</td></tr>
						<tr><th scope="row"><label for="pvi-qw">Calitate WebP</label></th><td>
							<input id="pvi-qw" type="number" min="30" max="100" name="<?php echo esc_attr( $n ); ?>[q_webp]" value="<?php echo (int) $s['q_webp']; ?>" class="small-text">
							<p class="description">Implicit 82.</p>
						</td></tr>
						<tr><th scope="row">Imagini noi</th><td>
							<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[auto]" value="1" <?php checked( ! empty( $s['auto'] ) ); ?>> Se convertesc singure după încărcare (în fundal)</label>
						</td></tr>
						<tr><th scope="row">Livrare automată</th><td>
							<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[deliver]" value="1" <?php checked( ! empty( $s['deliver'] ) ); ?>> Browserele primesc AVIF / WebP în locul JPG / PNG</label>
							<p class="description">Prin reguli în <code>wp-content/uploads/.htaccess</code> (LiteSpeed / Apache). După ce schimbi ceva aici: LiteSpeed Cache → Purge All.</p>
						</td></tr>
					</table>
					<?php submit_button( 'Salvează' ); ?>
				</form>
			</section>

			<details class="pvi-card pvi-more">
				<summary>Reguli manuale și ștergere</summary>
				<p>Dacă livrarea nu se poate scrie automat, pune aceste linii la începutul fișierului <code>wp-content/uploads/.htaccess</code>:</p>
				<pre class="pvi-pre"><?php echo esc_html( "# BEGIN " . PVI_Delivery::MARKER . "\n" . implode( "\n", PVI_Delivery::rules() ) . "\n# END " . PVI_Delivery::MARKER ); ?></pre>
				<p><button type="button" class="button pvi-danger" id="pvi-delete">Șterge toate variantele AVIF / WebP</button> <span class="pvi-muted">Originalele rămân. Site-ul revine la imaginile JPG / PNG.</span></p>
			</details>
		</div>
		<?php
	}
}
