<?php
/**
 * Pavva Products – import produse dintr-un pachet ZIP (products.json + imagini/)
 *
 * Produse → Import Pavva. Se încarcă pachetul, se vede lista cu ce se creează și ce există deja,
 * apoi produsele noi se creează pe rând (câte unul pe cerere, ca să nu expire serverul).
 * Produsele existente (același nume sau slug) nu se ating. Produsele noi se creează ca Ciornă.
 * Produsele create tot de aici pot primi imaginile dintr-un pachet nou („Actualizează imaginile”):
 * se înlocuiesc doar pozele importate, textele și ce ai schimbat de mână rămân.
 *
 * @package Pavva_Produse
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_P_Import {

    const PAGE  = 'pavva-import';
    const CAP   = 'edit_products';
    const NONCE = 'pavva_import';
    const DIR   = 'pavva-import';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_post_pavva_import_upload', array( $this, 'upload' ) );
        add_action( 'wp_ajax_pavva_import_one', array( $this, 'ajax_one' ) );
        add_action( 'admin_post_pavva_import_cleanup', array( $this, 'cleanup' ) );
    }

    public function menu() {
        add_submenu_page( 'edit.php?post_type=product', 'Import produse Pavva', 'Import Pavva', self::CAP, self::PAGE, array( $this, 'page' ) );
    }

    /* ---------------------------------------------------------------- pachetul pe disc */

    private static function base_dir(): string {
        $u = wp_upload_dir();
        return trailingslashit( $u['basedir'] ) . self::DIR . '/';
    }

    private static function token_ok( $t ): bool {
        return is_string( $t ) && (bool) preg_match( '/^[a-z0-9]{12}$/', $t );
    }

    /* folderul pachetului (products.json poate fi și într-un subfolder din ZIP) */
    private static function package_dir( $token ) {
        if ( ! self::token_ok( $token ) ) return false;
        $dir = self::base_dir() . $token . '/';
        if ( is_file( $dir . 'products.json' ) ) return $dir;
        foreach ( (array) glob( $dir . '*/products.json' ) as $f ) return trailingslashit( dirname( $f ) );
        return false;
    }

    private static function package( $token ) {
        $dir = self::package_dir( $token );
        if ( ! $dir ) return false;
        $data = json_decode( (string) file_get_contents( $dir . 'products.json' ), true );
        if ( ! is_array( $data ) || empty( $data['produse'] ) || ! is_array( $data['produse'] ) ) return false;
        return array( 'dir' => $dir, 'produse' => array_values( $data['produse'] ) );
    }

    /* ---------------------------------------------------------------- produse existente */

    private static function norm( $t ): string {
        $t = remove_accents( wp_strip_all_tags( html_entity_decode( (string) $t, ENT_QUOTES, 'UTF-8' ) ) );
        return trim( preg_replace( '/[^a-z0-9]+/', ' ', strtolower( $t ) ) );
    }

    private static function existing(): array {
        static $map = null;
        if ( null !== $map ) return $map;
        $map = array();
        $ids = get_posts( array( 'post_type' => 'product', 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'numberposts' => -1, 'fields' => 'ids' ) );
        foreach ( $ids as $id ) {
            $map[ 'n:' . self::norm( get_the_title( $id ) ) ] = $id;
            $map[ 's:' . get_post_field( 'post_name', $id ) ] = $id;
        }
        return $map;
    }

    /* după nume / slug, apoi după numele vechi din „alias” (ex. „Arco Granito” → „Granito Arco”) */
    private static function find_existing( array $p ): int {
        $m = self::existing();
        $names = array_merge( array( $p['titlu'] ?? '' ), (array) ( $p['alias'] ?? array() ) );
        foreach ( $names as $k => $t ) {
            $n = 'n:' . self::norm( $t );
            $s = 's:' . sanitize_title( 0 === $k && ! empty( $p['slug'] ) ? $p['slug'] : $t );
            if ( isset( $m[ $n ] ) ) return (int) $m[ $n ];
            if ( isset( $m[ $s ] ) ) return (int) $m[ $s ];
        }
        return 0;
    }

    /* produs creat de importator (doar acestora li se pot actualiza imaginile) */
    private static function imported( $id ): bool {
        return $id && '' !== (string) get_post_meta( $id, '_pavva_import_sursa', true );
    }

    /* iconurile caracteristicilor („Garanție - 10 ani” …), luate de la produsele care le au deja */
    private static function icons(): array {
        static $icons = null;
        if ( null !== $icons ) return $icons;
        $icons = array();
        $ids   = get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => PAVVA_Produse::META_CARACT ) );
        foreach ( $ids as $id ) {
            $items = get_post_meta( $id, PAVVA_Produse::META_CARACT, true );
            if ( ! is_array( $items ) ) continue;
            foreach ( $items as $it ) {
                $k = self::norm( $it['denumire'] ?? '' );
                if ( '' !== $k && ! empty( $it['icon_id'] ) && empty( $icons[ $k ] ) ) {
                    $icons[ $k ] = array( absint( $it['icon_id'] ), (string) $it['denumire'] );
                }
            }
        }
        return $icons;
    }

    /* ---------------------------------------------------------------- pagina din admin */

    public function page() {
        if ( ! current_user_can( self::CAP ) ) return;
        $token = isset( $_GET['pachet'] ) ? sanitize_key( wp_unslash( $_GET['pachet'] ) ) : '';
        echo '<div class="wrap"><h1>Import produse Pavva</h1>';

        if ( isset( $_GET['eroare'] ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['eroare'] ) ) ) . '</p></div>';
        }
        if ( isset( $_GET['sters'] ) ) {
            echo '<div class="notice notice-success"><p>Fișierele temporare ale pachetului au fost șterse.</p></div>';
        }

        $pkg = $token ? self::package( $token ) : false;
        if ( ! $pkg ) {
            echo '<p>Încarcă pachetul ZIP primit (conține <code>products.json</code> și folderul <code>imagini</code>). '
                . 'Vei vedea lista produselor înainte de import. Produsele care există deja pe site nu se modifică; cele noi se creează ca <strong>Ciornă</strong>.</p>';
            echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
            wp_nonce_field( self::NONCE );
            echo '<input type="hidden" name="action" value="pavva_import_upload">';
            echo '<p><input type="file" name="pachet" accept=".zip" required> ';
            submit_button( 'Încarcă pachetul', 'primary', 'submit', false );
            echo '</p><p class="description">Mărimea maximă acceptată de server: <strong>' . esc_html( size_format( wp_max_upload_size() ) ) . '</strong>. '
                . 'Dacă pachetul e mai mare, cere găzduirii să mărească <code>upload_max_filesize</code> și <code>post_max_size</code>.</p></form></div>';
            return;
        }

        $icons = self::icons();
        $rows  = '';
        $todo  = 0;
        $upd   = 0;
        foreach ( $pkg['produse'] as $i => $p ) {
            $ex  = self::find_existing( $p );
            $imp = self::imported( $ex );
            if ( ! $ex ) $todo++;
            if ( $imp ) $upd++;
            $miss = array();
            foreach ( (array) ( $p['caracteristici'] ?? array() ) as $c ) {
                if ( empty( $icons[ self::norm( $c ) ] ) ) $miss[] = $c;
            }
            $rows .= '<tr data-i="' . (int) $i . '" data-new="' . ( $ex ? '0' : '1' ) . '" data-upd="' . ( $imp ? '1' : '0' ) . '">'
                . '<td><strong>' . esc_html( $p['titlu'] ?? '' ) . '</strong><br><small>' . esc_html( $p['sursa'] ?? '' ) . '</small></td>'
                . '<td>' . esc_html( $p['categorie'] ?? '' ) . '</td>'
                . '<td>' . count( (array) ( $p['culori'] ?? array() ) ) . ' culori · ' . count( (array) ( $p['dimensiuni'] ?? array() ) ) . ' dimensiuni · ' . ( count( (array) ( $p['galerie'] ?? array() ) ) + ( empty( $p['imagine_principala'] ) ? 0 : 1 ) ) . ' imagini galerie / header'
                . ( $miss ? '<br><small style="color:#996800">Fără iconiță pe site: ' . esc_html( implode( ', ', $miss ) ) . '</small>' : '' ) . '</td>'
                . '<td class="pvi-status">' . ( $imp ? 'Importat deja – <a href="' . esc_url( get_edit_post_link( $ex ) ) . '">vezi produsul</a><br><small>imaginile se pot actualiza din acest pachet</small>'
                    : ( $ex ? 'Există deja – <a href="' . esc_url( get_edit_post_link( $ex ) ) . '">vezi produsul</a> (nu se modifică)' : '<span style="color:#2271b1">Va fi creat (Ciornă)</span>' ) ) . '</td></tr>';
        }

        echo '<p><strong>' . count( $pkg['produse'] ) . '</strong> produse în pachet · <strong>' . (int) $todo . '</strong> noi de creat.</p>';
        echo '<table class="widefat striped" style="max-width:1100px"><thead><tr><th>Produs</th><th>Categorie</th><th>Conținut</th><th>Status</th></tr></thead><tbody>' . $rows . '</tbody></table>';
        if ( $todo || $upd ) {
            echo '<p style="margin-top:16px">';
            if ( $todo ) echo '<button type="button" class="button button-primary button-hero pvi-run" data-mod="nou">Importă ' . (int) $todo . ' produse noi</button> ';
            if ( $upd ) echo '<button type="button" class="button button-hero pvi-run" data-mod="upd">Actualizează imaginile la ' . (int) $upd . ' produse importate</button>';
            echo ' <span id="pvi-progress" style="margin-left:12px"></span></p>';
            if ( $upd ) echo '<p class="description" style="max-width:900px">„Actualizează imaginile” înlocuiește doar pozele puse de import (principală, galerie / header, culori, dimensiuni) și numele, dacă s-a schimbat în pachet. Descrierile, categoria și pozele adăugate de tine rămân. Pozele vechi ale importului se șterg din Media.</p>';
        }
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:24px">';
        wp_nonce_field( self::NONCE );
        echo '<input type="hidden" name="action" value="pavva_import_cleanup"><input type="hidden" name="pachet" value="' . esc_attr( $token ) . '">';
        submit_button( 'Am terminat – șterge fișierele temporare ale pachetului', 'secondary', 'submit', false );
        echo '</form>';
        ?>
        <script>
        (function () {
            var btns = [].slice.call(document.querySelectorAll('.pvi-run'));
            var prog = document.getElementById('pvi-progress');
            btns.forEach(function (btn) { btn.addEventListener('click', function () {
                var mod = btn.getAttribute('data-mod');
                var rows = [].slice.call(document.querySelectorAll(mod === 'upd' ? 'tr[data-upd="1"]' : 'tr[data-new="1"]'));
                btns.forEach(function (b) { b.disabled = true; });
                var done = 0, ok = 0;
                (function next() {
                    if (!rows.length) {
                        prog.textContent = mod === 'upd' ? 'Gata: ' + ok + ' produse actualizate.' : 'Gata: ' + ok + ' produse create. Le găsești în Produse → Ciorne.';
                        btns.forEach(function (b) { if (b !== btn) b.disabled = false; });
                        return;
                    }
                    var tr = rows.shift(), st = tr.querySelector('.pvi-status');
                    st.textContent = mod === 'upd' ? 'Se actualizează…' : 'Se creează…';
                    prog.textContent = 'Produsul ' + (++done) + ' din ' + (done + rows.length) + '…';
                    var fd = new FormData();
                    fd.append('action', 'pavva_import_one');
                    fd.append('_wpnonce', <?php echo wp_json_encode( wp_create_nonce( self::NONCE ) ); ?>);
                    fd.append('pachet', <?php echo wp_json_encode( $token ); ?>);
                    fd.append('i', tr.getAttribute('data-i'));
                    fd.append('mod', mod);
                    fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function (r) { return r.json(); })
                        .then(function (r) {
                            if (r && r.success) { ok++; st.innerHTML = r.data.html; }
                            else { st.innerHTML = '<span style="color:#b32d2e">Eroare: ' + ((r && r.data) || 'necunoscută') + '</span>'; }
                        })
                        .catch(function (e) { st.innerHTML = '<span style="color:#b32d2e">Eroare: ' + e + '</span>'; })
                        .then(next);
                })();
            }); });
        })();
        </script>
        <?php
        echo '</div>';
    }

    /* ---------------------------------------------------------------- încărcarea ZIP */

    public function upload() {
        if ( ! current_user_can( self::CAP ) ) wp_die( 'Nu ai drepturi.' );
        check_admin_referer( self::NONCE );
        $back = admin_url( 'edit.php?post_type=product&page=' . self::PAGE );
        if ( empty( $_FILES['pachet']['tmp_name'] ) || ! is_uploaded_file( $_FILES['pachet']['tmp_name'] ) ) {
            wp_safe_redirect( add_query_arg( 'eroare', rawurlencode( 'Nu am primit fișierul (poate depășește ' . size_format( wp_max_upload_size() ) . ', limita serverului).' ), $back ) ); exit;
        }
        $name = sanitize_file_name( wp_unslash( $_FILES['pachet']['name'] ) );
        if ( ! preg_match( '/\.zip$/i', $name ) ) {
            wp_safe_redirect( add_query_arg( 'eroare', rawurlencode( 'Fișierul trebuie să fie .zip.' ), $back ) ); exit;
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        $token = strtolower( wp_generate_password( 12, false, false ) );
        $dir   = self::base_dir() . $token . '/';
        wp_mkdir_p( $dir );
        /* fișierele temporare nu se pot lista din browser */
        if ( ! is_file( self::base_dir() . 'index.php' ) ) @file_put_contents( self::base_dir() . 'index.php', "<?php // Silence\n" );
        $res = unzip_file( $_FILES['pachet']['tmp_name'], $dir );
        if ( is_wp_error( $res ) || ! self::package( $token ) ) {
            self::rrmdir( $dir );
            wp_safe_redirect( add_query_arg( 'eroare', rawurlencode( 'Pachetul nu a putut fi citit (lipsește products.json?).' ), $back ) ); exit;
        }
        /* în pachet păstrăm doar products.json și imagini */
        foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) ) as $f ) {
            if ( $f->isFile() && ! preg_match( '/\.(json|jpe?g|png|webp)$/i', $f->getFilename() ) ) @unlink( $f->getPathname() );
        }
        wp_safe_redirect( add_query_arg( 'pachet', $token, $back ) ); exit;
    }

    public function cleanup() {
        if ( ! current_user_can( self::CAP ) ) wp_die( 'Nu ai drepturi.' );
        check_admin_referer( self::NONCE );
        $token = isset( $_POST['pachet'] ) ? sanitize_key( wp_unslash( $_POST['pachet'] ) ) : '';
        if ( self::token_ok( $token ) ) self::rrmdir( self::base_dir() . $token . '/' );
        wp_safe_redirect( add_query_arg( 'sters', 1, admin_url( 'edit.php?post_type=product&page=' . self::PAGE ) ) ); exit;
    }

    private static function rrmdir( $dir ) {
        if ( ! is_dir( $dir ) ) return;
        foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST ) as $f ) {
            $f->isDir() ? @rmdir( $f->getPathname() ) : @unlink( $f->getPathname() );
        }
        @rmdir( $dir );
    }

    /* ---------------------------------------------------------------- crearea unui produs */

    public function ajax_one() {
        if ( ! current_user_can( self::CAP ) ) wp_send_json_error( 'Nu ai drepturi.' );
        check_ajax_referer( self::NONCE );
        $token = isset( $_POST['pachet'] ) ? sanitize_key( wp_unslash( $_POST['pachet'] ) ) : '';
        $i     = isset( $_POST['i'] ) ? absint( $_POST['i'] ) : -1;
        $pkg   = self::package( $token );
        if ( ! $pkg || ! isset( $pkg['produse'][ $i ] ) ) wp_send_json_error( 'Pachetul nu mai există.' );
        @set_time_limit( 300 );
        $p  = $pkg['produse'][ $i ];
        $ex = self::find_existing( $p );
        if ( isset( $_POST['mod'] ) && 'upd' === $_POST['mod'] ) {
            if ( ! self::imported( $ex ) ) wp_send_json_error( 'Produsul nu a fost creat de import.' );
            $r = self::update_images( $ex, $p, $pkg['dir'] );
            if ( is_wp_error( $r ) ) wp_send_json_error( $r->get_error_message() );
            wp_send_json_success( array( 'html' => '<span style="color:#008a20">Actualizat</span> – <a href="' . esc_url( get_edit_post_link( $ex ) ) . '">editează</a> · <a href="' . esc_url( get_preview_post_link( $ex ) ) . '" target="_blank">previzualizează</a>' ) );
        }
        if ( $ex ) wp_send_json_success( array( 'html' => 'Există deja – sărit (<a href="' . esc_url( get_edit_post_link( $ex ) ) . '">vezi</a>)' ) );
        $id = self::create( $p, $pkg['dir'] );
        if ( is_wp_error( $id ) ) wp_send_json_error( $id->get_error_message() );
        wp_send_json_success( array( 'html' => '<span style="color:#008a20">Creat (Ciornă)</span> – <a href="' . esc_url( get_edit_post_link( $id ) ) . '">editează</a> · <a href="' . esc_url( get_preview_post_link( $id ) ) . '" target="_blank">previzualizează</a>' ) );
    }

    private static function category_id( $name ) {
        $name = trim( (string) $name );
        if ( '' === $name ) return 0;
        $t = get_term_by( 'name', $name, 'product_cat' );
        if ( $t ) return (int) $t->term_id;
        $r = wp_insert_term( $name, 'product_cat' );
        return is_wp_error( $r ) ? 0 : (int) $r['term_id'];
    }

    private static function sideload( $dir, $file, $post_id, $alt ) {
        $file = basename( (string) $file );
        if ( '' === $file || ! preg_match( '/\.(jpe?g|png|webp)$/i', $file ) ) return 0;
        $src = $dir . 'imagini/' . $file;
        if ( ! is_file( $src ) ) return 0;
        $tmp = wp_tempnam( $file );
        if ( ! $tmp || ! @copy( $src, $tmp ) ) return 0;
        $id = media_handle_sideload( array( 'name' => $file, 'tmp_name' => $tmp ), $post_id, $alt );
        if ( is_wp_error( $id ) ) { @unlink( $tmp ); return 0; }
        update_post_meta( $id, '_wp_attachment_image_alt', $alt );
        update_post_meta( $id, '_pavva_import_img', 1 );
        return (int) $id;
    }

    /* poză pusă de import (marcată, sau după numele fișierului la importurile vechi) */
    private static function import_att( $att, $pid ): bool {
        $att = absint( $att );
        if ( ! $att ) return false;
        if ( get_post_meta( $att, '_pavva_import_img', true ) ) return true;
        return (int) get_post_field( 'post_parent', $att ) === (int) $pid
            && (bool) preg_match( '/^pavva-[a-z0-9-]+-(galerie|culoare|dimensiune)-/', basename( (string) get_attached_file( $att ) ) );
    }

    /* imaginea principală + galeria; principala intră și în galerie, ca slider-ul din header să aibă mereu o imagine */
    private static function gallery( array $p, $dir, $id, $title ): array {
        $main = self::sideload( $dir, $p['imagine_principala'] ?? '', $id, $title );
        $gal  = $main ? array( $main ) : array();
        foreach ( (array) ( $p['galerie'] ?? array() ) as $k => $f ) {
            $a = self::sideload( $dir, $f, $id, $title . ' – imagine ' . ( $k + 2 ) );
            if ( $a ) $gal[] = $a;
        }
        return array( $main, $gal );
    }

    private static function create( array $p, $dir ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $title = sanitize_text_field( $p['titlu'] ?? '' );
        if ( '' === $title ) return new WP_Error( 'pvi', 'Produs fără nume.' );

        $product = new WC_Product_Simple();
        $product->set_name( $title );
        $product->set_slug( sanitize_title( $p['slug'] ?? $title ) );
        $product->set_status( 'draft' );
        $product->set_catalog_visibility( 'visible' );
        $product->set_short_description( wp_kses_post( $p['descriere_scurta'] ?? '' ) );
        $product->set_description( wp_kses_post( $p['descriere'] ?? '' ) );
        $cat = self::category_id( $p['categorie'] ?? '' );
        if ( $cat ) $product->set_category_ids( array( $cat ) );
        $id = $product->save();
        if ( ! $id ) return new WP_Error( 'pvi', 'Produsul nu a putut fi creat.' );
        update_post_meta( $id, '_pavva_import_sursa', sanitize_text_field( $p['sursa'] ?? '' ) );

        /* imaginea principală + galeria */
        list( $main, $gal ) = self::gallery( $p, $dir, $id, $title );
        if ( $main ) $product->set_image_id( $main );
        if ( $gal ) $product->set_gallery_image_ids( $gal );
        $product->save();

        /* descriere sinteză (cardurile din categorie) */
        $sum = sanitize_textarea_field( $p['descriere_sinteza'] ?? '' );
        if ( '' !== $sum ) update_post_meta( $id, PAVVA_Produse::META_DESCRIERE_SINTEZA, $sum );

        /* caracteristici: iconurile existente pe site, după denumire */
        $icons = self::icons();
        $car   = array();
        foreach ( (array) ( $p['caracteristici'] ?? array() ) as $c ) {
            $k = self::norm( $c );
            if ( ! empty( $icons[ $k ] ) ) $car[] = array( 'denumire' => $icons[ $k ][1], 'icon_id' => $icons[ $k ][0], 'vizibil' => 1 );
        }
        if ( $car ) update_post_meta( $id, PAVVA_Produse::META_CARACT, $car );

        /* culori și finisaje */
        $col = array();
        foreach ( (array) ( $p['culori'] ?? array() ) as $c ) {
            $name = sanitize_text_field( $c['denumire'] ?? '' );
            $t    = self::sideload( $dir, $c['thumbnail'] ?? '', $id, $title . ' – ' . $name );
            if ( '' === $name && ! $t ) continue;
            $col[] = array( 'denumire' => $name, 'thumbnail_id' => $t, 'imagine_mare_id' => 0 );
        }
        if ( $col ) {
            update_post_meta( $id, PAVVA_Produse::META_CULORI, $col );
            update_post_meta( $id, PAVVA_Produse::META_CULORI_COLS, in_array( (int) ( $p['culori_coloane'] ?? 3 ), array( 3, 4 ), true ) ? (int) $p['culori_coloane'] : 3 );
        }

        /* dimensiuni și specificații */
        $dims = array();
        foreach ( (array) ( $p['dimensiuni'] ?? array() ) as $d ) {
            $specs = array();
            foreach ( (array) ( $d['specificatii'] ?? array() ) as $s ) {
                $l = sanitize_text_field( $s['eticheta'] ?? '' );
                $v = sanitize_text_field( $s['valoare'] ?? '' );
                if ( '' !== $l || '' !== $v ) $specs[] = array( 'eticheta' => $l, 'valoare' => $v );
            }
            $label = $specs ? $title . ' – ' . $specs[0]['valoare'] : $title;
            $img   = self::sideload( $dir, $d['imagine'] ?? '', $id, $label );
            if ( $img || $specs ) $dims[] = array( 'imagine_id' => $img, 'specificatii' => $specs, 'vizibil' => 1 );
        }
        if ( $dims ) update_post_meta( $id, PAVVA_Produse::META_DIM, $dims );

        /* lista din formularul de ofertă și cache-urile se reîmprospătează */
        clean_post_cache( $id );
        if ( class_exists( 'Pavva_W_Quote_Form' ) ) Pavva_W_Quote_Form::flush();
        return $id;
    }

    /* ---------------------------------------------------------------- actualizarea imaginilor */

    private static function update_images( $id, array $p, $dir ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $product = wc_get_product( $id );
        if ( ! $product ) return new WP_Error( 'pvi', 'Produsul nu mai există.' );
        $title = sanitize_text_field( $p['titlu'] ?? '' );
        if ( '' === $title ) $title = $product->get_name();
        $old = array();

        /* numele nou din pachet (slug-ul se schimbă doar dacă produsul nu e încă publicat) */
        if ( $product->get_name() !== $title ) {
            $product->set_name( $title );
            if ( 'publish' !== $product->get_status() ) $product->set_slug( sanitize_title( $p['slug'] ?? $title ) );
        }

        /* principala + galeria: pozele tale (neimportate) din galerie rămân, după cele noi */
        list( $main, $gal ) = self::gallery( $p, $dir, $id, $title );
        $cur_main = $product->get_image_id();
        if ( $main && ( ! $cur_main || self::import_att( $cur_main, $id ) ) ) {
            if ( $cur_main ) $old[] = $cur_main;
            $product->set_image_id( $main );
        }
        $keep = array();
        foreach ( $product->get_gallery_image_ids() as $g ) {
            if ( self::import_att( $g, $id ) ) $old[] = $g; else $keep[] = $g;
        }
        $product->set_gallery_image_ids( array_values( array_unique( array_merge( $gal, $keep ) ) ) );
        $product->save();

        /* culori: după denumire, apoi după poziție; se schimbă doar miniatura importată */
        $col = get_post_meta( $id, PAVVA_Produse::META_CULORI, true );
        if ( is_array( $col ) ) {
            $new = array_values( (array) ( $p['culori'] ?? array() ) );
            foreach ( $col as $k => $c ) {
                $match = null;
                foreach ( $new as $n ) {
                    if ( self::norm( $n['denumire'] ?? '' ) === self::norm( $c['denumire'] ?? '' ) ) { $match = $n; break; }
                }
                if ( ! $match && isset( $new[ $k ] ) ) $match = $new[ $k ];
                $t = absint( $c['thumbnail_id'] ?? 0 );
                if ( ! $match || ( $t && ! self::import_att( $t, $id ) ) ) continue;
                $a = self::sideload( $dir, $match['thumbnail'] ?? '', $id, $title . ' – ' . ( $c['denumire'] ?? '' ) );
                if ( ! $a ) continue;
                if ( $t ) $old[] = $t;
                $col[ $k ]['thumbnail_id'] = $a;
            }
            update_post_meta( $id, PAVVA_Produse::META_CULORI, $col );
        }

        /* dimensiuni: după poziție; specificațiile rămân cum sunt */
        $dims = get_post_meta( $id, PAVVA_Produse::META_DIM, true );
        if ( is_array( $dims ) ) {
            $new = array_values( (array) ( $p['dimensiuni'] ?? array() ) );
            foreach ( $dims as $k => $d ) {
                $t = absint( $d['imagine_id'] ?? 0 );
                if ( empty( $new[ $k ]['imagine'] ) || ( $t && ! self::import_att( $t, $id ) ) ) continue;
                $v = $d['specificatii'][0]['valoare'] ?? '';
                $a = self::sideload( $dir, $new[ $k ]['imagine'], $id, $v ? $title . ' – ' . $v : $title );
                if ( ! $a ) continue;
                if ( $t ) $old[] = $t;
                $dims[ $k ]['imagine_id'] = $a;
            }
            update_post_meta( $id, PAVVA_Produse::META_DIM, $dims );
        }

        /* pozele vechi ale importului, dacă nu mai sunt folosite în produs */
        $used = array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() );
        foreach ( array_unique( array_map( 'absint', $old ) ) as $a ) {
            if ( $a && ! in_array( $a, array_map( 'absint', $used ), true ) ) wp_delete_attachment( $a, true );
        }

        clean_post_cache( $id );
        if ( class_exists( 'Pavva_W_Quote_Form' ) ) Pavva_W_Quote_Form::flush();
        return true;
    }
}
