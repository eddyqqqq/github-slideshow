<?php
/**
 * Pavva Widgets – Pavva Motion (animații la scroll)
 *
 * GSAP + ScrollTrigger + SplitText și Lenis (scroll lin), încărcate local din
 * assets/motion/vendor, doar pe paginile care au cel puțin un element animat.
 *
 * Cum se pune o animație pe un element:
 *  - Elementor V4 (Flexbox, Div block, Heading, Paragraph, Button…):
 *      General → „Pavva – Animație”, sau Style → Classes cu o clasă „pv-lines” etc.
 *  - Elemente clasice (widgeturi, inclusiv cele Pavva, containere):
 *      Advanced → „Pavva – Animație”, sau Advanced → CSS Classes „pv-lines”.
 *  - Oriunde: atributul data-pv="lines" (+ data-pv-delay etc.).
 * Setările generale: Setări → Pavva – Animații.
 *
 * @package Pavva_Widgets
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;

final class Pavva_W_Motion {

    const OPTION = 'pavva_motion';
    const STACKS = array( 'common', 'common-optimized', 'container', 'section', 'column' );

    /** @var array elementele V4 în curs de afișare (pentru clase) */
    private static $stack = array();
    /** @var array data-id → animație (rezervă pentru elementele V4) */
    private static $map = array();

    public static function types(): array {
        return array(
            'lines'      => 'Text – reveal pe rânduri',
            'words'      => 'Text – cuvinte în cascadă',
            'blur'       => 'Text – cuvinte din blur',
            'chars'      => 'Text – litere în cascadă',
            'scrub'      => 'Text – se umple la scroll',
            'count'      => 'Cifră care crește',
            'fade-up'    => 'Apariție de jos',
            'stagger'    => 'Cascadă (carduri, coloane, iconițe)',
            'img-reveal' => 'Imagine dezvelită',
            'parallax'   => 'Parallax (imagine)',
            'draw'       => 'Linie / SVG desenat',
            'pin-zoom'   => 'Secțiune fixată + imagine care crește',
            'hscroll'    => 'Galerie orizontală la scroll',
            'marquee'    => 'Bandă de text care curge',
            'magnetic'   => 'Buton magnetic',
        );
    }

    public static function defaults(): array {
        return array( 'enabled' => 1, 'intensity' => 'soft', 'smooth' => 1, 'mobile' => 'simple' );
    }

    public static function settings(): array {
        $o = get_option( self::OPTION, array() );
        return array_merge( self::defaults(), is_array( $o ) ? $o : array() );
    }

    public static function init() {
        /* setările generale (admin) */
        add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );

        $s = self::settings();
        if ( empty( $s['enabled'] ) ) return;

        /* pe site */
        add_action( 'wp_head', array( __CLASS__, 'head' ), 2 );
        add_action( 'wp_footer', array( __CLASS__, 'footer' ), 50 );

        /* Elementor clasic: Advanced → „Pavva – Animație” */
        foreach ( self::STACKS as $stack ) {
            add_action( "elementor/element/{$stack}/_section_responsive/after_section_end", array( __CLASS__, 'controls' ), 11, 2 );
        }
        add_action( 'elementor/frontend/before_render', array( __CLASS__, 'before_render' ) );
        add_action( 'elementor/frontend/after_render', array( __CLASS__, 'after_render' ) );

        /* Elementor V4: General → „Pavva – Animație” */
        add_filter( 'elementor/atomic-widgets/props-schema', array( __CLASS__, 'v4_schema' ), 5 );
        add_filter( 'elementor/atomic-widgets/controls', array( __CLASS__, 'v4_controls' ), 11, 2 );
        add_filter( 'elementor/atomic-widgets/settings/transformers/classes', array( __CLASS__, 'v4_classes' ), PHP_INT_MAX - 10, 2 );
    }

    private static function is_editor(): bool {
        if ( class_exists( 'Pavva_W_Conditional' ) ) return Pavva_W_Conditional::is_editor();
        return false;
    }

    private static function front(): bool {
        return ! is_admin() && ! self::is_editor() && ! is_customize_preview();
    }

    /* ================= pe site ================= */

    /* În <head>: elementele animate stau ascunse până pornește animația (fără „clipire”). */
    public static function head() {
        if ( ! self::front() ) return;
        $css = file_get_contents( PAVVA_WIDGETS_PATH . 'assets/motion/pavva-motion.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        echo '<style id="pavva-motion-css">' . $css . '</style>' . "\n"; // phpcs:ignore -- fișier propriu
        /* rezervă: dacă animațiile nu pornesc în 5 s, conținutul apare oricum */
        echo '<script id="pavva-motion-head">(function(h){h.classList.add("pv-js");setTimeout(function(){if(!h.classList.contains("pv-ready"))h.classList.remove("pv-js");},5000);})(document.documentElement);</script>' . "\n";
    }

    /* În footer: scripturile se încarcă doar dacă pagina are elemente animate. */
    public static function footer() {
        if ( ! self::front() ) return;
        $s    = self::settings();
        $base = PAVVA_WIDGETS_URL . 'assets/motion/';
        $ver  = function ( $file ) { return pavva_widgets_asset_version( 'assets/motion/' . $file ); };
        $scripts = array(
            $base . 'vendor/gsap.min.js?ver=' . $ver( 'vendor/gsap.min.js' ),
            $base . 'vendor/ScrollTrigger.min.js?ver=' . $ver( 'vendor/ScrollTrigger.min.js' ),
            $base . 'vendor/SplitText.min.js?ver=' . $ver( 'vendor/SplitText.min.js' ),
        );
        if ( ! empty( $s['smooth'] ) ) {
            $scripts[] = $base . 'vendor/lenis.min.js?ver=' . $ver( 'vendor/lenis.min.js' );
        }
        $scripts[] = $base . 'pavva-motion.js?ver=' . $ver( 'pavva-motion.js' );

        $config = array(
            'intensity' => 'bold' === $s['intensity'] ? 'bold' : 'soft',
            'smooth'    => ! empty( $s['smooth'] ),
            'mobile'    => in_array( $s['mobile'], array( 'full', 'simple', 'off' ), true ) ? $s['mobile'] : 'simple',
            'bp'        => self::mobile_breakpoint(),
        );
        $selector = implode( ',', array_map( function ( $t ) { return '.pv-' . $t . ',[data-pv="' . $t . '"]'; }, array_keys( self::types() ) ) );

        echo "<script id=\"pavva-motion\">\n";
        echo 'window.PavvaMotionMap=' . wp_json_encode( (object) self::$map ) . ";\n";
        echo '(function(d,w){var h=d.documentElement,c=' . wp_json_encode( $config ) . ',s=' . wp_json_encode( $scripts ) . ',q=' . wp_json_encode( $selector ) . ';'
            . 'function done(){h.classList.remove("pv-js");h.classList.add("pv-ready");}'
            . 'if(w.matchMedia&&w.matchMedia("(prefers-reduced-motion: reduce)").matches){done();return;}'
            . 'if(!d.querySelector(q)&&!Object.keys(w.PavvaMotionMap||{}).length){done();return;}'
            . 'var i=0;(function next(){if(i>=s.length){if(w.PavvaMotion){w.PavvaMotion.init(c);}else{done();}return;}'
            . 'var t=d.createElement("script");t.src=s[i++];t.onload=next;t.onerror=done;d.body.appendChild(t);})();'
            . '})(document,window);';
        echo "\n</script>\n";
    }

    private static function mobile_breakpoint(): int {
        if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->breakpoints ) ) {
            try {
                $bp = \Elementor\Plugin::$instance->breakpoints->get_breakpoints( 'mobile' );
                if ( $bp && method_exists( $bp, 'get_value' ) ) return (int) $bp->get_value();
            } catch ( \Throwable $e ) {} // phpcs:ignore
        }
        return 767;
    }

    /* ================= Elementor clasic ================= */

    public static function controls( $element, $args ) {
        if ( $element->get_controls( 'pavva_anim' ) ) return;

        $element->start_controls_section(
            'pavva_anim_section',
            array(
                'label' => 'Pavva – Animație',
                'tab'   => Controls_Manager::TAB_ADVANCED,
            )
        );

        $element->add_control(
            'pavva_anim',
            array(
                'label'   => 'Animație la scroll',
                'type'    => Controls_Manager::SELECT,
                'default' => '',
                'options' => array( '' => 'Fără' ) + self::types(),
            )
        );

        $element->add_control(
            'pavva_anim_delay',
            array(
                'label'     => 'Întârziere (secunde)',
                'type'      => Controls_Manager::NUMBER,
                'min'       => 0,
                'max'       => 3,
                'step'      => 0.05,
                'condition' => array( 'pavva_anim!' => '' ),
            )
        );

        $element->add_control(
            'pavva_anim_duration',
            array(
                'label'       => 'Durată (secunde)',
                'description' => 'Gol = durata recomandată.',
                'type'        => Controls_Manager::NUMBER,
                'min'         => 0.2,
                'max'         => 4,
                'step'        => 0.05,
                'condition'   => array( 'pavva_anim!' => array( '', 'scrub', 'parallax', 'pin-zoom', 'hscroll', 'marquee', 'magnetic' ) ),
            )
        );

        $element->add_control(
            'pavva_anim_stagger',
            array(
                'label'     => 'Pauză între elemente (secunde)',
                'type'      => Controls_Manager::NUMBER,
                'min'       => 0,
                'max'       => 1,
                'step'      => 0.01,
                'condition' => array( 'pavva_anim' => array( 'lines', 'words', 'blur', 'chars', 'stagger', 'draw' ) ),
            )
        );

        $element->add_control(
            'pavva_anim_from',
            array(
                'label'     => 'Direcție',
                'type'      => Controls_Manager::SELECT,
                'default'   => '',
                'options'   => array(
                    ''       => 'Implicit',
                    'bottom' => 'De jos',
                    'top'    => 'De sus',
                    'left'   => 'Din stânga',
                    'right'  => 'Din dreapta',
                    'center' => 'Din centru (linie)',
                ),
                'condition' => array( 'pavva_anim' => array( 'img-reveal', 'draw', 'marquee' ) ),
            )
        );

        $element->add_control(
            'pavva_anim_speed',
            array(
                'label'       => 'Viteză / forță',
                'description' => 'Parallax: 4–20 (implicit 10). Bandă: px pe secundă (implicit 70). Magnetic: 0.1–0.8 (implicit 0.35).',
                'type'        => Controls_Manager::NUMBER,
                'min'         => 0,
                'step'        => 0.05,
                'condition'   => array( 'pavva_anim' => array( 'parallax', 'marquee', 'magnetic' ) ),
            )
        );

        $element->add_control(
            'pavva_anim_mobile',
            array(
                'label'        => 'Oprită pe telefon',
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => 'Da',
                'label_off'    => 'Nu',
                'return_value' => 'off',
                'default'      => '',
                'condition'    => array( 'pavva_anim!' => '' ),
            )
        );

        $element->add_control(
            'pavva_anim_note',
            array(
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => 'Animația rulează pe site și în Preview, nu în editor. „Cascadă” pe un widget Pavva (Grilă, Carusel, Culori, Dimensiuni, Caracteristici, Acordeon) animă pe rând cardurile lui. Setări generale: Setări → Pavva – Animații.',
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                'condition'       => array( 'pavva_anim!' => '' ),
            )
        );

        $element->end_controls_section();
    }

    private static function unwrap( $v ) {
        if ( is_array( $v ) && array_key_exists( 'value', $v ) && isset( $v['$$type'] ) ) return $v['value'];
        return $v;
    }

    private static function is_atomic( $element ): bool {
        return is_object( $element ) && method_exists( $element, 'get_atomic_settings' );
    }

    public static function before_render( $element ) {
        if ( ! is_object( $element ) || ! method_exists( $element, 'get_settings' ) || self::is_editor() ) return;

        if ( self::is_atomic( $element ) ) {
            self::$stack[] = $element;
            $type = (string) self::unwrap( $element->get_settings( 'pavva_anim' ) );
            if ( $type && 'none' !== $type && isset( self::types()[ $type ] ) && method_exists( $element, 'get_id' ) ) {
                $o = array( 'type' => $type );
                $delay = self::unwrap( $element->get_settings( 'pavva_anim_delay' ) );
                if ( is_numeric( $delay ) && $delay > 0 ) $o['delay'] = round( $delay / 1000, 2 );
                if ( self::unwrap( $element->get_settings( 'pavva_anim_mobile_off' ) ) ) $o['mobile'] = 'off';
                self::$map[ (string) $element->get_id() ] = $o;
            }
            return;
        }

        $type = (string) $element->get_settings( 'pavva_anim' );
        if ( '' === $type || ! isset( self::types()[ $type ] ) ) return;
        $attrs = array( 'data-pv' => $type );
        foreach ( array( 'delay', 'duration', 'stagger', 'from', 'speed', 'mobile' ) as $key ) {
            $v = $element->get_settings( 'pavva_anim_' . $key );
            if ( '' !== $v && null !== $v && false !== $v ) $attrs[ 'data-pv-' . $key ] = (string) $v;
        }
        $element->add_render_attribute( '_wrapper', $attrs );
    }

    public static function after_render( $element ) {
        if ( self::is_atomic( $element ) && self::$stack && end( self::$stack ) === $element ) {
            array_pop( self::$stack );
        }
    }

    /* ================= Elementor V4 ================= */

    private static function v4_available(): bool {
        foreach ( array(
            '\Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type',
            '\Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type',
            '\Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type',
            '\Elementor\Modules\AtomicWidgets\Controls\Section',
            '\Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control',
            '\Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control',
            '\Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control',
        ) as $class ) {
            if ( ! class_exists( $class ) ) return false;
        }
        return true;
    }

    public static function v4_schema( $schema ) {
        if ( ! is_array( $schema ) || isset( $schema['pavva_anim'] ) || ! self::v4_available() ) return $schema;
        try {
            $schema['pavva_anim'] = \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type::make()
                ->enum( array_merge( array( 'none' ), array_keys( self::types() ) ) )
                ->default( 'none' );
            $schema['pavva_anim_delay']      = \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type::make();
            $schema['pavva_anim_mobile_off'] = \Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type::make()->default( false );
        } catch ( \Throwable $e ) {
            return $schema;
        }
        return $schema;
    }

    public static function v4_controls( $controls, $element = null ) {
        if ( ! is_array( $controls ) || ! self::v4_available() ) return $controls;
        foreach ( $controls as $c ) {
            if ( $c instanceof \Elementor\Modules\AtomicWidgets\Controls\Section && 'pavva_motion' === $c->get_id() ) return $controls;
        }
        $options = array( array( 'value' => 'none', 'label' => 'Fără' ) );
        foreach ( self::types() as $value => $label ) {
            $options[] = array( 'value' => $value, 'label' => $label );
        }
        try {
            $controls[] = \Elementor\Modules\AtomicWidgets\Controls\Section::make()
                ->set_label( 'Pavva – Animație' )
                ->set_id( 'pavva_motion' )
                ->set_items( array(
                    \Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control::bind_to( 'pavva_anim' )
                        ->set_label( 'Animație la scroll' )
                        ->set_options( $options )
                        ->set_description( 'Rulează pe site și în Preview, nu în editor. „Cascadă” pe un container animă pe rând elementele din el (carduri, coloane).' ),
                    \Elementor\Modules\AtomicWidgets\Controls\Types\Number_Control::bind_to( 'pavva_anim_delay' )
                        ->set_label( 'Întârziere (ms)' )
                        ->set_placeholder( '0' )
                        ->set_min( 0 )
                        ->set_max( 3000 )
                        ->set_step( 50 ),
                    \Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control::bind_to( 'pavva_anim_mobile_off' )
                        ->set_label( 'Oprită pe telefon' ),
                ) );
        } catch ( \Throwable $e ) {
            return $controls;
        }
        return $controls;
    }

    /* Clasele animației se adaugă direct în HTML-ul elementului V4 (fără „clipire” la încărcare). */
    public static function v4_classes( $value, $context = null ) {
        if ( ! is_array( $value ) || ! self::$stack ) return $value;
        $el   = end( self::$stack );
        $type = (string) self::unwrap( $el->get_settings( 'pavva_anim' ) );
        if ( ! $type || 'none' === $type || ! isset( self::types()[ $type ] ) ) return $value;
        $add = array( 'pv-' . $type );
        $delay = self::unwrap( $el->get_settings( 'pavva_anim_delay' ) );
        if ( is_numeric( $delay ) && $delay > 0 ) $add[] = 'pv-delay-' . (int) round( $delay / 100 );
        if ( self::unwrap( $el->get_settings( 'pavva_anim_mobile_off' ) ) ) $add[] = 'pv-mobile-off';
        foreach ( $add as $cls ) {
            if ( ! in_array( $cls, $value, true ) ) $value[] = $cls;
        }
        return $value;
    }

    /* ================= setări generale ================= */

    public static function admin_menu() {
        add_options_page( 'Pavva – Animații', 'Pavva – Animații', 'manage_options', 'pavva-motion', array( __CLASS__, 'page' ) );
    }

    public static function register_setting() {
        register_setting( 'pavva_motion', self::OPTION, array(
            'type'              => 'array',
            'sanitize_callback' => array( __CLASS__, 'sanitize' ),
            'default'           => self::defaults(),
        ) );
    }

    public static function sanitize( $in ): array {
        $in = is_array( $in ) ? $in : array();
        return array(
            'enabled'   => empty( $in['enabled'] ) ? 0 : 1,
            'intensity' => ( isset( $in['intensity'] ) && 'bold' === $in['intensity'] ) ? 'bold' : 'soft',
            'smooth'    => empty( $in['smooth'] ) ? 0 : 1,
            'mobile'    => ( isset( $in['mobile'] ) && in_array( $in['mobile'], array( 'full', 'simple', 'off' ), true ) ) ? $in['mobile'] : 'simple',
        );
    }

    public static function page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $s = self::settings();
        $n = self::OPTION;
        ?>
        <div class="wrap">
            <h1>Pavva – Animații</h1>
            <p>Animațiile se aleg pe fiecare element din Elementor: la elementele V4 în <strong>General → Pavva – Animație</strong>, la cele clasice și la widgeturile Pavva în <strong>Advanced → Pavva – Animație</strong>. Aici sunt setările pentru tot site-ul.</p>
            <form method="post" action="options.php">
                <?php settings_fields( 'pavva_motion' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Animații pe site</th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[enabled]" value="1" <?php checked( ! empty( $s['enabled'] ) ); ?>> Pornite</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Intensitate</th>
                        <td>
                            <label><input type="radio" name="<?php echo esc_attr( $n ); ?>[intensity]" value="soft" <?php checked( 'soft', $s['intensity'] ); ?>> Elegant (recomandat)</label><br>
                            <label><input type="radio" name="<?php echo esc_attr( $n ); ?>[intensity]" value="bold" <?php checked( 'bold', $s['intensity'] ); ?>> Spectaculos</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Scroll lin</th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[smooth]" value="1" <?php checked( ! empty( $s['smooth'] ) ); ?>> Pornit (doar pe calculator; pe telefon rămâne scroll-ul normal)</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Pe telefon</th>
                        <td>
                            <label><input type="radio" name="<?php echo esc_attr( $n ); ?>[mobile]" value="simple" <?php checked( 'simple', $s['mobile'] ); ?>> Simplificate (recomandat): fără secțiuni fixate, galerie orizontală, parallax și butoane magnetice; mișcări mai scurte</label><br>
                            <label><input type="radio" name="<?php echo esc_attr( $n ); ?>[mobile]" value="full" <?php checked( 'full', $s['mobile'] ); ?>> Complete (ca pe calculator, cu mișcări mai scurte)</label><br>
                            <label><input type="radio" name="<?php echo esc_attr( $n ); ?>[mobile]" value="off" <?php checked( 'off', $s['mobile'] ); ?>> Oprite</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button( 'Salvează' ); ?>
            </form>
            <h2>Clase (alternativă la panou)</h2>
            <p>În Elementor V4 le adaugi în <strong>Style → Classes</strong>, la cele clasice în <strong>Advanced → CSS Classes</strong>. Întârziere ca clasă: <code>pv-delay-3</code> = 0,3 s. Oprită pe telefon: <code>pv-mobile-off</code>.</p>
            <table class="widefat striped" style="max-width:820px">
                <thead><tr><th>Animație</th><th>Clasă</th></tr></thead>
                <tbody>
                <?php foreach ( self::types() as $key => $label ) : ?>
                    <tr><td><?php echo esc_html( $label ); ?></td><td><code>pv-<?php echo esc_html( $key ); ?></code></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
