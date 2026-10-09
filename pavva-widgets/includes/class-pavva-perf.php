<?php
/**
 * Pavva Widgets – Viteză (Setări → Pavva – Viteză)
 *
 *  - prima imagine din slider-ul din header se preîncarcă din <head> (LCP)
 *  - fonturile din Elementor → Custom Fonts: font-display: swap + preîncărcare
 *  - CSS / JS WooCommerce doar pe paginile de magazin (produse, categorii, coș)
 *
 * @package Pavva_Widgets
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_W_Perf {

    const OPTION = 'pavva_perf';
    const META   = '_pavva_lcp';

    /** @var bool prima imagine a paginii a fost deja reținută în cererea curentă */
    private static $lcp_done = false;

    public static function defaults(): array {
        return array( 'lcp' => 1, 'fonts_swap' => 1, 'fonts_preload' => 1, 'woo_assets' => 1 );
    }

    public static function settings(): array {
        $o = get_option( self::OPTION, array() );
        return array_merge( self::defaults(), is_array( $o ) ? $o : array() );
    }

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
        /* fonturile preîncărcate se recitesc când salvezi un font în Elementor → Custom Fonts */
        add_action( 'save_post_elementor_font', function () { delete_transient( 'pavva_perf_fonts' ); } );

        $s = self::settings();
        if ( ! empty( $s['lcp'] ) || ! empty( $s['fonts_preload'] ) ) {
            add_action( 'wp_head', array( __CLASS__, 'head' ), 1 );
        }
        if ( ! empty( $s['fonts_swap'] ) ) {
            /* Elementor Pro → Custom Fonts (după schimbare: Elementor → Tools → Regenerate CSS) */
            add_filter( 'elementor_pro/custom_fonts/font_display', function () { return 'swap'; } );
        }
        if ( ! empty( $s['woo_assets'] ) ) {
            add_action( 'wp_enqueue_scripts', array( __CLASS__, 'woo_assets' ), 99 );
        }
    }

    private static function front(): bool {
        if ( is_admin() || is_customize_preview() ) return false;
        if ( class_exists( 'Pavva_W_Conditional' ) && Pavva_W_Conditional::is_editor() ) return false;
        return true;
    }

    /* ================= imaginea din header ================= */

    /* apelată de slider la afișare: păstrează fișierele primei imagini pentru pagina curentă */
    public static function remember_lcp( array $list ) {
        if ( self::$lcp_done || ! self::front() || ! is_singular() ) return;
        self::$lcp_done = true;
        $id = (int) get_queried_object_id();
        if ( ! $id ) return;
        $old = get_post_meta( $id, self::META, true );
        if ( $old !== $list ) update_post_meta( $id, self::META, $list );
    }

    public static function head() {
        if ( ! self::front() ) return;
        $s = self::settings();

        if ( ! empty( $s['lcp'] ) && is_singular() ) {
            $list = get_post_meta( (int) get_queried_object_id(), self::META, true );
            if ( is_array( $list ) ) {
                foreach ( $list as $it ) {
                    if ( empty( $it['href'] ) ) continue;
                    $a = '<link rel="preload" as="image" fetchpriority="high" href="' . esc_url( $it['href'] ) . '"';
                    if ( ! empty( $it['srcset'] ) ) $a .= ' imagesrcset="' . esc_attr( $it['srcset'] ) . '" imagesizes="' . esc_attr( $it['sizes'] ?? '100vw' ) . '"';
                    if ( ! empty( $it['media'] ) ) $a .= ' media="' . esc_attr( $it['media'] ) . '"';
                    echo $a . ">\n"; // phpcs:ignore -- valori escapate mai sus
                }
            }
        }

        if ( ! empty( $s['fonts_preload'] ) ) {
            foreach ( self::font_files() as $url ) {
                $type = preg_match( '/\.woff2(\?|$)/i', $url ) ? 'font/woff2' : ( preg_match( '/\.woff(\?|$)/i', $url ) ? 'font/woff' : 'font/ttf' );
                echo '<link rel="preload" as="font" type="' . esc_attr( $type ) . '" href="' . esc_url( $url ) . '" crossorigin>' . "\n";
            }
        }
    }

    /* fonturile din Elementor → Custom Fonts (câte un fișier pe font, de preferat WOFF2) */
    private static function font_files(): array {
        $cached = get_transient( 'pavva_perf_fonts' );
        if ( is_array( $cached ) ) return $cached;
        $out   = array();
        $fonts = get_posts( array( 'post_type' => 'elementor_font', 'post_status' => 'publish', 'numberposts' => 6, 'fields' => 'ids' ) );
        foreach ( $fonts as $fid ) {
            $files = get_post_meta( $fid, 'elementor_font_files', true );
            if ( ! is_array( $files ) ) continue;
            foreach ( $files as $variation ) {
                /* doar WOFF2: fișierele TTF sunt prea mari și ar concura cu imaginea din header pe mobil */
                if ( ! empty( $variation['woff2']['url'] ) ) {
                    $out[] = $variation['woff2']['url'];
                    break; /* un singur fișier pe font (fonturile variabile au toate grosimile în el) */
                }
            }
        }
        $out = array_values( array_unique( $out ) );
        set_transient( 'pavva_perf_fonts', $out, DAY_IN_SECONDS );
        return $out;
    }

    /* ================= WooCommerce ================= */

    public static function woo_assets() {
        if ( ! function_exists( 'is_woocommerce' ) || ! self::front() ) return;
        if ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) return;
        foreach ( array( 'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen', 'woocommerce-inline', 'wc-blocks-style', 'wc-blocks-vendors-style' ) as $h ) {
            wp_dequeue_style( $h );
        }
        foreach ( array( 'wc-add-to-cart', 'woocommerce', 'wc-cart-fragments', 'jquery-blockui', 'wc-jquery-blockui', 'js-cookie', 'wc-js-cookie', 'wc-order-attribution', 'sourcebuster-js', 'wc-sourcebuster' ) as $h ) {
            wp_dequeue_script( $h );
        }
    }

    /* ================= setări ================= */

    public static function admin_menu() {
        add_options_page( 'Pavva – Viteză', 'Pavva – Viteză', 'manage_options', 'pavva-perf', array( __CLASS__, 'page' ) );
    }

    public static function register_setting() {
        register_setting( 'pavva_perf', self::OPTION, array(
            'type'              => 'array',
            'sanitize_callback' => array( __CLASS__, 'sanitize' ),
            'default'           => self::defaults(),
        ) );
    }

    public static function sanitize( $in ): array {
        delete_transient( 'pavva_perf_fonts' );
        $in  = is_array( $in ) ? $in : array();
        $out = array();
        foreach ( array_keys( self::defaults() ) as $k ) {
            $out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
        }
        return $out;
    }

    public static function page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $s    = self::settings();
        $n    = self::OPTION;
        $rows = array(
            'lcp'           => array( 'Imaginea din header', 'Prima imagine din slider-ul Pavva se încarcă prima, înaintea restului paginii. Celelalte slide-uri vin după afișarea paginii.' ),
            'fonts_swap'    => array( 'Fonturi – text vizibil imediat', 'Textul apare imediat cu un font de rezervă și trece la fontul Pavva când s-a încărcat (font-display: swap). După ce schimbi: Elementor → Tools → Regenerate CSS & Data.' ),
            'fonts_preload' => array( 'Fonturi – preîncărcare', 'Fonturile din Elementor → Custom Fonts încep să se descarce din primul moment. Doar cele cu fișier WOFF2 (TTF-urile sunt prea mari).' ),
            'woo_assets'    => array( 'WooCommerce doar pe paginile de magazin', 'Pe Contact, Fabrica, Avantaje etc. nu se mai încarcă CSS-ul și scripturile WooCommerce. Pe produse, categorii, coș și checkout rămân.' ),
        );
        ?>
        <div class="wrap">
            <h1>Pavva – Viteză</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'pavva_perf' ); ?>
                <table class="form-table" role="presentation">
                    <?php foreach ( $rows as $k => $r ) : ?>
                        <tr>
                            <th scope="row"><?php echo esc_html( $r[0] ); ?></th>
                            <td><label><input type="checkbox" name="<?php echo esc_attr( $n . '[' . $k . ']' ); ?>" value="1" <?php checked( ! empty( $s[ $k ] ) ); ?>> Pornit</label>
                                <p class="description"><?php echo esc_html( $r[1] ); ?></p></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php submit_button( 'Salvează' ); ?>
            </form>
        </div>
        <?php
    }
}
